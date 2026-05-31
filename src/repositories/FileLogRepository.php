<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Logs\repositories;

use Besnovatyj\Logs\entities\Log;
use Besnovatyj\Logs\entities\LogFileName;
use Exception;
use FilesystemIterator;
use InvalidArgumentException;
use Yii;

/**
 * Репозиторий всех лог-файлов приложения.
 *
 * Единый проход по @runtime/logs; классификация (current | history | zip)
 * детерминированным парсером {@see LogFileName} вместо набора регэкспов.
 */
class FileLogRepository
{
    public string|false $logsPath;

    public function __construct()
    {
        $this->logsPath = Yii::getAlias('@runtime/logs');
    }

    /**
     * @return Log[]
     */
    public function findLogsAll(): array
    {
        $out = [];
        if (!is_dir($this->logsPath)) {
            return $out;
        }
        foreach (new FilesystemIterator($this->logsPath) as $file) {
            if (!$file->isFile() || !LogFileName::looksLikeLog($file->getFilename())) {
                continue;
            }
            $out[] = new Log($file);
        }
        return $out;
    }

    /**
     * @return Log[]
     */
    public function findLogsMain(): array
    {
        return array_values(array_filter(
            $this->findLogsAll(),
            static fn(Log $log) => $log->getKind() === LogFileName::KIND_CURRENT
        ));
    }

    /**
     * @return Log[]
     */
    public function findLogsHistory(): array
    {
        return array_values(array_filter(
            $this->findLogsAll(),
            static fn(Log $log) => $log->getKind() === LogFileName::KIND_HISTORY
        ));
    }

    /**
     * @return Log[]
     */
    public function findLogsZip(): array
    {
        return array_values(array_filter(
            $this->findLogsAll(),
            static fn(Log $log) => $log->getKind() === LogFileName::KIND_ZIP
        ));
    }

    /**
     * @throws NotFoundException
     */
    public function getBySlug(string $slug): Log
    {
        foreach ($this->findLogsAll() as $log) {
            if ($log->getSlug() === $slug) {
                return $log;
            }
        }
        throw new NotFoundException('File not found.');
    }

    /**
     * История канала файла $slug: все его НЕ-current файлы (ротация + zip).
     *
     * @return Log[]
     * @throws NotFoundException
     */
    public function findHistoryBySlug(string $slug): array
    {
        $target = $this->getBySlug($slug);
        $channel = $target->getChannelKey();

        $selected = [];
        foreach ($this->findLogsAll() as $log) {
            if ($log->getChannelKey() === $channel
                && $log->getKind() !== LogFileName::KIND_CURRENT
            ) {
                $selected[] = $log;
            }
        }
        return $selected;
    }

    /**
     * @throws Exception
     */
    public function deleteFile(string $filePath): bool
    {
        if (!is_file($filePath)) {
            throw new InvalidArgumentException('Файл не существует.');
        }
        if (!unlink($filePath)) {
            throw new Exception('Не удалось удалить файл: ' . $filePath);
        }
        return true;
    }
}
