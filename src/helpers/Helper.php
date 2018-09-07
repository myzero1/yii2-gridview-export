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
     * 
     * @return  string
     **/

    public static function createExportForm($dataProvider, array $columns, $name, array $buttonOpts = ['class' => 'btn btn-info'], array $url=['/gdexport/export/export','id' => 1], $writerType='Xls'){
        $sqlNew = '';
        $querySerialized = '';
        if ($dataProvider instanceof \yii\data\ActiveDataProvider) {
            $querySerialized = json_encode(serialize($dataProvider->query));
        } else {
            $sql = $dataProvider->sql;
            $sqlNew = json_encode($sql);
        }
        $columnsSerialized = self::serializeWithClosure($columns);

        $form[] = Html::beginForm(
            $url, 
            'post', 
            [
                'id' => 'gdexport',
                'style' => 'display: inline-block;',
            ]
        );
        $form[] = Html::hiddenInput('export_name', $name);
        $form[] = Html::hiddenInput('export_sql', $sqlNew);
        $form[] = Html::hiddenInput('export_query', $querySerialized);
        $form[] = Html::hiddenInput('export_columns', $columnsSerialized);
        $form[] = Html::hiddenInput('export_type', $writerType);
        $form[] = Html::submitButton('导出',$buttonOpts);
        $form[] = Html::endForm();

        return implode('', $form);
    }

    public static function exportSend($columns, $exportQuery='', $exportSql='', $exportName='exportName', $writerType = 'Xls'){

        if (!empty($exportQuery)) {
            $query = unserialize(json_decode($exportQuery));
            $dataProvider = new \yii\data\ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => 999999999,
                ],
            ]);
        } else if (!empty($exportSql)) {
            $sql = json_decode($exportSql, true);
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

        $columns = \myzero1\gdexport\helpers\Helper::unserializeWithClosure($columns);

        // var_dump($columns);exit;

        $exporter = new \yii2tech\spreadsheet\Spreadsheet([
            'dataProvider' => $dataProvider,
            'columns' => $columns,
        ]);

        $exporter->writerType = $writerType;
        $exporter->send(sprintf('%s-%s.xls', $exportName, date('Y-m-d H:i:s')));
    }
}