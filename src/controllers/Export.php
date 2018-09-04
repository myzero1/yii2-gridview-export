<?php

namespace myzero1\gdexport\controllers;

use yii\web\Controller;

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
    public function actionExport($id)
    {
        $this->findModel($id)->delete();

        Yii::$app->getSession()->setFlash('success', '删除成功');
        return $this->redirect(['index']);
    }
}
