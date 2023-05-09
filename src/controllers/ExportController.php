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
        $post = \Yii::$app->request->post();
        return \myzero1\gdexport\helpers\Helper::exportStream(
            $post['export_columns'], 
            $post['export_query'], 
            $post['export_sql'], 
            $post['export_name'], 
            $post['export_timeout']
        );
    }

    public function actionExportStreamCurl()
    {
        \Yii::$app->session->close(); // 必须添加，否则使用curl在export中访问data时会卡死
        $cookie=$_COOKIE;
        $cookie = http_build_query($_COOKIE);
        $cookie = str_replace(['&', '='], ['; ', '='], $cookie);  // 替换后的cookie查是正确的
        $url=\yii\helpers\Url::to($this->module->id.'/export/export-stream-curl-data',true);
        $timeout=5;
        $post_data = \Yii::$app->request->post();
        $page=0;
        
        $flag = true;
        $ch = curl_init(); 
        while ($flag) { 
            // $ch = curl_init();
            // curl_setopt($ch, CURLOPT_URL, $url);
            // // curl_setopt($ch, CURLOPT_HEADER, 1);
            // curl_setopt($ch, CURLOPT_HEADER, 0);
            // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            // curl_setopt($ch, CURLOPT_COOKIE, $cookie);
            // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            // curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            // curl_setopt($ch,CURLOPT_USERAGENT,"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36");
            
            $post_data['page']=$page;
            $post_string = http_build_query($post_data, '', '&');
            $page=$page+1;

               // 启动一个CURL会话
            curl_setopt($ch, CURLOPT_URL, $url);     // 要访问的地址
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // 对认证证书来源的检查   // https请求 不验证证书和hosts
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
            curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
            //curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
            //curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
            curl_setopt($ch, CURLOPT_POST, true); // 发送一个常规的Post请求
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);     // Post提交的数据包
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);     // 设置超时限制防止死循环
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            //curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);     // 获取的信息以文件流的形式返回 
            // curl_setopt($ch, CURLOPT_HTTPHEADER, $header); //模拟的header头
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
            
            $content = curl_exec($ch);

            // var_dump($content);exit;
            

            echo '--------';
            if ($content=='') {
                // $flag=false;
                // echo '-----1---';
            } else {
                echo $content;
                // if ($page>2) {
                //     $flag=false;
                // }
            }

            if ($page>10) {
                    $flag=false;
            }

            var_dump('==========',$flag,$page);


        }

        curl_close($ch);


        // $post = \Yii::$app->request->post();
        // return \myzero1\gdexport\helpers\Helper::exportStream(
        //     $post['export_columns'], 
        //     $post['export_query'], 
        //     $post['export_sql'], 
        //     $post['export_name'], 
        //     $post['export_timeout']
        // );

        exit;
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
