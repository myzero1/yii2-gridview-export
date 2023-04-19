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
        $post = \Yii::$app->request->post();
        return \myzero1\gdexport\helpers\Helper::exportStream(
            $post['export_columns'], 
            $post['export_query'], 
            $post['export_sql'], 
            $post['export_name'], 
            $post['export_timeout']
        );
    }
}
