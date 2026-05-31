<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Backend\Widgets\grid\ActionColumn;
use Besnovatyj\Logs\entities\Log;
use yii\data\ArrayDataProvider;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\web\View;

/**
 * @var View $this
 * @var Log $log
 * @var ArrayDataProvider $dataProvider
 * @var integer $fullSize
 */

$this->title = 'History (' . $log->getChannelKey() . ')';
$this->params['breadcrumbs'][] = ['label' => 'Logs', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$fullSizeFormat = \Yii::$app->formatter->asShortSize($fullSize);
?>

<div class="card">
    <div class="card-header d-md-flex justify-content-md-between">
        <div><?= Html::encode($this->title) ?></div>
        <div class="card-tools">
            <?= Html::a('Back to Logs', ['index'], ['class' => 'btn btn-sm btn-secondary']) ?>
        </div>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <strong>Channel:</strong> <?= Html::encode($log->getChannelKey()) ?><br>
            <strong>Total history + zip size:</strong> <?= $fullSizeFormat ?>
        </div>
        <?= GridView::widget([
            'layout' => '{items}',
            'tableOptions' => ['class' => 'table'],
            'options' => ['class' => 'grid-view table-responsive'],
            'dataProvider' => $dataProvider,
            'columns' => [
                [
                    'attribute' => 'fileName',
                    'format' => 'raw',
                    'value' => function (Log $hLog) {
                        return pathinfo($hLog->getFilePath(), PATHINFO_BASENAME);
                    },
                ], [
                    'attribute' => 'counts',
                    'format' => 'raw',
                    'value' => function (Log $log) {
                        return $this->render('_counts', ['log' => $log]);
                    },
                ], [
                    'attribute' => 'size',
                    'format' => 'shortSize',
                    'headerOptions' => ['class' => 'sort-ordinal'],
                ], [
                    'attribute' => 'updatedAt',
                    'format' => 'relativeTime',
                    'headerOptions' => ['class' => 'sort-numerical'],
                ], [
                    'class' => ActionColumn::class,
                    'template' => '{view} {zip} {download} {delete}',
                    'urlCreator' => function ($action, Log $log) {
                        return [$action, 'slug' => $log->getSlug()];
                    },
                    'buttons' => [
                        'view' => function ($url, Log $log) {
                            if ($log->getIsZip()) {
                                return '';
                            }
                            return Html::a('View', $url, [
                                'class' => 'btn  btn-xs btn-primary',
                                'target' => '_blank',
                            ]);
                        },
                        'zip' => function ($url, Log $log) {
                            if ($log->getIsZip()) {
                                return '';
                            }
                            return Html::a('Zip', $url, [
                                'class' => 'btn  btn-xs btn-info',
                                'data' => ['method' => 'post', 'confirm' => 'Упаковать в ZIP-архив?'],
                            ]);
                        },
                        'download' => function ($url, Log $log) {
                            return Html::a('Download', $url, [
                                'class' => 'btn  btn-xs btn-secondary',
                            ]);
                        },
                        'delete' => function ($url, Log $log) {
                            return Html::a('Delete', array_merge($url, ['since' => $log->getUpdatedAt()]), [
                                'class' => 'btn  btn-xs btn-danger',
                                'data' => ['method' => 'post', 'confirm' => 'Are you sure?'],
                            ]);
                        },
                    ],
                ],
            ],
        ]) ?>
    </div>
    <div class="card-footer clearfix"></div>
</div>
