<?php



/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Logs\controllers\backend;

use Besnovatyj\Logs\services\LogManageService;
use Besnovatyj\Kernel\controller\ControllerTrait;
use Exception;
use Yii;
use yii\web\Controller;
use yii\web\Response;

class LogController extends Controller
{
    use ControllerTrait;

    private LogManageService $service;

    public function __construct($id, $module, LogManageService $service, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->service = $service;
    }

    public function actionIndex(): string
    {
        return $this->render('index', [
            'dataProvider' => $this->service->getDataProvider('all')
        ]);
    }

    public function actionIndexHistory(): string
    {
        return $this->render('index-history', [
            'dataProvider' => $this->service->getDataProvider('history')
        ]);
    }

    public function actionIndexZip(): string
    {
        return $this->render('index-zip', [
            'dataProvider' => $this->service->getDataProvider('zip')
        ]);
    }

    public function actionView($slug)
    {
        try {
            $log = $this->service->find($slug);
            return Yii::$app->response->sendFile($log->getFilePath(), basename($log->getFilePath()), [
                'mimeType' => 'text/plain',
                'inline' => true
            ]);
        } catch (Exception $e) {
            $this->handleDomainException($e);
        }
        return $this->goReferer();
    }

    public function actionRotate($slug): Response
    {
        try {
            $this->service->rotate($slug);
            Yii::$app->session->setFlash('success', 'Rotate success');
            return $this->goReferer();
        } catch (Exception $e) {
            $this->handleDomainException($e);
        }
        return $this->goReferer();
    }

    public function actionHistory($slug): Response|string
    {
        try {
            $data = $this->service->getHistory($slug);
            return $this->render('history', [
                'log' => $data['log'],
                'dataProvider' => $data['data_provider'],
                'fullSize' => $data['fullSize'],
            ]);
        } catch (Exception $e) {
            $this->handleDomainException($e);
        }
        return $this->goReferer();
    }

    public function actionDelete($slug, $since = null): Response
    {
        try {
            $this->service->deleteLog($slug, $since);
            Yii::$app->session->setFlash('success', 'Delete success.');
        } catch (Exception $e) {
            $this->handleDomainException($e);
        }
        return $this->goReferer();
    }

    public function actionDownload($slug): void
    {
        try {
            $log = $this->service->find($slug);
            Yii::$app->response->sendFile($log->getFilePath())->send();
        } catch (Exception $e) {
            $this->handleDomainException($e);
        }
    }

    public function actionZip($slug): Response
    {
        try {
            $this->service->zip($slug);
            Yii::$app->session->setFlash('success', 'Zip success');
            return $this->redirect(['/Logs/backend/log/index-zip']);
        } catch (Exception $e) {
            $this->handleDomainException($e);
        }
        return $this->goReferer();
    }

    public function actionTail($slug, $line = 100, $stamp = null)
    {
        $log = $this->service->find($slug);
        $result = shell_exec("tail -n {$line} {$log->getFilePath()}");
        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'text/event-stream');
        return $result;
    }

}
