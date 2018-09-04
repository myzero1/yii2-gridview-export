<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\search\PlaceSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

\myzero1\adminlteiframe\gii\GiiAsset::register($this);

// var_dump(\Yii::$app->controller->module->dependClass['RbacpRole']::find());exit;

$this->title = '日志管理';
?>
<div class="place-index" id="search_form">

    <div class="adminlteiframe-action-box user2-search">

        <?php $form = ActiveForm::begin([
            'action' => ['index'],
            'method' => 'get',
        ]); ?>

        <?= $form->field($searchModel, 'text')?>

        <?= $form->field($searchModel, 'url')?>

        <?= $form->field($searchModel, 'user_id')?>

        <?= $form->field($searchModel, 'user_name')?>

        <div class="form-group aciotns">
            <?= Html::submitButton('搜索', ['class' => 'btn btn-primary']) ?>
        </div>
        <?php ActiveForm::end(); ?>

    </div>

    <?php

/*
    echo GridView::widget([
        'dataProvider' => $dataProvider,
        'options' => [
            'class' => 'list',
        ],
        'summary' => '
            <div class="dataTables_info" id="example2_info" role="status" aria-live="polite">
                <span>共<font id="P_RecordCount" style="color:Red;">{totalCount}</font>条记录</span>
                <span>第<font id="P_Index" style="color:Red;">{page}</font>/<font id="P_PageCount" style="color:Red;">{pageCount}</font>页</span>
                <span>每页<font id="P_PageSize" style="color:Red;">{count}</font>条记录</span>
            </div>
        ',
        'layout'=> '
            {items}
            <div class="footer-clearfix"></div>
            <div class="footer">
                <div class="row box-footer clearfix" id="PageTurn">
                    <div class="col-sm-5">
                        {summary}
                    </div>
                    <div class="col-sm-7">
                        {pager}
                    </div>
                </div>
            </div>
        ',
        // 'pager' => [
        //     'class' => \myzero1app\themes\adminlte\widgets\LinkPager::className(),
        //     'firstPageLabel'=>"首页",
        //     'prevPageLabel'=>'上一页',
        //     'nextPageLabel'=>'下一页',
        //     'lastPageLabel'=>'末页',
        //     'maxButtonCount'=>'0',
        //     'hideOnSinglePage'=>false,
        // ],
        'tableOptions' => ['class' => 'dataTables_wrapper no-footer'],
        'columns' => [
            'id',
            'text',
            'screenshot' => [
                'label'=>'截图日志',
                'content' => function($row){
                    return Html::tag ('span', '查看截图',[
                        'class'=>'list_r list_crud_act',
                        'crud-url'=>url::to(['/myzero1/log/z1log-log/snapshoot', 'id' => $row->id]),
                        'crud-method'=>'get',
                        'crud-data'=>'',
                        'win-title'=>'查看截图',
                        'win-width'=>'800px',
                    ]);
                }
            ],
            'url' => [
                'label'=>'操作Url',
                'attribute' => 'url',
                'value' => function($row){
                    return $row->url;
                }
            ],
            'user_id',
            'user_name',
            'ip',
            'created' => [
                'label'=>'创建时间',
                'attribute' => 'created',
                'value' => function($row){
                    return is_null($row->created) ? '' : date('Y-m-d H:i:s', $row->created);
                }
            ],
        ],
    ]); */

    ?> 


    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            [
                'headerOptions' => ['width'=>'30'],
                'class' => yii\grid\CheckboxColumn::className(),
                'name' => 'z1selected',
                'checkboxOptions' => function ($model, $key, $index, $column) {
                    return ['value' => $model->id];
                },
            ],
            'id',
            'text',
            'url' => [
                'label'=>'操作Url',
                'attribute' => 'url',
                'value' => function($row){
                    return $row->url;
                }
            ],
            'user_id',
            'user_name',
            'ip',
            'created' => [
                'label'=>'创建时间',
                'attribute' => 'created',
                'value' => function($row){
                    return is_null($row->created) ? '' : date('Y-m-d H:i:s', $row->created);
                }
            ],
            [
                // 'contentOptions' => [
                //     'width'=>'100'
                // ],
                'headerOptions' => [
                    'width'=>'100px'
                ],
                'header' => Yii::t('rbacp', '查看截图'),
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view}',

                'buttons' => [
                    'view' => function ($url, $model, $key) {
                        $options = array_merge([
                            'class'=>'btn btn-primary btn-xs use-layer',
                            'layer-config' => sprintf('{area:["700px","400px"],type:2,title:"%s",content:"%s",shadeClose:false}', '查看截图', url::to(['/'.$this->context->module->id.'/z1log-log/snapshoot', 'id' => $model->id])) ,
                        ]);

                        if (!empty($model->screenshot)) {
                            return Html::a('查看截图', '#', $options);
                        }
                    },
                ],
            ],
        ],
        'options' => [
            'rbacp_policy_sku' => 'rbacp|rbacp-privilege|index|rbacpPolicy|list|rbacp权限列表',
            'class' => 'adminlteiframe-gridview',
        ],
        'tableOptions' => [
            'class' => 'gridview-table table table-bordered table-hover dataTable'
        ],
        'summary' => '
            <div class="admlteiframe-gv-summary">
                共 <span class="total">{totalCount}</span> 条
            </div>
        ',
        'layout'=> '
            {items}
            <div class="admlteiframe-gv-footer">
                {pager}{summary}
            </div>
        ',
        'pager' => [
            'class' => \myzero1\adminlteiframe\widgets\LinkPager::className(),
            'firstPageLabel'=>"<<",
            'prevPageLabel'=>'<',
            'nextPageLabel'=>'>',
            'lastPageLabel'=>'>>',
            'maxButtonCount'=>'5',
            // 'activePageCssClass' => 'btn btn-primary btn-xs',
            'hideOnSinglePage'=>false,
            'options' => [
                'class' => 'admlteiframe-gv-pagination'
            ],
        ],
    ]); ?>

</div>

<?php 
$js=<<<eof
    function getTableHeight(){
        var heightToal = window.parent.$('html').outerHeight(true);
        var filterHeight = $(".adminlteiframe-action-box").height();
        height = heightToal - $(".adminlteiframe-action-box").height();// subtract filters
        height = height - 260;// subtract others
        return height;
    }

    function fixTable(){
        if (!($(".gridview-table .empty").length > 0 || $(".gridview-table tbody tr").length == 0)) {
                if(typeof mybootstrapTable!="undefined"){
                    mybootstrapTable.bootstrapTable('destroy');
                }

                mybootstrapTable = $(".gridview-table").bootstrapTable('destroy').bootstrapTable({
                    height: getTableHeight(),
                    fixedColumns: true
                });
        }
    }

    fixTable();

    $(window).resize(function(){
        fixTable();
    });

eof;

$this->registerJs($js);

?>