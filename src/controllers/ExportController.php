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
    public function actionExport()
    {
        $post = \Yii::$app->request->post();

        $sql = unserialize($post['export_sql']);
        $countSql = preg_replace('/^SELECT([^(FROM)])*FROM/i', 'SELECT COUNT(*) FROM', $sql);

        $count = \Yii::$app->db->createCommand($countSql)->queryScalar();
        $dataProviderNew = new \yii\data\SqlDataProvider([
            'sql' => $sql,
            'totalCount' => $count,
            'pagination' => [
                'pageSize' => 999999999,
            ],
        ]);

        $columns = \myzero1\gdexport\helpers\Helper::unserializeWithClosure($post['export_columns']);

        $exporter = new Spreadsheet([
            'dataProvider' => $dataProviderNew,
            'columns' => $columns,
        ]);
        $exporter->send(sprintf('%s-%s.xls', $post['export_name'], date('Y-m-d H:i:s')));
    }
}
