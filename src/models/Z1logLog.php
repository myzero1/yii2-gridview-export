<?php

namespace myzero1\log\models;

use Yii;

/**
 * This is the model class for table "z1log_log".
 *
 * @property int $id
 * @property int $user_id 操作人id
 * @property string $user_name 操作人的账号名称
 * @property string $ip 操作人的登录ip
 * @property int $created 日志创建时间
 * @property string $url 访问地址的相对url
 * @property string $text 文本日志
 * @property string $screenshot 截图日志
 */
class Z1logLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'z1log_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'user_name', 'url', 'text', 'screenshot'], 'required'],
            [['user_id', 'created'], 'integer'],
            [['text', 'screenshot'], 'string'],
            [['user_name', 'ip', 'url'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => '操作人Id',
            'user_name' => '操作人名称',
            'ip' => '操作IP',
            'created' => '操作时间',
            'url' => '操作路由',
            'text' => '文本日志',
            'screenshot' => '截图日志',
        ];
    }
}
