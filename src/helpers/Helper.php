<?php

namespace myzero1\gdexport\helpers;

use yii\helpers\Html;
use SuperClosure\Serializer;
use \myzero1\gdexport\csvgrid\CsvGrid;
use \PhpZip\ZipFile;

/**
 * The helpers for yii2-gridview-export.
 *
 * @author qinxuanwu
 *
 */
class Helper {
    public static function createExportForm(
        $dataProvider, 
        array $columns, 
        $name, 
        array $buttonOpts = ['class' => 'btn btn-info'], 
        array $url=['/gdexport/export/export','id' => 1], 
        $writerType='Xls', 
        $buttonLable='导出', 
        $timeout=600,
        $confirmMsg=''
    ){
        $sqlNew = '';
        $querySerialized = '';
        if ($dataProvider instanceof \yii\data\ActiveDataProvider) {
            $querySerialized = json_encode(serialize($dataProvider->query));
        } else {
            $sql = $dataProvider->sql;
            $sqlNew = json_encode($sql);
        }
        $columnsSerialized = self::serializeWithClosure($columns);

        $id = sprintf('gdexport-%s', $name);
        $id = base64_encode($id);
        $id = str_replace('+','',$id);
        $id = str_replace('=','',$id);

        $form[] = Html::beginForm(
            $url,
            'post',
            [
                'form-id' => $id,
                'style' => 'display: inline-block;',
            ]
        );
        $form[] = Html::hiddenInput('export_name', base64_encode($name));
        $form[] = Html::hiddenInput('export_type', base64_encode($writerType));
        $form[] = Html::hiddenInput('export_sql', base64_encode($sqlNew));
        $form[] = Html::hiddenInput('export_query', base64_encode($querySerialized));
        $form[] = Html::hiddenInput('export_columns', base64_encode($columnsSerialized));
        $form[] = Html::hiddenInput('export_timeout', base64_encode($timeout));
        // $form[] = Html::submitButton('导出',$buttonOpts);
        $form[] = Html::endForm();
        $formStr = implode('', $form);
        // return $formStr;
        $formStr = str_replace("\n", '', $formStr);

        $js = "
            $('html').append('$formStr');
            $('#$id').click(function(){
                if ('$confirmMsg'!=''){
                    if (confirm('$confirmMsg')==true){
                        $('form[form-id=\"$id\"]').submit();
                    }
                } else {
                    $('form[form-id=\"$id\"]').submit();
                }
            });
        ";

        \Yii::$app->view->registerJs($js);

        $buttonOpts['class'] = $buttonOpts['class'] . ' gdexport';
        $buttonOpts['id'] = $id;
        return Html::tag('div', $buttonLable, $buttonOpts);

    }

    public static function exportSend($columns, $exportQuery='', $exportSql='', $exportName='exportName', $writerType = 'Xls', $timeout = 600){
        return \myzero1\gdexport\helpers\Helper::exportFile(
            $columns, 
            $exportQuery, 
            $exportSql, 
            $exportName, 
            $timeout
        );
    }

    public static function exportBigSend($columns, $exportQuery='', $exportSql='', $exportName='exportName', $writerType = 'xlsx', $timeout = 600){
        return \myzero1\gdexport\helpers\Helper::exportStream(
            $columns, 
            $exportQuery, 
            $exportSql, 
            $exportName, 
            $timeout
        );
    }

