<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Backend\Widgets\grid\ActionColumn;
use Besnovatyj\Logs\entities\Log;
use Besnovatyj\Backend\Widgets\pagination\LinkPager;
use yii\data\ArrayDataProvider;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\web\View;

/**
 * @var View $this
 * @var ArrayDataProvider $dataProvider
 */

$this->title = 'History';
$this->params['breadcrumbs'][] = ['label' => 'Logs', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
    <p>
        <?= Html::a('Logs', ['/Logs/backend/log/index'], ['class' => 'btn  btn-secondary']) ?> >
        <?= Html::a('History', ['/Logs/backend/log/index-history'], ['class' => 'btn  btn-info', 'disabled' => 'disabled']) ?>
        >
        <?= Html::a('Zip', ['/Logs/backend/log/index-zip'], ['class' => 'btn  btn-secondary']) ?>
    </p>
    <div class="card">
        <div class="card-header"><?= $this->title ?></div>
        <!-- /.card-header -->
        <div class="card-body">
            <?= GridView::widget([
                'layout' => '{items}',
                'tableOptions' => ['class' => 'table'],
                'options' => ['class' => 'grid-view table-responsive'],
                'dataProvider' => $dataProvider,
                'columns' => [
                    [
                        'attribute' => 'name',
                        'format' => 'raw',
                        'value' => function (Log $log) {
                            return Html::tag('h5', join("\n", [
                                Html::encode($log->getName()),
                                '<br/>',
                                Html::tag('small', Html::encode($log->getFilePath()), ['style' => 'font-size:50%;']),
                            ]));
                        },
                    ], [
                        'attribute' => 'counts',
                        'format' => 'raw',
                        'headerOptions' => ['class' => 'sort-ordinal'],
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
                            'zip' => function ($url, Log $log) {
                                return Html::a('Zip', $url, [
                                    'class' => 'btn  btn-xs btn-success',
                                    'data' => ['method' => 'post', 'confirm' => 'Are you sure?'],
                                ]);
                            },
                            'view' => function ($url, Log $log) {
                                return Html::a('View', $url, [
                                    'class' => 'btn  btn-xs btn-primary',
                                    'target' => '_blank',
                                ]);
                            },
                            'delete' => function ($url, Log $log) {
                                return Html::a('Delete', array_merge($url, ['since' => $log->getUpdatedAt()]), [
                                    'class' => 'btn  btn-xs btn-danger',
                                    'data' => ['method' => 'post', 'data-a' => 'aa', 'confirm' => 'Are you sure?'],
                                ]);
                            },
                            'download' => function ($url, Log $log) {
                                return Html::a('Download', $url, [
                                    'class' => 'btn  btn-xs btn-secondary',
                                ]);
                            },
                        ],
                    ],
                ],
            ]) ?>
        </div>
        <!-- /.card-body -->
        <div class="card-footer clearfix">
            <nav aria-label="" class="nav-pagination">
                <?= LinkPager::widget([
                    'pagination' => $dataProvider->getPagination(),
                ]) ?>
            </nav>
        </div>
    </div>
    <!-- /.card -->
<?php
$this->registerCss(<<<CSS
.log-reader-index .table tbody td {
   vertical-align: middle;
}
CSS
);
