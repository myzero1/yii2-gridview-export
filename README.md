yii2-gridview-export
========================

The export module,export gridview data to excel.use the dataProvider and columns of the gridview.

Show time
------------

![](https://github.com/myzero1/show-time/blob/master/yii2-gridview-export/screenshot/1.png)

Installation
------------

The preferred way to install this module is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require myzero1/yii2-gridview-export：1.*
```

or add

```
"myzero1/yii2-gridview-export": "^1"
```

to the require section of your `composer.json` file.



Setting
-----

Once the extension is installed, simply modify your application configuration as follows:

```php
return [
    ......
    'modules' => [
        ......
        'gdexport' => [
            'class' => 'myzero1\gdexport\Module',
        ],
        ......
    ],
    ......
];
```

Usage
-----

### Use export widget in view
You can use it,anywhere in view as following:

```php

<?= \myzero1\gdexport\helpers\Helper::createExportForm($dataProvider, $columns, $name='导出文件名', $buttonOpts = ['class' => 'btn btn-info'], $url=['/gdexport/export/export','id' => 1], $writerType='Xls', $buttonLable='导出', $timeout = 600);?>

// 推荐使用下面这种方式,不会内存溢出
// <?= \myzero1\gdexport\helpers\Helper::createExportForm($dataProvider, $columns, $name='导出文件名', $buttonOpts = ['class' => 'btn btn-info'], $url=['/gdexport/export/big-export','id' => 1], $writerType='Xls', $buttonLable='导出大量数据', $timeout = 600);?>

```
### Use custom router
Use the custom router in ExportController.php, as following:

```php

<?php
//......
/**
 * ExportController.
 */
class ExportController extends Controller
{
    //......
    /**
     * Realtime exporter
     * @return mixed
     */
    public function actionRealtime()
    {
        $post = \Yii::$app->request->post();

        return \myzero1\gdexport\helpers\Helper::exportSend($post['export_columns'], $exportQuery=$post['export_query'], $exportSql=$post['export_sql'], $exportName=$post['export_name'], $writerType = $post['export_type'], $post['export_timeout']);

        // 推荐使用下面这种方式,不会内存溢出
        // return \myzero1\gdexport\helpers\Helper::exportSend($post['export_columns'], $exportQuery=$post['export_query'], $exportSql=$post['export_sql'], $exportName=$post['export_name'], $writerType = $post['export_type'], $post['export_timeout']);
    }

    //......
}
?>

```

Use the custom router in view, as following:

```php
<?= \myzero1\gdexport\helpers\Helper::createExportForm($dataProvider, $columns, $name='导出文件名', $buttonOpts = ['class' => 'btn btn-info'], ['/export/realtime'], $writerType='Xls', $buttonLable='导出', $timeout = 600);?>


$provider = \myzero1\gdexport\helpers\Helper::remoteArrayDataProvider(
    $url, 
    $params,
    $timeout=600,
    $itemsKeys=['data','items'],
    $totalKeys=['data','total'],
    $pageSizeKeys=['data','page_size']
);

```


### Rebuild 2.0.0

|导出方式|优点|缺点|保护|
|---|---|---|---|
|文件(exportFile)|可以压缩传输|反应慢（需要完全导出为文件后再下载）|是要zip加密压缩|
|数据流(exportStream)|反应快（边生成边下载）|下载时间长不能压缩|使用web自动到的用户验证系统|


```
基于 https://packagist.org/packages/yii2tech/csv-grid 进行加工完善

```

### Composer中~和^的含义
```
Laravel Framework 6.20.27
6 表示主版本号
20 表示次版本号
27 表示修订号

^和~的出现是为了对扩展包进行版本锁定的。它们的区别如下:
^表示锁定主版本号。
~表示锁定次版本号

我们假定这个扩展的主版本号6,中间的次版本号最大是99，末尾的修订号是999。
^6.20 表示版本的范围是6.20.0到6.99.999
~6.20 表示版本的范围是6.20.0到6.20.999

```