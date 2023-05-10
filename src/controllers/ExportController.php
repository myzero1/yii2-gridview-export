<?php

namespace myzero1\gdexport\controllers;

use yii\web\Controller;
use yii2tech\spreadsheet\Spreadsheet;
use yii\filters\VerbFilter;

/**
 * Default controller for the `test` module
 */
class ExportController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'export' => ['post'],
                    'big-export' => ['post'],
                    'export-file' => ['post'],
                    'export-file-pw' => ['post'],
                    'export-stream' => ['post'],
                    'export-stream-curl' => ['post'],
                    'export-stream-curl-data' => ['post'],
                ],
            ],
        ];
    }

    public function actionExport()
    {
        $post = \Yii::$app->request->post();
        return \myzero1\gdexport\helpers\Helper::exportSend(
            $post['export_columns'], 
            $exportQuery=$post['export_query'], 
            $exportSql=$post['export_sql'], 
            $exportName=$post['export_name'], 
            $writerType = $post['export_type'], 
            $timeout = $post['export_timeout']
        );
    }

    public function actionBigExport()
    {
        $post = \Yii::$app->request->post();

        return \myzero1\gdexport\helpers\Helper::exportBigSend(
            $post['export_columns'], 
            $exportQuery=$post['export_query'], 
            $exportSql=$post['export_sql'], 
            $exportName=$post['export_name'], 
            $writerType = $post['export_type'], 
            $timeout = $post['export_timeout']
        );
    }

    public function actionExportFile()
    {
        $post = \Yii::$app->request->post();

        // var_dump($post['export_name']);
        // var_dump($post['export_query']);
        // var_dump($post['export_sql']);
        // var_dump($post['export_columns']);
        // var_dump($post['export_type']);
        // var_dump($post['export_timeout']);
        // exit;
        return \myzero1\gdexport\helpers\Helper::exportFile(
            $post['export_columns'], 
            $post['export_query'], 
            $post['export_sql'], 
            $post['export_name'], 
            $post['export_timeout']
        );
    }

    public function actionExportFilePw()
    {
        $post = \Yii::$app->request->post();
        return \myzero1\gdexport\helpers\Helper::exportFile(
            $post['export_columns'], 
            $post['export_query'], 
            $post['export_sql'], 
            $post['export_name'], 
            $post['export_timeout'],
            \Yii::$app->user->identity->username
        );
    }

    public function actionExportStream()
    {
        \Yii::$app->session->close(); // 必须添加，否则使用curl在export中访问data时会卡死

        $url=\yii\helpers\Url::to($this->module->id.'/export/export-stream?z1action=z1_get_curl_data',true);
        $post = \Yii::$app->request->post();

        if (\Yii::$app->request->get('z1action')=='z1_get_curl_data') {
            return \myzero1\gdexport\helpers\Helper::exportStreamCurl(
                $post['export_columns'], 
                $post['export_query'], 
                $post['export_sql'], 
                $post['export_name'], 
                $post['export_timeout'],
                '',
                '',
                $post['page']
            );
        } else {
            if ($this->module->streamMode=='curl') {
                return \myzero1\gdexport\helpers\Helper::exportStreamCurlWrap($post,$url);
            } else {
                return \myzero1\gdexport\helpers\Helper::exportStream(
                    $post['export_columns'], 
                    $post['export_query'], 
                    $post['export_sql'], 
                    $post['export_name'], 
                    $post['export_timeout']
                );
            }
        }

    }

    public function actionExportStreamCurl()
    {
        \Yii::$app->session->close(); // 必须添加，否则使用curl在export中访问data时会卡死

        $url=\yii\helpers\Url::to($this->module->id.'/export/export-stream-curl-data',true);
        $post = \Yii::$app->request->post();

        return \myzero1\gdexport\helpers\Helper::exportStreamCurlWrap($post,$url);
    }

    public function actionExportStreamCurlData()
    {
        $post = \Yii::$app->request->post();

        // var_dump($post);exit;
        return \myzero1\gdexport\helpers\Helper::exportStreamCurl(
            $post['export_columns'], 
            $post['export_query'], 
            $post['export_sql'], 
            $post['export_name'], 
            $post['export_timeout'],
            '',
            '',
            $post['page']
        );
        exit;
    }
}
