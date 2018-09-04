<?php

namespace myzero1\log;

use Yii;
use yii\base\BootstrapInterface;
use yii\web\ForbiddenHttpException;
use yii\base\View;
use yii\base\Controller;
use yii\base\Application;

/**
 * This is the main module class for the z1rbacp module.
 *
 *
 * @author myzero1 <myzero1@sina.com>
 * @since 2.0
 */
class Module extends \yii\base\Module implements BootstrapInterface
{
    /**
     * @var string  if it equal to 'myzero1', main.php will add autoload and alias
     */
    public static $z1layout = '@vendor/myzero1/yii2-theme-adminlteiframe/src/views/layouts/main';  //blank
    /**
     * @var string  if it equal to 'myzero1', main.php will add autoload and alias
     */
    public $from;

    /**
     * @var array dependClass 
     *
        [
            'clssName' => 'Class ',
            'DefaultController' => '\z1demo\controllers\DefaultController',
        ]
     * 
     */
    public $dependClass;

    /**
     * @var array menu 
     *
        [
            "z1demo" => [
                'label' => Yii::t('app', '平台首页'),
                // 'url' => Yii::$app->homeUrl,
                'url' => ['/site/index'],
                'icon' => 'fa-dashboard',
                'active' => Yii::$app->request->url === Yii::$app->homeUrl || Yii::$app->request->url=='/site/index',
                'items' => [
                ]
            ],
        ];
     * 
     */
    public $menu;

    /**
     * @var array appKeyValueConfig 
     *
        [
            'auditProcess' => [
                'apply' => [
                    1 => '新开申请',
                    2 => '装机停业申请',
                    4 => '未装机停业申请',
                ],
                'deal' => [
                    1 => '审核通过',
                    2 => '审核不通过',
                    3 => '审核中',
                ],
            ],
        ]
     * 
     */
    public $appKeyValueConfig;

    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'myzero1\log\controllers';
    
    /**
     * @var array the params will merger to module
     */
    public $params = [];

    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        $this->addConfig($app);
        $this->addBehaviors($app);
        $this->rewriteLibs($app);

    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        \Yii::$app->params['dependClass'] = \Yii::$app->controller->module->dependClass;

        return true;
    }

    private function rewriteLibs($app){
        \Yii::$classMap['yii\db\Command'] = '@vendor/myzero1/yii2-log/src/components/libs/Command.php';
    }

    private function addConfig($app){
        $aConfig = require(__DIR__ . '/main.php');
        
        $z1logParams = array_merge($aConfig['params'], $this->params, $this->params);

        // var_dump($z1logParams);exit;

        $app->params['z1log']['params']['template'] = $z1logParams['template'];
        $app->params['z1log']['params']['userInfo'] = $z1logParams['userInfo'];

        if (isset($z1logParams['urlManager']['rules'])) {
            $app->urlManager->addRules(
                $z1logParams['urlManager']['rules'],
                false
            );
        }
    }

    private function addBehaviors($app){
        $app->on(Controller::EVENT_BEFORE_ACTION,['\myzero1\log\Module','z1logBeforeAction']);
        $app->view->on(View::EVENT_BEFORE_RENDER,['\myzero1\log\Module','z1logBeforeRender']);
        $app->on(Application::EVENT_AFTER_REQUEST,['\myzero1\log\Module','z1logAfterRequest']);
    }

    /**
     * Set method to get,bodyParams to array().
     * 
     * @author myzero1
     * @since 2.0.13
     */
    public static function z1logBeforeAction($event)
    {
        if (isset($_SESSION['z1logSaved'])) {
            $_POST['_method'] = 'get';
            $_SERVER['REQUEST_METHOD'] = 'get';

            \Yii::$app->request->bodyParams = array();
        }
    }

    /**
     * Rend view by renderAjax.
     * 
     * @param  array $event
     * 
     * @author myzero1
     * @since 2.0.13
     */
    public static function z1logBeforeRender($event)
    {
        if (isset(\Yii::$app->params['z1log']['params']['z1logToRending'])) {
            unset(\Yii::$app->params['z1log']['params']['z1logToRending']);

            $layout = \Yii::$app->layout;
            if (!is_null(\Yii::$app->controller->module->layout)) {
                $layout = \Yii::$app->controller->module->layout;
            }
            if (!is_null(\Yii::$app->controller->layout)) {
                $layout = \Yii::$app->controller->layout;
            }

            if (!isset(\Yii::$app->params['z1logRended'])) {
                \Yii::$app->params['z1logRended'] = true;
                // \Yii::$app->layout = '//blank';
                \Yii::$app->layout = self::$z1layout;
            }

        }


        // var_dump($layout);exit;

        // if (!isset(\Yii::$app->params['z1logRended'])) {
        //     \Yii::$app->params['z1logRended'] = true;

        //     // $pathinfo = pathinfo($event->viewFile);
        //     // $sHtml = \Yii::$app->view->renderAjax($pathinfo['filename'],$event->params);
        


        //     // \Yii::$app->view->context->layout = '//blank';
        //     \Yii::$app->layout = '//blank';
        // }
    }

    /**
     * unset the z1logSaved session.
     * 
     * @param  array $event
     * 
     * @author myzero1
     * @since 2.0.13
     */
    public static function z1logAfterRequest($event)
    {
        unset($_SESSION['z1logSaved']);
    }

    /**
     * unset the z1logSaved session.
     * 
     * @param string $model text,screenshot,all
     * @param string $screenshot 'z1user/user2/update' The template router
     * @param array $screenshotParams ['id'=>21]
     * @param string $text z1user/user2/update
     *
     * @return viod 
     * 
     * @author myzero1
     * @since 2.0.13
     */
    // public static function add($z1LogParams, $requestParams){
    public static function add($model, $screenshot, $screenshotParams, $text){
        $screenshot = '';
        $textContent = '';
        
        if (in_array($model, ['all','screenshot']) ) {
            $params = \Yii::$app->request->get();
            $params = array_merge($params, $screenshotParams);

            $sHtml = \Yii::$app->runAction('/' . $screenshot, $params);

            $sHtmlCom = $sHtml;
            $sHtmlCom = ltrim(rtrim(preg_replace(array("/> *([^ ]*) *</","//","'/\*[^*]*\*/'","/\r\n/","/\n/","/\t/",'/>[ ]+</'),array(">\\1<",'','','','','','><'),$sHtml)));
            $sHtmlCom = str_replace('href="', 'hrefDisabled="', $sHtmlCom);
            $sHtmlCom = str_replace('action="', 'actionDisabled="', $sHtmlCom);
            $sHtmlCom = str_replace('type="submit"', 'typeDisabled="submit"', $sHtmlCom);

            $screenshot = $sHtmlCom;
        }

        if (in_array($model, ['all','text']) ) {
            $textContent = $text['text']();
        }

        $sql = sprintf("INSERT INTO `z1log_log` (
                    `id`,
                    `user_id`,
                    `user_name`,
                    `ip`,
                    `created`,
                    `url`,
                    `text`,
                    `screenshot`
                )
                VALUES
                    (NULL, %d, '%s', '%s', %d, '%s', '%s', '%s')",
                    \Yii::$app->params['z1log']['params']['userInfo']['id'](),
                    \Yii::$app->params['z1log']['params']['userInfo']['name'](),
                    \Yii::$app->request->userIP,
                    time(),
                    $z1LogParams['screenshot'],
                    $textContent,
                    base64_encode($screenshot));

        \Yii::$app->db->createCommand($sql)->execute();
    }
}