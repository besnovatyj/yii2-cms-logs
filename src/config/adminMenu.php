<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

return [
    // TXT Logs
    [
        'label' => 'TXT Logs',
        'iconClass' => 'bi bi-filetype-txt me-1',
        'url' => ['/Logs/backend/log/index'],
        'active' => static function () {
            return str_contains(\Yii::$app->request->url, 'Logs/backend/log');
        },
        '_meta' => [
            'placements' => [
//                [
//                    'location' => 'left-sidebar',
//                    'group' => 'Logs',
//                    'groupIcon' => 'bi bi-clock-history',
//                    'priority' => 100,
//                    'groupPriority' => 100,
//                ],
                [
                    'location' => 'right-sidebar',
                    'group' => 'Logs',
                    'groupIcon' => 'bi bi-clock-history',
                    'priority' => 100,
                    'groupPriority' => 100,
                ],
            ],
        ],
    ],
];
