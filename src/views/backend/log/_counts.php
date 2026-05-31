<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Logs\entities\Log;
use yii\helpers\Html;
use yii\web\View;

/**
 * @var View $this
 * @var Log $log
 */
$levelClasses = [
    'trace' => 'bg-default',
    'info' => 'badge-info',
    'warning' => 'badge-warning',
    'error' => 'badge-danger',
];
$defaultLevelClass = 'bg-default';

foreach ($log->getCounts() as $level => $count) {
    $class = $levelClasses[$level] ?? $defaultLevelClass;
    echo Html::tag('span', $count, [
        'class' => 'badge ' . $class,
        'title' => $level,
    ]);
    echo ' ';
}
