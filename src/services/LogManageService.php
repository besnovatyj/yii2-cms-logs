<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Logs\services;

use Besnovatyj\Logs\entities\Log;
use Besnovatyj\Logs\repositories\FileLogRepository;
use DomainException;
use Exception;
use RuntimeException;
use yii\data\ArrayDataProvider;
use ZipArchive;

/**
 * Сервис управления всеми логами приложения.
 */
class LogManageService
{
    public FileLogRepository|null $repo;

    public function __construct(FileLogRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getDataProvider($target = null): ArrayDataProvider
    {
        $models = match ($target) {
            'all' => $this->repo->findLogsMain(),
            'history' => $this->repo->findLogsHistory(),
            'zip' => $this->repo->findLogsZip(),
            default => throw new DomainException("Target '$target' is not a valid target type"),
        };

        return new ArrayDataProvider([
            'allModels' => $models,
            'sort' => [
                'attributes' => [
                    'name',
                    'size' => ['default' => SORT_DESC],
                    'updatedAt' => ['default' => SORT_DESC],
                ],
            ],
            'pagination' => ['pageSize' => 0],
        ]);
    }

    /**
     * Принудительная ротация текущего файла (copy+truncate): начать текущий
     * лог заново, снимок уводится в историю с меткой YmdHis. Открытый
     * дескриптор писателя продолжает писать в тот же (теперь пустой) inode.
     *
     * @throws RuntimeException
     */
    public function rotate(string $slug): void
    {
        $log = $this->repo->getBySlug($slug);
        if (!$log->getIsCurrent()) {
            throw new DomainException('Ротировать можно только текущий файл.');
        }

        $path = $log->getFilePath();
        $name = $log->getName();

        $stem = preg_match('/\.log$/', $name) ? substr($name, 0, -4) : $name;
        $target = $log->getPath() . '/' . $stem . '.' . date('YmdHis') . '.log';

        if (!copy($path, $target)) {
            throw new RuntimeException('Rotate error: copy failed');
        }
        if (file_put_contents($path, '') === false) {
            throw new RuntimeException('Rotate error: truncate failed');
        }
    }

    /**
     * Упаковать файл в ZIP. Любой файл, включая текущий: для текущего оригинал
     * не удаляется, а обнуляется, чтобы не рвать открытый дескриптор писателя.
     *
     * @throws Exception
     */
    public function zip(string $slug): void
    {
        $log = $this->repo->getBySlug($slug);
        $path = $log->getFilePath();

        $zip = new ZipArchive();
        if ($zip->open($path . '.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new DomainException('Cannot open zipFile, do you have permission?');
        }
        $zip->addFile($path, basename($path));
        $zip->close();

        if ($log->getIsCurrent()) {
            if (file_put_contents($path, '') === false) {
                throw new RuntimeException('Zip error: truncate failed');
            }
        } else {
            $this->repo->deleteFile($path);
        }
    }

    /**
     * История канала, к которому относится файл $slug.
     *
     * @return array{log: Log, fullSize: int, data_provider: ArrayDataProvider}
     * @throws Exception
     */
    public function getHistory(string $slug): array
    {
        $data = [];
        $data['log'] = $this->repo->getBySlug($slug);
        $allLogs = $this->repo->findHistoryBySlug($slug);

        $fullSize = 0;
        foreach ($allLogs as $log) {
            $fullSize += $log->getSize();
        }
        $data['fullSize'] = $fullSize;

        $data['data_provider'] = new ArrayDataProvider([
            'allModels' => $allLogs,
            'sort' => [
                'attributes' => [
                    'fileName',
                    'size' => ['default' => SORT_DESC],
                    'updatedAt' => ['default' => SORT_DESC],
                ],
                'defaultOrder' => ['updatedAt' => SORT_DESC],
            ],
            'pagination' => ['pageSize' => 0],
        ]);

        return $data;
    }

    /**
     * @throws Exception
     */
    public function find(string $slug): Log
    {
        return $this->repo->getBySlug($slug);
    }

    /**
     * @throws Exception
     */
    public function deleteLog($slug, $since): bool
    {
        $log = $this->repo->getBySlug($slug);
        if (isset($since) && ($log->getUpdatedAt() != $since)) {
            throw new Exception('Delete error: file has updated.');
        }
        $this->repo->deleteFile($log->getFilePath());
        return true;
    }
}
