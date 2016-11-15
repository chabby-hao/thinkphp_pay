<?php 
namespace Common\Extend;

class Helper
{
    
    /**
     * 根据字节数转换为可视大小（GB/MB/KB）
     * 参    数：$strSize        string      字节数大小，为数字类型，但是由于整形的范围的限制，使用字符串格式
     * 返    回：$strRe          string      结果
     * 作    者：王雕
     * 功    能：输出软件包的大小
     * 修改日期：2016-08-01
     */
    public static function sizeInfo($strSize)
    {
        $floTemp = $strSize / 1024;
        if ($strSize <= 1024)
        {
            $strRe = '1KB';
        }
        elseif ($floTemp <= 800)
        {
            $strRe = number_format($floTemp, 2) . 'KB';
        }
        else
        {
            $floTemp = $floTemp / 1024;
            if ($floTemp <= 800)
            {
                $strRe = number_format($floTemp, 2) . 'MB';
            }
            else
            {
                $strRe = number_format($floTemp / 1024, 2) . 'GB';
            }
        }
        return $strRe;
    }
    
    public static function getApkUrl($apkPath)
    {
        return 'http://' . $_SERVER['HTTP_HOST'] . $apkPath;
    }
    
    
}