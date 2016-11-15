<?php
namespace Root\Controller;
use Think\Controller;
use Think\Upload;
use Common\Model\SelfUpdateModel;
class UpdateController extends BaseController {
    
    
    public function index()
    {
        $arrData = D('SelfUpdate')->where(['name'=>SelfUpdateModel::NAME_MAJOR])->find();
        $this->assign('arrData', $arrData);
        $this->_setPageHeaderAction('自更新控制台');
        $this->display();
    }
    
    public function edit()
    {
        $arrData = D('SelfUpdate')->where(['name'=>SelfUpdateModel::NAME_MAJOR])->find();
        $this->assign('arrData', $arrData);
        $this->_setPageHeaderAction('自更新编辑');
        $this->display();
    }
    
    public function editHandler()
    {
        $arrInput = I('post.');
        if($arrInput['new_file'])
        {
            $upload = new Upload(); // 实例化上传类
            $upload->maxSize = 5000000 ;// 设置附件上传大小
            $upload->saveExt = null;//不需要设置后缀
            $upload->exts = array(
                'apk',
            ); // 设置附件上传类型 
            $upload->rootPath = PUBLIC_PATH; // 设置附件上传根目录
            $upload->savePath = 'Uploads/Update/'; // 设置附件上传（子）目录
            $upload->saveName = $_FILES['file_upload']['name']; // GUID文件名
            $upload->autoSub = false;
            // 上传文件
            $info = $upload->upload();
            if ( !$info)
            { // 上传错误提示错误信息
                adminOutputErr($upload->getError());
            }
            else
            { // 上传成功
                $arrInput['apk_filename'] = $info['file_upload']['name'];
                $arrInput['apk_path'] = ltrim(PUBLIC_PATH . $info['file_upload']['savepath'] . $info['file_upload']['name'], '.');
                //adminOutputData(PUBLIC_PATH . $info['file_upload']['savepath'] . $info['file_upload']['savename']);
            }
            unset($arrInput['new_file']);
        }
        $res = D('SelfUpdate')->where(['name'=>SelfUpdateModel::NAME_MAJOR])->save($arrInput);
        
        if($res !== false)
        {
            D('SelfUpdate')->clearSelfUpdateFromRedis();
            ajaxRedirect('index');
            //adminOutputData('success');
        }
        else 
        {
            adminOutputErr('失败');
        }
        
    }
    
    //启用或者停用当前自更新
    public function editUse()
    {
        $arrData = D('SelfUpdate')->where(['name'=>SelfUpdateModel::NAME_MAJOR])->find();
        if($arrData)
        {
            $data = [];
            $data['is_use'] = ($arrData['is_use'] == SelfUpdateModel::IS_USE_CLOSE) ? SelfUpdateModel::IS_USE_OPEN : SelfUpdateModel::IS_USE_CLOSE;
            $res = D('SelfUpdate')->where(['name'=>SelfUpdateModel::NAME_MAJOR])->save($data);
            if($res !== false)
            {
                $arrData = array_merge($arrData, $data);
                D('SelfUpdate')->setSelfUpdateToRedis($arrData);
                adminOutputData($data['is_use']);
            }
        }
        adminOutputData('修改失败');
    }
    
    protected function _setPageHeaderAction($action)
    {
        $this->_setPageHeader($action, '自更新');
    }
    
}