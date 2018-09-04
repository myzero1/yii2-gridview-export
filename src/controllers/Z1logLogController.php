<?php

namespace myzero1\log\controllers;

use Yii;
use myzero1\log\models\Z1logLog;
use myzero1\log\models\search\Z1logLogSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * Z1logLogController implements the CRUD actions for Z1logLog model.
 */
class Z1logLogController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all Z1logLog models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new Z1logLogSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Z1logLog model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Z1logLog model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Z1logLog();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Z1logLog model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Z1logLog model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Displays a single JfOperationLog model.
     * @param integer $id
     * @return mixed
     */
    public function actionSnapshoot($id)
    {
        // Yii::$app->getModule('rate');
        // $this->layout = '//blank.php';

        $model = $this->findModel($id);
        $sSnapshoot = base64_decode($model->screenshot);
        $sTip = Yii::t('app',
            '{sUsername} 于 {date} {text}',
            [
                'sUsername' => $model->user_name,
                'date' => date('Y-m-d H:i:s', $model->created),
                'text' => $model->text,
            ]);
        $sContent = sprintf('<div class="log-tip-wrap alert alert-warning alert-dismissible">%s<span class="log-tip">%s</span></div>%s',
            $sTip,
            Yii::t('app', '请注意：此页面为快照页面不可以进行操作。'),
            $sSnapshoot);
        // return $this->renderContent($sContent);
        return $this->render('snapshoot', ['content' => $sContent]);
        // return $this->renderContent('11');
    }

    /**
     * Finds the Z1logLog model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Z1logLog the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Z1logLog::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
