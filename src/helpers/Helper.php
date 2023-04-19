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
    /**
     * 调用实例：Helper::
     *
     * @param   string  dataProvider
     * @param   array   columns
     * @param   string  name
     * @param   array   buttonOpts    ['class' => 'btn btn-info']
     * @param   array   url     ['/gdexport/export/export2','id' => 1]
     * @param   string  writerType    Xls,Xlsx,Ods,Csv,Html,Tcpdf,Dompdf,Mpdf
     * @param   string  buttonLable
     *
     * @return  string
     **/

    public static function createExportForm($dataProvider, array $columns, $name, array $buttonOpts = ['class' => 'btn btn-info'], array $url=['/gdexport/export/export','id' => 1], $writerType='Xls', $buttonLable='导出', $timeout=600){
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
                $('form[form-id=\"$id\"]').submit();
            });
        ";

        \Yii::$app->view->registerJs($js);

        $buttonOpts['class'] = $buttonOpts['class'] . ' gdexport';
        $buttonOpts['id'] = $id;
        return Html::tag('div', $buttonLable, $buttonOpts);

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
        $fileName = sprintf('%s-%s.zip', $exportName, date('Y-m-d_H-i-s'));

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
        // $GridCnf['columns']=$columns;

        $exporter = new CsvGrid($GridCnf);
        $exporter->exportStream($exportName);
    }

    public static function exportBigSend($columns, $exportQuery='', $exportSql='', $exportName='exportName', $writerType = 'xlsx', $timeout = 600){
        if ('Content-type' != '') {
            if ($exportName != 'exportName') {
                $exportName = base64_decode($exportName);
            }
            if ($writerType != 'xlsx') {
                $writerType = base64_decode($writerType);
            }
            if ($timeout != 600) {
                $timeout = base64_decode($timeout);
            }

            $writerType='xlsx';

            \Yii::$app->session->close();
            set_time_limit($timeout);
            $filename = sprintf('%s_%s.%s',$exportName, date('YmdHis'),$writerType);
    
            // https://www.cnblogs.com/jzxy/articles/16779621.html
            header("Content-type:application/octet-stream");
            header("Accept-Ranges:bytes");
            header("Content-type:application/vnd.ms-excel,charset=UTF8-Bom");
            header("Content-Disposition:attachment;filename=" . $filename);
            header("Pragma: no-cache");
            header("Expires: 0");
        }
        
        $values=[];
        if ('headers'!='') {
            $columns = \myzero1\gdexport\helpers\Helper::unserializeWithClosure(base64_decode($columns));
    
            $headers=[];
            foreach ($columns as $k => $v) {
                if (is_string($v)) {
                    $headers[] = $v;
                    $values[] = $v;
                } else if (is_array($v)) {
                    if (isset($v['header'])) {
                        $headers[] = $v['header'];
                    } else {
                        $headers[] = isset($v['attribute'])?$v['attribute']:'';
                    }
    
                    if (isset($v['value'])) {
                        $values[] = $v['value'];
                    } else if (isset($v['attribute'])) {
                        $values[] = $v['attribute'];
                    } else {
                        $values[] = '';
                    }
                }
            }

            echo implode("\t",$headers)."\n";
        }

        if ('rows'!='') {
            $pageSize = 1000;
            // $pageSize = 3;
            $page = 0;
            $sql='';

            if (!empty($exportQuery)) {
                $query = unserialize(json_decode(base64_decode($exportQuery)));
                $sql=$query->createCommand()->getRawSql();
            } else if (!empty($exportSql)) {
                $sql = json_decode(base64_decode($exportSql), true);
            }

            if ($sql=='') {
                throw new \Exception('sql is empty');
            }

            $go=true;
            while ($go) {
                $sqlTmp=sprintf(
                    '%s limit %d,%d',
                    $sql,
                    $page*$pageSize,
                    $pageSize+1
                );

                $go=false;
                $page=$page+1;

                $all=\Yii::$app->db->createCommand($sqlTmp)->queryAll();
                foreach ($all as $k => $r) {
                    if ($k>=$pageSize) {
                        $go=true;
                        break;
                    }

                    $tmp=[];
                    foreach ($values as $k => $v) {
                        $t='';
                        if(is_callable($v)){
                            $t = $v($r);
                        }else{
                            $t = $r[$v];
                        }

                        $t=self::noScientificNotation($t);

                        $tmp[] = $t;
                    }
                    echo implode("\t",$tmp)."\n";
                }
            }
        }
        exit();
    }

    public static function remoteArrayDataProvider(
        $url, 
        $params,
        $timeout=600,
        $itemsKeys=['data','items'],
        $totalKeys=['data','total'],
        $pageSizeKeys=['data','page_size']
        // $dataProviderKey='id'
    ){
        $ret = self::HttpCurl($url, $params, 'get',$timeout);
        if ($ret['code'] == 200) {
            $data = json_decode($ret['data'], true);

            $tmp=$data;
            foreach ($itemsKeys as $v) {
                $tmp=$tmp[$v];
            }
            $items=$tmp;

            $tmp=$data;
            foreach ($totalKeys as $v) {
                $tmp=$tmp[$v];
            }
            $total=$tmp;

            $tmp=$data;
            foreach ($pageSizeKeys as $v) {
                $tmp=$tmp[$v];
            }
            $size=$tmp;

            return new \myzero1\gdexport\helpers\RemoteArrayDataProvider([
                'allModels' => $items,
                'totalCount' => $total,
                'pagination' => [
                    'pageSize' => $size,
                ],
                // 'key' => $dataProviderKey,
            ]);
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


        /**
     * Get the module's name of rbacp.
     *
     * 调用实例：Helper::
     *
     * @param   void
     * @return  string
     **/
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

    // -------------
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
        $pattern='/^\d{9,}$/';
        if (preg_match($pattern, $value)){
            $value=self::force2str($value);
        }

        return $value;
    }

    public static function force2str($value){
        // 中文空格占位符
        return $value=$value.' ';
    }
}