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
php composer.phar require myzero1/yii2-gridview-export：*
```

or add

```
"myzero1/yii2-gridview-export": "*"
```

to the require section of your `composer.json` file.



Setting
-----

Once the extension is installed, simply modify your application configuration as follows:

in main.php
```php
return [
    ......
    'bootstrap' => [
        ......
        // function(){\myzero1\gdexport\helpers\Helper::rewriteClass2GC();}, // If you want to set myzero1_gdexport_streamMode to rewrite_class, you need to add this line.
        ......
    ],
    'modules' => [
        ......
        // 'gdexport' => ['class' => 'myzero1\gdexport\Module',], // If using /gdexport/export/export as the export address, you need to add this line.
        ......
    ],
    ......
];
```

in params.php
```php
return [
    ......
    // 'myzero1_gdexport_streamMode' => 'rewrite_class', //gc,rewrite_class,curl,default is gc,to solve the memory leak of yii2 AR recorder 
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

// 导出文件zip
// <?= \myzero1\gdexport\helpers\Helper::createExportForm($dataProvider, $columns, $name='导出文件名', $buttonOpts = ['class' => 'btn btn-info'], $url=['/gdexport/export/export-file','id' => 1], $writerType='Xls', $buttonLable='导出大量数据', $timeout = 600);?>

// 导出文件zip并加密
// <?= \myzero1\gdexport\helpers\Helper::createExportForm($dataProvider, $columns, $name='导出文件名', $buttonOpts = ['class' => 'btn btn-info'], $url=['/gdexport/export/export-file-pw','id' => 1], $writerType='Xls', $buttonLable='导出大量数据', $timeout = 600);?>

// 用数据流导出
// <?= \myzero1\gdexport\helpers\Helper::createExportForm($dataProvider, $columns, $name='导出文件名', $buttonOpts = ['class' => 'btn btn-info'], $url=['/gdexport/export/export-stream','id' => 1], $writerType='Xls', $buttonLable='导出大量数据', $timeout = 600);?>

// 用数据流导出，添加确认框
// <?= \myzero1\gdexport\helpers\Helper::createExportForm($dataProvider, $columns, $name='导出文件名', $buttonOpts = ['class' => 'btn btn-info'], $url=['/gdexport/export/export-stream','id' => 1], $writerType='Xls', $buttonLable='导出大量数据', $timeout = 600, $confirmMsg = "请问你确认导出数据吗？");?>

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
    $pageSizeKeys=['data','page_size'],
    $dataProviderKey='',
    $extendDataKeys=['data','total_amount']
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

### myzero1_gdexport_streamMode 各个种参数解决yii2 AR recorder内存泄漏的优缺点

|可选值|优点|缺点|注意|
|---|---|---|---|
|gc|使用gc_collect_cycles()回收内存，完全不影响现有逻辑|在某一些环境下gc_collect_cycles()不能回收内存，如php的一些docker环境，7.2-fpm-alpine，7.3-fpm-alpine ...|默认值|
|rewrite_class|重写vendor\yiisoft\yii2\base\Component.php，对业务处理逻辑没有影响|若在调用重写函数（rewriteClass2GC）之前已经使用了yii\base\Model或yii\db\Query 会导致重写失败,如\common\models\User::find()|若默认参数不能解决问题建议使用这个参数|
|curl|对原有代码没有任何侵入，通过curl分批次导出数据，每个curl请求完成自动回收内存，来达到防止内存泄漏的目的|CPU消耗大，只有actionExportStream实现了这个功能|cpu资源小的，不建议使用|



`*****注意`
```
1   php 导出的时候一定要设置  ini_set('memory_limit',-1); 否则很容易就出现内存不足，而且 gc_collect_cycles() 回收不起作用。

2   docker 容器中运行 php-fpm 一定要设置资源现在特别是内存的限制，否则会把资源耗尽

3   docker-composer 中配置资源限制，由于有资源限制, 且没有使用swarm, 所以要加上--compatibility参数, 否则报错或者限制不生效    docker-compose --compatibility up -d ，实例如下
version: '3.7'
services:
  openldap:
    image: 10.10.xxx.54/public/openldap:1.3.0
    container_name: openldap
    environment:
      - N9E_NID=22
    ports:
      - "389:389"
      - "636:636"
    deploy:
      resources:
         limits:
            cpus: "2.00"
            memory: 5G
         reservations:
            memory: 200M
    volumes:
      - ./ldap:/var/lib/ldap
      - ./slapd.d:/etc/ldap/slapd.d
    restart: always

4   https://docs.docker.com/compose/compose-file/deploy/#resources       https://www.codetd.com/article/14276922

5   从2.2.3开始，只要把docker的内存限制一下，用默认配置，基本上就可以了满足导出需求。


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

### release log
```
2023/05/10 14:50        2.1.0   add curl to exportStream 
2023/05/13 17:17        2.2.0   add rewrite_class to export 

```