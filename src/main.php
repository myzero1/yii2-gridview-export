<?php
return [
    'params' => [
        'urlManager' => [
            'rules' => [
                // 'rate/area/index' => 'rate/jf-core-area/index',
            ],
        ],
        'userInfo' => [
            'id' => function(){
                if(\Yii::$app->user->isGuest){
                    $id = 0;
                } else {
                    $id = \Yii::$app->user->identity->id;
                }

                return $id;
            },
            'name' => function(){
                if(\Yii::$app->user->isGuest){
                    $name = 'system';
                } else {
                    $name = \Yii::$app->user->identity->user_name;
                }
                
                return $name;
            }
        ],
        'template' => [
            'z1user/user2/create' => [
                'model' => 'all', // text,screenshot,all
                'text' => function(){
                    return '添加用户';
                },
                'screenshot' => 'z1user/user2/index', // The template of screenshot
            ],
            // 'z1user/user2/update' => [
            //     'model' => 'all', // text,screenshot,all
            //     'text' => function(){
            //         return '修改用户';
            //     },
            //     'screenshot' => 'z1user/user2/update', // The template of screenshot
            // ],
            'z1user/user2/delete' => [
                'model' => 'all', // text,screenshot,all
                'text' => function(){
                    return '删除用户';
                },
                'screenshot' => 'z1user/user2/update', // The template of screenshot
            ],
        ],
    ],
];
