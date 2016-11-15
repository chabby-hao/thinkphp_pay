<?php
//自更新控制器
namespace Api\Controller;
use Think\Controller;
use Api\Msg\ErrMsg;
use Common\Extend\Helper;
use Common\Model\SelfUpdateModel;
class SelfUpdateController extends BaseController {
    
    
    //检查应用自更新接口
    public function checkUpdate()
    {
        $arrInput = checkFieldInArray('vercode', $this->getPost('post.'));
        $arrData = D('SelfUpdate')->getSelfUpdateForApi();
        
        if($arrData)
        {
            if($arrData['is_use'] == SelfUpdateModel::IS_USE_OPEN 
            && (intval($arrData['vercode'])-intval($arrInput['vercode'])) > 0 
            && file_exists(ROOT_PATH . '/Web' . $arrData['apk_path']) )
            {
                $data = [];
                $data['update_type'] = intval($arrData['update_type']);//1普通更新，2强制更新
                $data['apk_url'] = Helper::getApkUrl($arrData['apk_path']);
                $data['new_vercode'] = intval($arrData['vercode']);
                $data['new_version'] = $arrData['version'];
                $data['update_desc'] = $arrData['update_desc'];
                outputData($data);
            }
        }
        outputErr(ErrMsg::$err['self.update.error']);
        
    }
    
}