    public static function exportFile($columns='', $exportQuery='', $exportSql='', $exportName='exportName', $timeout=600, $pw='', $filePath=''){
        if ($exportQuery!='') {
            $query = unserialize(json_decode(base64_decode($exportQuery)));
            $dataProvider = new \yii\data\ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => 1000, // export batch size
                ],
            ]);
        } else if ($exportSql!='') {
            $sql = json_decode(base64_decode($exportSql), true);
            $dataProvider = new \yii\data\SqlDataProvider([
                'sql' => $sql,
                'pagination' => [
                    'pageSize' => 1000, // export batch size
                ],
            ]);
        }
        $GridCnf=[
            'dataProvider' => $dataProvider,
            'maxEntriesPerFile' => 60000 + 1, // limit max rows per single file
            'resultConfig' => [
                'forceArchive' => true // always archive the results
            ],
        ];

        if ($exportName != 'exportName') {
            $exportName = base64_decode($exportName);
        }
        $fileName = sprintf('%s-%s.zip', $exportName, date('YmdHis'));

        if ($timeout != 600) {
            $timeout = base64_decode($timeout);
        }
        \Yii::$app->session->close();
        set_time_limit($timeout);
        
        if ($columns != '') {
            $columns = \myzero1\gdexport\helpers\Helper::unserializeWithClosure(base64_decode($columns));
        }
        $GridCnf['columns']=$columns;

        if ($pw!='') {
            \Yii::$app->params['yii2-gridview-export_pw']=$pw;
            $GridCnf['resultConfig']['archiver']=function (array $files, $dirName) {
                $archiveFileName = $dirName . DIRECTORY_SEPARATOR . 'data' . '.zip';

                $zipFile = new ZipFile();
                $zipFile
                    ->addDir($dirName)
                    ->setPassword(\Yii::$app->params['yii2-gridview-export_pw'])
                    ->saveAsFile($archiveFileName)
                    ->close();

                $zipFile->close();
                return $archiveFileName;
            };
        }

        $exporter = new CsvGrid($GridCnf);
        if ($filePath) {
            $archiveFileName = $filePath.DIRECTORY_SEPARATOR.$fileName;
            // $archiveFileName = $filePath.DIRECTORY_SEPARATOR.'t1.zip';
            $exporter->export()->saveAs($archiveFileName);
            $url=sprintf('/%s/%s',$filePath,$fileName);
            return $url;
        } else {
            $exporter->export()->send($fileName);
        }
    }

    public static function exportStream($columns='', $exportQuery='', $exportSql='', $exportName='exportName', $timeout=600, $pw='', $filePath=''){
        if ($exportQuery!='') {
            $query = unserialize(json_decode(base64_decode($exportQuery)));
            $dataProvider = new \yii\data\ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => 1000, // export batch size
                ],
            ]);
        } else if ($exportSql!='') {
            $sql = json_decode(base64_decode($exportSql), true);
            $dataProvider = new \yii\data\SqlDataProvider([
                'sql' => $sql,
                'pagination' => [
                    'pageSize' => 1000, // export batch size
                ],
            ]);
        }
        $GridCnf=[
            'dataProvider' => $dataProvider,
        ];

        if ($exportName != 'exportName') {
            $exportName = base64_decode($exportName);
        }

        if ($timeout != 600) {
            $timeout = base64_decode($timeout);
        }
        \Yii::$app->session->close();
        set_time_limit($timeout);
        
        if ($columns != '') {
            $columns = \myzero1\gdexport\helpers\Helper::unserializeWithClosure(base64_decode($columns));
        }
        $GridCnf['columns']=$columns;

        $exporter = new CsvGrid($GridCnf);
        $exporter->exportStream($exportName);
    }

    public static function exportStreamCurlWrap($post_data,$url){

        // \myzero1\gdexport\csvgrid\CsvGrid::addStreamHeader('test');

        $cookie=$_COOKIE;
        $cookie = http_build_query($_COOKIE);
        $cookie = str_replace(['&', '='], ['; ', '='], $cookie);  // 替换后的cookie查是正确的
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

               // 启动一个CURL会话
            curl_setopt($ch, CURLOPT_URL, $url);     // 要访问的地址
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // 对认证证书来源的检查   // https请求 不验证证书和hosts
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
            curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
            //curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
            //curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
            curl_setopt($ch, CURLOPT_POST, true); // 发送一个常规的Post请求
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);     // Post提交的数据包
            // curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);     // 设置超时限制防止死循环
            // curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            // curl_setopt($ch, CURLOPT_TIMEOUT, 1); // 尝试建立链接的超时时间
            // curl_setopt($ch, CURLOPT_TIMEOUT_MS, 1000*1); // 尝试建立链接的超时时间,这里是访问本地地址网络很快，可以设置小一些
            // curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 1000*15);     // 链接保持的最长时间,设置超时限制防止死循环
            curl_setopt($ch, CURLOPT_TIMEOUT, self::curlTimeOut()); // 尝试建立链接的超时时间,这里是访问本地地址网络很快，可以设置小一些
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::curlTimeOut()*10);     // 链接保持的最长时间,设置超时限制防止死循环
            //curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);     // 获取的信息以文件流的形式返回 
            // curl_setopt($ch, CURLOPT_HTTPHEADER, $header); //模拟的header头
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
            
            $content = curl_exec($ch);

            // var_dump($content);exit;

            $curlErr = curl_error($ch);
            
            if ($curlErr=='') {
                echo $content;
                $page=$page+1;

                if ($content=='') {
                    $flag=false;
                }
            } else {
                if (strpos($curlErr,'timed out') !== false) {
                    if ($page==0) {
                        self::curlTimeOut(1);
                    }
                } else {
                    echo $curlErr;
                    exit;
                }
            }

            if ($page>4) {
                    $flag=false;
            }

            // var_dump('==========', memory_get_usage(),$flag,$page,time(),curl_error($ch),self::curlTimeOut());
            var_dump('==========', $page,self::curlTimeOut(),$curlErr);


        }

        curl_close($ch);

        exit;
    }

    public static function exportStreamCurl($columns='', $exportQuery='', $exportSql='', $exportName='exportName', $timeout=600, $pw='', $filePath='',$page){
        if ($exportQuery!='') {
            $query = unserialize(json_decode(base64_decode($exportQuery)));
            $dataProvider = new \yii\data\ActiveDataProvider([
                'query' => $query,
                // 'pagination' => [
                //     'pageSize' => 1000, // export batch size
                // ],
            ]);
        } else if ($exportSql!='') {
            $sql = json_decode(base64_decode($exportSql), true);
            $dataProvider = new \yii\data\SqlDataProvider([
                'sql' => $sql,
                // 'pagination' => [
                //     'pageSize' => 1000, // export batch size
                // ],
            ]);
        }

        // var_dump($dataProvider->query->createCommand()->getRawSql());exit;

        $GridCnf=[
            'dataProvider' => $dataProvider,
        ];

        if ($exportName != 'exportName') {
            $exportName = base64_decode($exportName);
        }

        if ($timeout != 600) {
            $timeout = base64_decode($timeout);
        }
        \Yii::$app->session->close();
        set_time_limit($timeout);
        
        if ($columns != '') {
            $columns = \myzero1\gdexport\helpers\Helper::unserializeWithClosure(base64_decode($columns));
        }
        $GridCnf['columns']=$columns;

        $exporter = new CsvGrid($GridCnf);
        $exporter->exportStreamCurl($exportName,$page);
    }

    public static function remoteArrayDataProvider(
        $url, 
        $params,
        $timeout=600,
        $itemsKeys=['data','items'],
        $totalKeys=['data','total'],
        $pageSizeKeys=['data','page_size'],
        $dataProviderKey='',
        $extendDataKeys=['data','total_amount']
    ){
        $ret = self::HttpCurl($url, $params, 'get',$timeout);
        if ($ret['code'] == 200) {
            $data = json_decode($ret['data'], true);

            $tmp=$data;
            foreach ($itemsKeys as $v) {
                if (isset($tmp[$v])) {
                    $tmp=$tmp[$v];
                }else{
                    $tmp;
                }
            }
            $items=$tmp;

            $tmp=$data;
            foreach ($totalKeys as $v) {
                if (isset($tmp[$v])) {
                    $tmp=$tmp[$v];
                }else{
                    $tmp;
                }
            }
            $total=$tmp;

            $tmp=$data;
            foreach ($pageSizeKeys as $v) {
                if (isset($tmp[$v])) {
                    $tmp=$tmp[$v];
                }else{
                    $tmp;
                }
            }
            $size=$tmp;

            $tmp=$data;
            foreach ($extendDataKeys as $v) {
                if (isset($tmp[$v])) {
                    $tmp=$tmp[$v];
                }else{
                    $tmp;
                }
            }
            $extendData=$tmp;

            $dataProviderCnf=[
                'allModels' => $items,
                'totalCount' => $total,
                'pagination' => [
                    'pageSize' => $size,
                ],
            ];
            if ($dataProviderKey!='') {
                $dataProviderCnf['key']=$dataProviderKey;
            }

            return [
                'dataProvider'=>new \myzero1\gdexport\helpers\RemoteArrayDataProvider($dataProviderCnf),
                'extendData'=>$extendData,
            ];
        }
    }

    public static  function HttpCurl($url, $param, $method = "get", $timeout = 30)
    {
        try {
            if (strtolower($method) == 'get') {
                $url = $url . '?' . http_build_query($param);
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            if (strtolower($method) == 'post') {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
            }

            $result = curl_exec($ch);

            $rsp = [
                'code' => 200,
                'msg' => 'ok',
                'data' => '',
            ];

            //获取返回状态 200为正常
            $http_status = curl_getinfo($ch);
            if (isset($http_status['http_code'])) {
                if ($http_status['http_code'] == "200") {
                    $rsp['data'] = $result;
                } else {
                    $rsp['code'] = $http_status['http_code'];
                    $rsp['msg'] = sprintf('http访问错误:%s', $http_status['http_code']);
                    $rsp['data'] = curl_error($ch);
                }
            } else {
                $rsp['code'] = 500;
                $rsp['msg'] = 'curl执行错误';
                $rsp['data'] = curl_error($ch);
            }
        } catch (\Exception $e) {
            $rsp['code'] = 500;
            $rsp['msg'] = $e->getMessage();
        }

        return $rsp;
    }

    public static function columsFilter(array $colums){
        $rm = ['yii\grid\CheckboxColumn', 'yii\grid\ActionColumn', 'yii\grid\SerialColumn'];

        foreach ($colums as $k1 => $v1) {
            if (is_array($v1)) {
                if (array_key_exists("class", $v1)){
                    if (in_array($v1["class"], $rm)) {
                        unset($colums[$k1]);
                    }
                }
            }
        }

        return $colums;
    }

    public static function serializeWithClosure(array $source){
        $serializer = new Serializer();
        $source = self::columsFilter($source);
        foreach ($source as $k1 => $v1) {
            if (is_array($v1)) {
                foreach ($v1 as $k2 => $v2) {
                   if ($v2 instanceof \Closure) {
                        $source[$k1][$k2] = $serializer->serialize($v2);
                    }
                }
            }
        }

        return json_encode($source);
    }

    public static function unserializeWithClosure($source){
        $serializer = new Serializer();

        $source = json_decode($source, true);

        foreach ($source as $k1 => $v1) {
            if (is_array($v1)) {
                foreach ($v1 as $k2 => $v2) {
                    if (!is_array($v2)) {
                        if(strpos($v2,'SuperClosure\SerializableClosure') !== false){
                            $source[$k1][$k2] = $serializer->unserialize($v2);
                        }
                    }
                }
            }
        }

        return $source;
    }

    public static function noScientificNotation($value){
        $value=$value . '';
        $pattern='/\d{9,}/';
        if (preg_match($pattern, $value)){
            $value=self::force2str($value);
        }

        return $value;
    }

    public static function force2str($value){
        // 中文空格占位符
        return $value=$value.' ';
    }

    public static function curlTimeOut($inc=0){
        if (!isset(\Yii::$app->params['CURLOPT_TIMEOUT'])) {
            \Yii::$app->params['CURLOPT_TIMEOUT']=1;
        } else {
            \Yii::$app->params['CURLOPT_TIMEOUT'] = \Yii::$app->params['CURLOPT_TIMEOUT']+$inc;
        }

        if (\Yii::$app->params['CURLOPT_TIMEOUT'] > 15) {
            \Yii::$app->params['CURLOPT_TIMEOUT'] = 15;
        }

        return \Yii::$app->params['CURLOPT_TIMEOUT'];
    }
}