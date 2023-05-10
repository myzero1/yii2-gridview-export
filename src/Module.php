<?php

namespace myzero1\gdexport;

/**
 * test module definition class
 */
class Module extends \yii\base\Module
{
    public $streamMode = 'gc'; // gc,curl

    /**
     * {@inheritdoc}
     */
    // public $controllerNamespace = 'backend\modules\controllers';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        // custom initialization code goes here
    }
}
