<?php

namespace myzero1\gdexport\helpers;

use SuperClosure\Serializer;
use yii\helpers\Html;

/**
 * The helpers for rbacp.
 *
 * @author qinxuanwu
 *
 */
class Helper {
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

    /**
     * Get the module's name of rbacp.
     *
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

        $js = <<<JS
            $("html").append('$formStr');
            $('#$id').click(function(){
                console.log($('form[form-id="$id"]'));
                $('form[form-id="$id"]').submit();
            });
JS;

        \Yii::$app->view->registerJs($js);

        $buttonOpts['class'] = $buttonOpts['class'] . ' gdexport';
        $buttonOpts['id'] = $id;
        return Html::tag('div', $buttonLable, $buttonOpts);

    }

    public static function exportSend($columns, $exportQuery='', $exportSql='', $exportName='exportName', $writerType = 'xlsx', $timeout = 600){
        if ($exportName != 'exportName') {
            $exportName = base64_decode($exportName);
        }
        if ($writerType != 'Xls') {
            $writerType = base64_decode($writerType);
        }
        if ($timeout != 600) {
            $timeout = base64_decode($timeout);
        }

        \Yii::$app->session->close();
        set_time_limit($timeout);

        if (!empty($exportQuery)) {
            $query = unserialize(json_decode(base64_decode($exportQuery)));
            $dataProvider = new \yii\data\ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => 999999999,
                ],
            ]);
        } else if (!empty($exportSql)) {
            $sql = json_decode(base64_decode($exportSql), true);
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

        $columns = \myzero1\gdexport\helpers\Helper::unserializeWithClosure(base64_decode($columns));

        $exporter = new \yii2tech\spreadsheet\Spreadsheet([
            'dataProvider' => $dataProvider,
            'columns' => $columns,
        ]);

        $exporter->writerType = $writerType;
        $exporter->send(sprintf('%s-%s.xls', $exportName, date('Y-m-d H:i:s')));
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
    }

    public static function noScientificNotation($value){
        // $value=12345678;
        // $value=123456789011;
        $value=$value . '';
        $pattern='/^\d{9,}$/';
        if (preg_match($pattern, $value)){
            // 中文空格占位符
            $value=$value.' ';
        }

        return $value;
    }
}