<?php

if (!defined('APP_PATH')) {
    exit(0);
}

/**
 * @description Hope You Do Good But Not Evil
 */
class Index extends Controller
{
    const COOKIE_EXP = 28800;
    const LIST_LIMIT = 100;
    const loginKeyK = '4s5mpxa';

    /**
     * 构造函数
     * @param type $ControllerName
     * @param type $Action
     * @param type $QueryString
     */
    public function __construct($ControllerName, $Action, $QueryString)
    {
        parent::__construct($ControllerName, $Action, $QueryString);
        $this->initSettings();
        $this->loadModel('Session');
        header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0"); // HTTP/1.1
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
        header("Pragma: no-cache"); // Date in the past
    }

    /**
     * 管理后台首页
     */
    public function index()
    {
        if (!$this->Auth->checkAuth()) {
            return $this->redirect('?/Index/logOut');
        }
        if ($this->pCookie('loginKey')) {
            $this->Smarty->cache_lifetime = 7200;
            if (!$this->isCached()) {
                $uid = $this->Session->get('uid');
                $menuData = $this->Db->query("call p_get_user_menu(" . $uid . ");");
                $this->Smarty->assign('adid', $uid);
                $this->Smarty->assign('adname', $this->Session->get('uname'));
                $this->Smarty->assign('menu', $menuData);
                $this->Smarty->assign('today', date("n月j日 星期") . $this->Util->getTodayStr());
            }
            $this->show('./views/index.tpl');
        } else {
            header('Location:' . $this->root . '?/Index/login');
            exit(0);
        }
    }

    /**
     * 退出登录清空cookie
     */
    public function logOut()
    {
        foreach ($_COOKIE as $k => $v) {
            setcookie($k, null);
        }
        $this->Session->clear();
        header('Location:?/Index/login/');
    }

    /**
     * 登录处理
     */
    public function checkLogin()
    {
        $this->Session->start();
        $ip = $this->getIp();
        $this->loadModel('mAdmin');
        $admin_acc = addslashes(trim($this->post('admin_acc')));
        $admin_pwd = addslashes(trim($this->post('admin_pwd')));
        // 保存登录账户
        $this->sCookie('admin_acc', $admin_acc, self::COOKIE_EXP);
        // admin login
        $admininfo = $this->mAdmin->get($admin_acc);
        // 写入登陆记录
        @$this->Dao->insert(TABLE_LGOIN_RECORDS, 'account,ip,ldate')
            ->values(array($admin_acc, $ip, 'NOW()'))
            ->exec();
        if ($admininfo) {
            // 校验成功
            if ($this->mAdmin->pwdCheck((string)$admininfo['person_password'], (string)$admin_pwd)) {
                // 更新管理员登录状态
                $this->mAdmin->updateAdminState($admin_acc, $ip, $admininfo['id']);
                // 权限密钥
                $loginKey = $this->mAdmin->encryptToken($ip, $admininfo['id']);
                // 写入数据到session
                $this->Session->set('loginKey', $loginKey);
                $this->setUserSession($admininfo);
                $this->sCookieHttpOnly('loginKey', $loginKey, self::COOKIE_EXP);
                // 删除cookie
                $this->sCookie('admin_acc', '', 1);
                // 成功
                $this->echoJson(array('status' => 1));
            } else {
                // 失败
                $this->echoJson(array('status' => 0));
            }
        } else {
            Util::log("登录失败，密码有误！ " . $admin_acc);
            // 失败
            $this->echoJson(array('status' => 0));
        }
        $this->sCookie('admin_acc', null);
    }

    /**
     * 登录页面
     */
    public function login()
    {
        $this->initSettings(true);
        $this->show('./views/login.tpl');
    }

    /**
     * 过期时间（秒）
     * @var int
     */
    private $expire = 7200;


    public function wxerror()
    {
        $this->show('./views/wxerror.tpl');
    }
}
