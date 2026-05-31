<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Logs\entities;

use SplFileInfo;
use Yii;
use yii\base\BaseObject;
use yii\caching\FileDependency;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;

class Log extends BaseObject
{
    private $_file;
    private $_slug;
    private $_size;
    private $_dateTimeStamp;
    private LogFileName $_parsed;

    /**
     * @param SplFileInfo $file
     * @param string|null $dateTimeStamp
     * @param array $config
     */
    public function __construct(SplFileInfo $file, string $dateTimeStamp = null, array $config = [])
    {
        $this->_file = $file;
        $this->_slug = Inflector::slug($file->getFilename());
        $this->_size = $file->getSize();
        $this->_dateTimeStamp = $dateTimeStamp;
        $this->_parsed = LogFileName::parse($file->getFilename());
        parent::__construct($config);
    }

    /** Разобранное имя файла (классификация, ключ канала). */
    public function getParsed(): LogFileName
    {
        return $this->_parsed;
    }

    /** Логический ключ канала/группы (для истории). */
    public function getChannelKey(): string
    {
        return $this->_parsed->getBase();
    }

    /** current | history | zip */
    public function getKind(): string
    {
        return $this->_parsed->getKind();
    }

    public function getIsCurrent(): bool
    {
        return $this->_parsed->isCurrent();
    }
    /**
     * Full filename with extension
     */
    public function getName(): string
    {
        return $this->_file->getFilename();
    }
    /**
     * Filename without extension, but with dot at the end
     */
    public function getBaseName(): string
    {
        return $this->_file->getBasename($this->getExtension());
    }
    /**
     * Filename extension
     */
    public function getExtension(): string
    {
        return $this->_file->getExtension();
    }
    /**
     * Path to the file without filename
     */
    public function getPath(): string
    {
        return $this->_file->getPath();
    }

    public function getSlug(): string
    {
        return $this->_slug;
    }
    /**
     * Path to the file with filename and extension
     */
    public function getFilePath(): string
    {
        return $this->_file->getPathname();
    }

    public function getDateTimeStamp(): ?string
    {
        return $this->_dateTimeStamp;
    }

    public function getIsZip(): bool
    {
        return StringHelper::endsWith($this->getFilePath(), '.zip');
    }

    public function getSize(): int
    {
        return $this->_size;
    }

    public function getUpdatedAt(): int
    {
        return $this->_file->getMTime();
    }

    public function getDownloadName(): string
    {
        return $this->getName();
    }

    /**
     * Подсчет сообщений каждого из возможных уровней важности
     *
     * @param bool $force
     * @return array - ['error' => 3, 'info' => 5, ...]
     */
    public function getCounts(bool $force = false): array
    {
        if ($this->_parsed->getIsZip()) {
            return [];
        }

        $key = $this->getFilePath() . '#counts';
        if (!$force && ($counts = Yii::$app->cache->get($key)) !== false) {
            return $counts;
        }

        $counts = [];
        if ($h = fopen($this->_file->getPathname(), 'r')) {
            while (($line = fgets($h)) !== false) {
                // ищем строку, которая начинается с - 2021-02-24 20:45:02
                if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $line)) {
                    // ищем четвертые квадратные скобки в строке и забираем их содержимое
                    // 2021-02-24 20:45:02 [127.0.0.1][1][gn207ja6q0kprnph0vbic28j2lnq4cvb][error]
                    if (preg_match('/^[\d\-\: ]+\[.*\]\[.*\]\[.*\]\[(.*)\]/U', $line, $m)) {
                        $level = $m[1];
                        if (!isset($counts[$level])) $counts[$level] = 0;
                        $counts[$level]++;
                    }
                }
            }
            fclose($h);
            Yii::$app->cache->set($key, $counts, null, new FileDependency([
                'fileName' => $this->getFilePath(),
            ]));
        }

        return $counts;
    }

}
