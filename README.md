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
"myzero1/yii2-gridview-export": "~1"
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


You can use it,anywhere as following:

```php

<?= \myzero1\gdexport\helpers\Helper::createExportForm($dataProvider, $columns, $name, $buttonOpts = ['class' => 'btn btn-info'], $url=['/gdexport/export/export','id' => 1], $writerType='Xls', $buttonLable='导出');?>

```


