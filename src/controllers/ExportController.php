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
                ],
            ],
        ];
    }

    /**
     * Deletes an existing User2 model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionExportOld()
    {
        $post = \Yii::$app->request->post();

        // var_dump($post['export_query']);
        // var_dump($post['export_sql']);
        // exit;

        if (!empty($post['export_query'])) {
            $query = unserialize(json_decode($post['export_query']));
            $dataProvider = new \yii\data\ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => 999999999,
                ],
            ]);
        } else if (!empty($post['export_sql'])) {
            $sql = json_decode($post['export_sql'], true);
            $countSql = preg_replace('/^SELECT([^(FROM)])*FROM/i', 'SELECT COUNT(*) FROM', $sql);

            $count = \Yii::$app->db->createCommand($countSql)->queryScalar();
            $dataProvider = new \yii\data\SqlDataProvider([
                'sql' => $sql,
                'totalCount' => $count,
                'pagination' => [
                    'pageSize' => 999999999,
                ],
            ]);
        }

        $columns = \myzero1\gdexport\helpers\Helper::unserializeWithClosure($post['export_columns']);

        // var_dump($columns);exit;

        $exporter = new Spreadsheet([
            'dataProvider' => $dataProvider,
            'columns' => $columns,
        ]);
        $exporter->send(sprintf('%s-%s.xls', $post['export_name'], date('Y-m-d H:i:s')));
    }

    /**
     * Deletes an existing User2 model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
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
            $exportQuery=$post['export_query'], 
            $exportSql=$post['export_sql'], 
            $exportName=$post['export_name'], 
            $timeout=$post['export_timeout'],
            $pw='',
            $filePath=''
        );
    }

    public function actionExportFilePw()
    {
        $post = \Yii::$app->request->post();
        return \myzero1\gdexport\helpers\Helper::exportFile(
            $post['export_columns'], 
            $exportQuery=$post['export_query'], 
            $exportSql=$post['export_sql'], 
            $exportName=$post['export_name'], 
            $timeout=$post['export_timeout'],
            $pw=\Yii::$app->user->identity->username,
            $filePath=''
        );
    }

    public function actionBigExport()
    {
        $post = \Yii::$app->request->post();

        // var_dump($post['export_name']);
        // var_dump($post['export_query']);
        // var_dump($post['export_sql']);
        // var_dump($post['export_columns']);
        // var_dump($post['export_type']);
        // var_dump($post['export_timeout']);
        // exit;
        return \myzero1\gdexport\helpers\Helper::exportBigSend($post['export_columns'], $exportQuery=$post['export_query'], $exportSql=$post['export_sql'], $exportName=$post['export_name'], $writerType = $post['export_type'], $timeout = $post['export_timeout']);
    }
}
