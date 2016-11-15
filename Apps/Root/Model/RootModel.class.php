<?php 
namespace Root\Model;
use Think\Model;
class RootModel extends Model {
   
    protected $tableName = 'root';//表名
    
    //产生密码
    public function generateRootPwd($strInputPwd)
    {
    	return md5(sha1($strInputPwd));
    }
    
    //根据用户和密码获取root用户
    public function getRoot($rootName, $rootPwd)
    {
        $where = [];
        $where['root_name'] = ':root_name';
        $where['root_pwd'] = ':root_pwd';
        $bind = [];
        $bind[':root_name'] = $rootName;
        $bind[':root_pwd'] = $this->generateRootPwd($rootPwd);
        
        $res = $this->where($where)->bind($bind)->find();
        return empty($res) ? false : $res;
    }
    
    //保存root用户信息
    public function updateRoot($data, $rootId)
    {
        if(is_array($data) && isset($data['root_pwd']))
        {
            $data['root_pwd'] = $this->generateRootPwd($data['root_pwd']);
        }
        $where = ['id'=>':id'];
        $bind = [':id'=>$rootId];
        return $this->where($where)->bind($bind)->save($data);
    }
    
    //新增管理员用户
    public function addRoot($data)
    {
        if(is_array($data) && isset($data['root_pwd']))
        {
            $data['root_pwd'] = $this->generateRootPwd($data['root_pwd']);
            return $this->add($data);
        }
        return false;
    }
    
}
