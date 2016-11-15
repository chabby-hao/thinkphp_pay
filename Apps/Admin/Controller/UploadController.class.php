<?php
namespace Admin\Controller;
use Think\Controller;
use Think\Upload;
use Common\Model\StoreModel;
class UploadController extends BaseController {
    
    //文件上传
    public function fileUpload()
    {
        $upload = new Upload(); // 实例化上传类                                // $upload->maxSize = 3145728 ;// 设置附件上传大小        $upload->exts = array(            'jpg',            'gif',            'png',            'jpeg'        ); // 设置附件上传类型        $upload->rootPath = PUBLIC_PATH; // 设置附件上传根目录        $upload->savePath = 'Uploads/Store/'; // 设置附件上传（子）目录        $upload->saveName = date('YmdHis') . mt_rand(10000, 99999); // GUID文件名        $upload->autoSub = false;        // 上传文件        $info = $upload->upload();        if (! $info)        { // 上传错误提示错误信息            adminOutputErr($upload->getError());        }        else        { // 上传成功            adminOutputData(PUBLIC_PATH . $info['file_upload']['savepath'] . $info['file_upload']['savename']);        }
    }
}