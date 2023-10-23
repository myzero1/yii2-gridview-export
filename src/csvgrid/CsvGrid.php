<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace myzero1\gdexport\csvgrid;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\data\ActiveDataProvider;
use yii\di\Instance;
use yii\i18n\Formatter;

/**
 * CsvGrid allows export of data into CSV files.
 * It supports exporting of the {@see \yii\data\DataProviderInterface} and {@see \yii\db\QueryInterface} instances.
 *
 * Example:
 *
 * ```php
 * use myzero1\gdexport\csvgrid\CsvGrid;
 * use yii\data\ArrayDataProvider;
 *
 * $exporter = new CsvGrid([
 *     'dataProvider' => new ArrayDataProvider([
 *         'allModels' => [
 *             [
 *                 'name' => 'some name',
 *                 'price' => '9879',
 *             ],
 *             [
 *                 'name' => 'name 2',
 *                 'price' => '79',
 *             ],
 *         ],
 *     ]),
 *     'columns' => [
 *         [
 *             'attribute' => 'name',
 *         ],
 *         [
 *             'attribute' => 'price',
 *             'format' => 'decimal',
 *         ],
 *     ],
 * ]);
 * $exporter->export()->saveAs('/path/to/file.csv');
 * ```
 *
 * @property array|Formatter $formatter the formatter used to format model attribute values into displayable texts.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class CsvGrid extends Component
{
    /**
     * @var \yii\data\DataProviderInterface the data provider for the view.
     * This property can be omitted in case {@see query} is set.
     */
    public $dataProvider;
    /**
     * @var \yii\db\QueryInterface the data source query.
     * Note: this field will be ignored in case {@see dataProvider} is set.
     */
    public $query;
    /**
     * @var int the number of records to be fetched in each batch.
     * This property takes effect only in case of {@see query} usage.
     */
    public $batchSize = 100; // 经过测试设置为100最合适，不然资源差的机子可能会卡死，比如2C4G
    /**
     * @var array|Column[] grid column configuration. Each array element represents the configuration
     * for one particular grid column. For example:
     *
     * ```php
     * [
     *     ['class' => SerialColumn::className()],
     *     [
     *         'class' => DataColumn::className(), // this line is optional
     *         'attribute' => 'name',
     *         'format' => 'text',
     *         'header' => 'Name',
     *     ],
     * ]
     * ```
     *
     * If a column is of class {@see DataColumn}, the "class" element can be omitted.
     */
    public $columns = [];
    /**
     * @var bool whether to show the header section of the sheet.
     */
    public $showHeader = true;
    /**
     * @var bool whether to show the footer section of the sheet.
     */
    public $showFooter = false;
    /**
     * @var string the HTML display when the content of a cell is empty.
     * This property is used to render cells that have no defined content,
     * e.g. empty footer or filter cells.
     *
     * Note that this is not used by the {@see DataColumn} if a data item is `null`. In that case
     * the {@see nullDisplay} property will be used to indicate an empty data value.
     */
    public $emptyCell = '';
    /**
     * @var string the text to be displayed when formatting a `null` data value.
     */
    public $nullDisplay = '';
    /**
     * @var int the maximum entries count allowed in single file.
     * You may use this parameter to split large export results into several smaller files.
     *
     * For example: 'Open Office' and 'MS Excel 97-2003' allows maximum 65536 rows per CSV file,
     * 'MS Excel 2007' - 1048576.
     *
     * In case several files are generated during export the results will be archived into a single file.
     *
     * If this value is empty - no limit checking will be performed.
     */
    public $maxEntriesPerFile;
    /**
     * @var array configuration for {@see CsvFile} instances created in process.
     * For example:
     *
     * ```php
     * [
     *     'rowDelimiter' => "\n",
     *     'cellDelimiter' => ';',
     * ]
     * ```
     *
     * @see CsvFile
     */
    public $csvFileConfig = [];
    /**
     * @var array configuration for {@see ExportResult} instance created in process result.
     *
     * For example:
     *
     * ```php
     * [
     *     'forceArchive' => true
     * ]
     * ```
     *
     * @see ExportResult
     */
    public $resultConfig = [];

    /**
     * @var array|Formatter the formatter used to format model attribute values into displayable texts.
     * This can be either an instance of {@see Formatter} or an configuration array for creating the {@see Formatter}
     * instance. If this property is not set, the "formatter" application component will be used.
     */
    private $_formatter;
    /**
     * @var array|null internal iteration information
     */
    private $batchInfo;


    /**
     * Initializes the grid.
     * This method will initialize required property values and instantiate {@see columns} objects.
     */
    public function init()
    {
        parent::init();

        if ($this->dataProvider === null) {
            if ($this->query !== null) {
                $this->dataProvider = new ActiveDataProvider([
                    'query' => $this->query,
                    'pagination' => [
                        'pageSize' => $this->batchSize,
                    ],
                ]);
            }
        }
    }

    /**
     * @return Formatter formatter instance
     */
    public function getFormatter()
    {
        if (!is_object($this->_formatter)) {
            if ($this->_formatter === null) {
                $this->_formatter = Yii::$app->getFormatter();
            } else {
                $this->_formatter = Instance::ensure($this->_formatter, Formatter::className());
            }
        }
        return $this->_formatter;
    }

    /**
     * @param array|Formatter $formatter
     */
    public function setFormatter($formatter)
    {
        $this->_formatter = $formatter;
    }

    /**
     * Creates column objects and initializes them.
     * @param array $model list of single row model
     */
    protected function initColumns($model)
    {
        if (empty($this->columns)) {
            $this->guessColumns($model);
        }
        foreach ($this->columns as $i => $column) {
            if (is_string($column)) {
                $column = $this->createDataColumn($column);
            } else {
                $column = Yii::createObject(array_merge([
                    'class' => DataColumn::className(),
                    'grid' => $this,
                ], $column));
            }
            if (!$column->visible) {
                unset($this->columns[$i]);
                continue;
            }
            $this->columns[$i] = $column;
        }
    }

    /**
     * This function tries to guess the columns to show from the given data,
     * if {@see columns} are not explicitly specified.
     * @param array $model list of model
     */
    protected function guessColumns($model)
    {
        if (is_array($model) || is_object($model)) {
            foreach ($model as $name => $value) {
                $this->columns[] = (string) $name;
            }
        }
    }

    /**
     * Creates a {@see DataColumn} object based on a string in the format of "attribute:format:label".
     * @param string $text the column specification string
     * @return DataColumn the column instance
     * @throws InvalidConfigException if the column specification is invalid
     */
    protected function createDataColumn($text)
    {
        if (!preg_match('/^([^:]+)(:(\w*))?(:(.*))?$/', $text, $matches)) {
            throw new InvalidConfigException('The column must be specified in the format of "attribute", "attribute:format" or "attribute:format:label"');
        }

        return Yii::createObject([
            'class' => DataColumn::className(),
            'grid' => $this,
            'attribute' => $matches[1],
            'format' => isset($matches[3]) ? $matches[3] : 'raw',
            'label' => isset($matches[5]) ? $matches[5] : null,
        ]);
    }

    /**
     * Performs data export.
     * @return ExportResult export result.
     * @throws InvalidConfigException if invalid {@see resultConfig} value.
     */
    public function export()
    {
        /** @var ExportResult $result */
        $result = Yii::createObject(array_merge([
            'class' => ExportResult::className(),
        ], $this->resultConfig));

        $columnsInitialized = false;

        $maxEntriesPerFile = false;
        if (!empty($this->maxEntriesPerFile)) {
            $maxEntriesPerFile = $this->maxEntriesPerFile;
            if ($this->showFooter) {
                $maxEntriesPerFile--;
            }
        }

        $csvFile = null;
        $rowIndex = 0;
        while (($data = $this->batchModels()) !== false) {
            list($models, $keys) = $data;

            if (!$columnsInitialized) {
                $this->initColumns(reset($models));
                $columnsInitialized = true;
            }

            foreach ($models as $index => $model) {
                if (!is_object($csvFile)) {
                    $csvFile = $result->newCsvFile($this->csvFileConfig);
                    if ($this->showHeader) {
                        $csvFile->writeRow($this->composeHeaderRow());
                    }
                }

                $key = isset($keys[$index]) ? $keys[$index] : $index;
                $csvFile->writeRow($this->composeBodyRow($model, $key, $rowIndex));
                $rowIndex++;

                if ($maxEntriesPerFile !== false && $csvFile->entriesCount >= $maxEntriesPerFile) {
                    if ($this->showFooter) {
                        $csvFile->writeRow($this->composeFooterRow());
                    }
                    $csvFile->close();
                    $csvFile = null;
                }
            }

            $this->gc();
        }

        if (is_object($csvFile)) {
            if ($this->showFooter) {
                $csvFile->writeRow($this->composeFooterRow());
            }
            $csvFile->close();
        }

        if (empty($result->csvFiles)) {
            $csvFile = $result->newCsvFile($this->csvFileConfig);
            $csvFile->open();

            if ($this->showHeader) {
                $csvFile->writeRow($this->composeHeaderRow());
            }
            if ($this->showFooter) {
                $csvFile->writeRow($this->composeFooterRow());
            }

            $csvFile->close();
        }

        return $result;
    }

    /**
     * Performs data export.
     * @return ExportResult export result.
     * @throws InvalidConfigException if invalid {@see resultConfig} value.
     */
    public function exportStream($exportName)
    {
        /** @var ExportResult $result */
        $result = Yii::createObject(array_merge([
            'class' => ExportResult::className(),
        ], $this->resultConfig));

        $this->addStreamHeader($exportName);
        
        $columnsInitialized = false;
        $csvFile = null;
        $rowIndex = 0;
        while (($data = $this->batchModels()) !== false) {
            list($models, $keys) = $data;

            if (!$columnsInitialized) {
                $this->initColumns(reset($models));
                $columnsInitialized = true;
            }

            foreach ($models as $index => $model) {
                if (!is_object($csvFile)) {
                    $csvFile = $result->newCsvFile($this->csvFileConfig);
                    if ($this->showHeader) {
                        echo $csvFile->formatRow($this->composeHeaderRow());
                    }
                }

                $key = isset($keys[$index]) ? $keys[$index] : $index;
                echo $csvFile->formatRow($this->composeBodyRow($model, $key, $rowIndex));
                $rowIndex++;

                if ($this->showFooter) {
                    echo $csvFile->formatRow($this->composeFooterRow());
                }
            }

            $this->gc();
        }

        exit();
    }

    public function exportStreamArray($exportName,$start=true,$end=true)
    {
        /** @var ExportResult $result */
        $result = Yii::createObject(array_merge([
            'class' => ExportResult::className(),
        ], $this->resultConfig));

        if ($start) {
            $this->addStreamHeader($exportName);
        }
        
        $columnsInitialized = false;
        $csvFile = null;
        $rowIndex = 0;


        // if (!is_object($csvFile)) {
        //     $csvFile = $result->newCsvFile($this->csvFileConfig);
        //     echo $csvFile->formatRow($this->composeHeaderRow());
        // }
        
        while (($data = $this->batchModels()) !== false) {
            list($models, $keys) = $data;

            if (!$columnsInitialized) {
                $this->initColumns(reset($models));
                $columnsInitialized = true;
            }

            if (!is_object($csvFile)) {
                $csvFile = $result->newCsvFile($this->csvFileConfig);
                if ($this->showHeader && $start) {
                    echo $csvFile->formatRow($this->composeHeaderRow());
                }
            }
            foreach ($models as $index => $model) {
                $key = isset($keys[$index]) ? $keys[$index] : $index;
                echo $csvFile->formatRow($this->composeBodyRow($model, $key, $rowIndex));
                $rowIndex++;

                if ($this->showFooter && $end) {
                    echo $csvFile->formatRow($this->composeFooterRow());
                }
            }

            $this->gc();
        }

        if (!is_object($csvFile)) {
            var_dump(11);exit;
            $csvFile = $result->newCsvFile($this->csvFileConfig);
            echo $csvFile->formatRow($this->composeHeaderRow());
        }

        // exit();
    }

    /* 
        \myzero1\gdexport\csvgrid\CsvGrid::exportStreamRemote(
            $exportName='export-stream-remote.csv',
            $url='http://127.0.0.1:36614/v1/points/deduct-list',
            $param=['token'=>'mytoken','now_timestamp'=>'1671506537','page_size'=>'5','page'=>'1'],
            $columns=[
                [
                    'header' => 'id',
                    'attribute' => 'id',
                    'value' => function ($row) {
                        return $row['id'];
                    },
                ],
                [
                    'header' => 'c_id',
                    'attribute' => 'c_id',
                ],
            ],
            $page_param='page',
            $page_size_param='page_size',
            $total_key=['data','total'],
            $items_key=['data','items']
        ) */
    public static function exportStreamRemote(
        $exportName,
        $url,
        $param,
        $columns=[
            [
                'header' => 'id',
                'attribute' => 'id',
            ],
        ],
        $page_param='page',
        $page_size_param='page_size',
        $total_key=['data','total'],
        $items_key=['data','items']
    ) {
        if (!isset($param[$page_param])) {
            $param[$page_param]=1;
        }
        if (!isset($param[$page_size_param])) {
            $param[$page_size_param]=30;
        }

        $ret = self::HttpCurl($url, $param, 'get');
        $data = json_decode($ret['data'],true);
        // var_dump($ret);exit;
        if ($ret['code']!=200) {
            throw new \Exception($ret['msg']);
        }

        $total=self::getValBykeys($data,$total_key);
        $items=self::getValBykeys($data,$items_key);
        $page_total = ceil($total/$param[$page_size_param]);

        // var_dump($page_size_param,$page_size,$page,$total,$page_total,$items,$ret);exit;
 
        $GridCnf=[
            'dataProvider' => new \yii\data\ArrayDataProvider([
                'allModels' => $items,
            ]),
            'columns' => $columns,
        ];
        $exporter = new CsvGrid($GridCnf);

        if ($page_total==0) {
            $exporter->exportStreamArray($exportName,true,false);
        }

        for ($i=0; $i < $page_total; $i++) { 
            $i2=$i+1;
            $param[$page_param]=$i2;
            $ret = self::HttpCurl($url, $param, 'get');
            if ($ret['code']!=200) {
                throw new \Exception($ret['msg']);
            }
            // var_dump($ret);exit;
            $data = json_decode($ret['data'],true);
            $items=self::getValBykeys($data,$items_key);

            $GridCnf=[
                'dataProvider' => new \yii\data\ArrayDataProvider([
                    'allModels' => $items,
                ]),
                'columns' => $columns,
            ];
            $exporter = new CsvGrid($GridCnf);

            if ($i==0) {
                $exporter->exportStreamArray($exportName,true,false);
            } else {
                $exporter->exportStreamArray($exportName,false,false);
            }
        }

        exit;
    }

    public static function getValBykeys($data,array $keys){
        $tmp=$data;
        foreach ($keys as $k => $v) {
            $tmp=$tmp[$v];
        }

        return $tmp;
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

    public function exportStreamCurl($exportName,$page)
    {
        /** @var ExportResult $result */
        $result = Yii::createObject(array_merge([
            'class' => ExportResult::className(),
        ], $this->resultConfig));

        // var_dump(2222222222);
        // var_dump($page);
        // var_dump($this->batchModelsCurl($page));
        // exit;

        $columnsInitialized = false;
        $csvFile = null;
        $rowIndex = 0;
        if (($data = $this->batchModelsCurl($page)) !== false) {
            list($models, $keys) = $data;

            if (!$columnsInitialized) {
                $this->initColumns(reset($models));
                $columnsInitialized = true;
            }

            foreach ($models as $index => $model) {
                if (!is_object($csvFile)) {
                    $csvFile = $result->newCsvFile($this->csvFileConfig);
                    if ($this->showHeader) {
                        if ($page==0) {
                            echo $csvFile->formatRow($this->composeHeaderRow());
                        }
                    }
                }

                $key = isset($keys[$index]) ? $keys[$index] : $index;
                echo $csvFile->formatRow($this->composeBodyRow($model, $key, $rowIndex));
                $rowIndex++;

                if ($this->showFooter) {
                    echo $csvFile->formatRow($this->composeFooterRow());
                }
            }

            $this->gc();
        }

        exit();
    }

    public static function addStreamHeader($exportName)
    {
        \Yii::$app->session->close();
        $filename = sprintf('%s_%s.csv',$exportName, date('YmdHis'));

        header('Content-Encoding: UTF-8');
        header("Content-type: application/csv; charset=UTF-8");
        header('Content-Disposition: attachment; filename="'.$filename.'";');

        // // https://zhuanlan.zhihu.com/p/449095577
        // $f=fopen("php://memory",'w');
        // fwrite($f, chr(0xEF).chr(0xBB).chr(0xBF));//加入BOM头
        // fseek($f,0);
        // // make php send the generated csv lines to the browser
        // fpassthru($f);

        // https://blog.csdn.net/weixin_41635750/article/details/109821604
        //打开php标准输出流
        $fp = fopen('php://output', 'a');
        //添加BOM头，以UTF8编码导出CSV文件，如果文件头未添加BOM头，打开会出现乱码。
        fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF));
    }

    /**
     * Iterates over {@see query} or {@see dataProvider} returning data by batches.
     * @return array|false data batch: first element - models list, second model keys list.
     */
    protected function batchModels()
    {
        if ($this->batchInfo === null) {
            if ($this->query !== null && method_exists($this->query, 'batch')) {
                $this->batchInfo = [
                    'queryIterator' => $this->query->batch($this->batchSize)
                ];
            } else {
                $this->batchInfo = [
                    'pagination' => $this->dataProvider->getPagination(),
                    'page' => 0
                ];
            }
        }

        if (isset($this->batchInfo['queryIterator'])) {
            /* @var $iterator \Iterator */
            $iterator = $this->batchInfo['queryIterator'];
            $iterator->next();

            if ($iterator->valid()) {
                return [$iterator->current(), []];
            }

            $this->batchInfo = null;
            return false;
        }

        if (isset($this->batchInfo['pagination'])) {
            /* @var $pagination \yii\data\Pagination|bool */
            $pagination = $this->batchInfo['pagination'];
            $page = $this->batchInfo['page'];

            if ($pagination === false || $pagination->pageCount === 0) {
                if ($page === 0) {
                    $this->batchInfo['page']++;
                    return [
                        $this->dataProvider->getModels(),
                        $this->dataProvider->getKeys()
                    ];
                }
            } else {
                if ($page < $pagination->pageCount) {
                    $pagination->setPage($page);
                    $this->dataProvider->prepare(true);
                    $this->batchInfo['page']++;
                    return [
                        $this->dataProvider->getModels(),
                        $this->dataProvider->getKeys()
                    ];
                }
            }

            $this->batchInfo = null;
            return false;
        }

        return false;
    }

    protected function batchModelsCurl($page)
    {
        if ($this->batchInfo === null) {
            $pagination = $this->dataProvider->getPagination();
            $pagination->page=$page;
            $pagination->pageSize=$this->batchSize;
            // $pagination->page=1;
            $this->batchInfo = [
                'pagination' => $pagination,
                'page' => 0
            ];
        }

        // $this->batchInfo = [
        //     'pagination' => $this->dataProvider->getPagination(),
        //     'page' => $page,
        // ];

        if (isset($this->batchInfo['queryIterator'])) {
            /* @var $iterator \Iterator */
            $iterator = $this->batchInfo['queryIterator'];
            $iterator->next();

            if ($iterator->valid()) {
                return [$iterator->current(), []];
            }

            $this->batchInfo = null;
            return false;
        }

        if (isset($this->batchInfo['pagination'])) {
            /* @var $pagination \yii\data\Pagination|bool */
            $pagination = $this->batchInfo['pagination'];
            $page = $this->batchInfo['page'];

            if ($pagination === false || $pagination->pageCount === 0) {
                if ($page === 0) {
                    $this->batchInfo['page']++;
                    return [
                        $this->dataProvider->getModels(),
                        $this->dataProvider->getKeys()
                    ];
                }
            } else {
                if ($page < $pagination->pageCount) {
                    $pagination->setPage($page);
                    $this->dataProvider->prepare(true);
                    $this->batchInfo['page']++;
                    return [
                        $this->dataProvider->getModels(),
                        $this->dataProvider->getKeys()
                    ];
                }
            }

            $this->batchInfo = null;
            return false;
        }

        return false;
    }

    /**
     * Composes header row contents.
     * @return array cell contents.
     */
    protected function composeHeaderRow()
    {
        $cells = [];
        foreach ($this->columns as $column) {
            $cells[] = $column->renderHeaderCellContent();
        }
        return $cells;
    }

    /**
     * Composes header row contents.
     * @return array cell contents.
     */
    protected function composeFooterRow()
    {
        $cells = [];
        foreach ($this->columns as $column) {
            $cells[] = $column->renderFooterCellContent();
        }
        return $cells;
    }

    /**
     * Composes body row contents.
     * @param mixed $model the data model
     * @param mixed $key the key associated with the data model
     * @param int $index the zero-based index of the data model among the models array returned by {@see CsvGrid::$dataProvider}.
     * @return array cell contents.
     */
    protected function composeBodyRow($model, $key, $index)
    {
        $cells = [];
        foreach ($this->columns as $column) {
            $cells[] = $column->renderDataCellContent($model, $key, $index);
        }
        return $cells;
    }

    /**
     * Performs PHP memory garbage collection.
     */
    protected function gc()
    {
        if (!gc_enabled()) {
            gc_enable();
        }
        gc_collect_cycles();
    }
}
