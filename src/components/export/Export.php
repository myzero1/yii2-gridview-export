<?php

namespace myzero1\log\components\export;

/**
 * Export
 *
 */
class Export
{
    /**
     * @inheritdoc
     */

    /**
     * unset the z1logSaved session.
     * 
     * @param string $model text,screenshot,all
     * @param string $screenshot 'z1user/user2/update' The template router
     * @param array $screenshotParams ['id'=>21]
     * @param string $text function(){return 'aa';}
     *
     *  \Yii::$app->params['dependClass']['z1logExport']::z1logAdd('all','z1user/user2/index', [], function(){return 'aa';});
     *
     * @return viod 
     * 
     * @author myzero1
     * @since 2.0.13
     */
    public static function z1logAdd($model, $screenshot, $screenshotParams, $text){

        if (!isset($_SESSION['z1logSaved'])) {
            $_SESSION['z1logSaved'] = 1;
        }

        $screenshotContent = '';
        $textContent = '';
        
        if (in_array($model, ['all','screenshot']) ) {
            $params = \Yii::$app->request->get();
            \Yii::$app->params['z1log']['params']['z1logToRending'] = true;
            $params = array_merge($params, $screenshotParams);
            $sHtml = \Yii::$app->runAction('/' . $screenshot, $params);
            $sHtmlCom = $sHtml;
            $sHtmlCom = ltrim(rtrim(preg_replace(array("/> *([^ ]*) *</","//","'/\*[^*]*\*/'","/\r\n/","/\n/","/\t/",'/>[ ]+</'),array(">\\1<",'','','','','','><'),$sHtml)));
            $sHtmlCom = str_replace('href="', 'hrefDisabled="', $sHtmlCom);
            $sHtmlCom = str_replace('action="', 'actionDisabled="', $sHtmlCom);
            $sHtmlCom = str_replace('type="submit"', 'typeDisabled="submit"', $sHtmlCom);

            $screenshotContent = $sHtmlCom;
        }

        if (in_array($model, ['all','text']) ) {
            $textContent = $text();
        }

        $sql = sprintf("INSERT INTO `z1log_log` (
                    `id`,
                    `user_id`,
                    `user_name`,
                    `ip`,
                    `created`,
                    `url`,
                    `text`,
                    `screenshot`
                )
                VALUES
                    (NULL, %d, '%s', '%s', %d, '%s', '%s', '%s')",
                    \Yii::$app->params['z1log']['params']['userInfo']['id'](),
                    \Yii::$app->params['z1log']['params']['userInfo']['name'](),
                    \Yii::$app->request->userIP,
                    time(),
                    \Yii::$app->request->pathInfo,
                    $textContent,
                    base64_encode($screenshotContent));

        \Yii::$app->db->createCommand($sql)->execute();
    }
}
