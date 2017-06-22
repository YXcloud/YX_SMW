<?php

/**
 * Desc
 * @description Hope You Do Good But Not Evil
 */
class mAdmin extends Model
{

    /**
     * 生成admin加密密文
     * @global type $config
     * @param type $pwd
     * @return type
     */
    public function encryptPassword($pwd)
    {
        global $config;
        return hash('sha384', $pwd . $config->admin_salt);
    }

    /**
     * 校验登陆提交密码
     * @param string $pwd_db
     * @param string $pwd_submit
     * @return boolean
     */
    public function pwdCheck($pwd_db, $pwd_submit)
    {
        return $pwd_db == $this->encryptPassword($pwd_submit);
    }

    /**
     * 生成登陆token
     * @global type $config
     * @param type $ip
     * @param type $id
     * @return type
     */
    public function encryptToken($ip, $id)
    {
        return sha1($ip . $id);
    }

    /**
     * 管理员登陆记录
     * @param type $account
     * @param type $ip
     * @param type $id
     */
    public function updateAdminState($account, $ip, $id)
    {
        // 更新登陆时间
        $this->Dao->update(TABLE_PERSON)
            ->set(array(
                'person_last_login' => 'NOW()',
                'person_ip_address' => $ip
            ))
            ->where("id = $id")
            ->exec();
    }

    /**
     * 获取管理员账户
     * @param string $admin_acc
     * @return array
     */
    public function get($admin_acc)
    {
        return $this->Dao->select('p.*,t.title_roles as roles')
            ->from(TABLE_PERSON)
            ->alias('p')
            ->leftJoin(TABLE_TITLE)
            ->alias('t')
            ->on('p.person_title = t.id and t.isvalid = 1')
            ->where("person_phone = '$admin_acc'")
            ->aw("p.isvalid = 1")
            ->getOneRow();
    }


    /**
     * 修改密码
     * @param string $admin_acc
     * @return array
     */
    public function changePass($data)
    {
        $old_pass = $this->encryptPassword($data['old']);
        $isExist = $this->Dao->select('count(1)')
            ->from(TABLE_PERSON)
            ->where("isvalid = 1")
            ->aw("id = " . $data['uid'])
            ->aw("person_password = '$old_pass'")
            ->getOne();
        if ($isExist > 0) {
            $this->Dao->update(TABLE_PERSON)
                ->set(['person_password' => $this->encryptPassword($data['new'])])
                ->where("isvalid = 1")
                ->aw("id = " . $data['uid'])
                ->exec();
        } else {
            throw new exception('原密码不正确');
        }
    }

}
