<?php

if (!defined('APP_PATH')) {
    exit(0);
}

/**
 * 微信页面控制器
 * @description Hope You Do Good But Not Evil
 *
 */
class WxPage extends ControllerWx
{

    const TPL = './views/';

    /**
     * 构造函数
     * @param string $ControllerName
     * @param string $Action
     * @param string $QueryString
     */
    public function __construct($ControllerName, $Action, $QueryString)
    {
        parent::__construct($ControllerName, $Action, $QueryString);
        $this->Db->cache = false;
        $this->Smarty->caching = false;
        $this->initSettings();
        header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0"); // HTTP/1.1
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
        header("Pragma: no-cache"); // Date in the past
    }

    public function wxtest()
    {
        $this->show(self::TPL . 'wxtest.tpl');
    }

}