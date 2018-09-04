<?php

use yii\db\Schema;

class m171129_120101_z1log_log extends \yii\db\Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable('z1log_log', [
            'id' => Schema::TYPE_PK,
            'user_id' => sprintf("%s(11) NOT NULL COMMENT '%s'",Schema::TYPE_INTEGER,'操作人id'),
            'user_name' => sprintf("%s(255) NOT NULL COMMENT '%s'",Schema::TYPE_STRING,'操作人的账号名称'),
            'ip' => sprintf("%s(255) COMMENT '%s'",Schema::TYPE_STRING,'操作人的登录ip'),
            'created' => sprintf("%s(11) NOT NULL DEFAULT '0' COMMENT '%s'",Schema::TYPE_INTEGER,'日志创建时间'),
            'url' => sprintf("%s(255) NOT NULL COMMENT '%s'",Schema::TYPE_STRING,'访问地址的相对url'),
            'text' => sprintf("%s NOT NULL COMMENT '%s'",Schema::TYPE_TEXT,'文本日志'),
            'screenshot' => sprintf("%s NOT NULL COMMENT '%s'",Schema::TYPE_TEXT,'截图日志'),
        ], $tableOptions);
    }

    public function down()
    {
        $this->dropTable('z1log_log');
    }
}
