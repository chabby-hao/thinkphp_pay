<?php
namespace Common\Controller;
use Think\Controller;
use Common\Extend\Page;
use Common\Extend\AdminPage;
class AdminCommonController extends Controller {
    
    public $intPageLimit = 10;//默认分页数量
    
    protected function _getPageNav($intRowCount, $intPage = 1, $offset = 4)    {        $uri = I('server.REQUEST_URI');        $strQuery = $_SERVER['QUERY_STRING'];        parse_str($strQuery, $arrQuery);        $arrQuery['page'] = '{page}';
        $strQuery = http_build_query($arrQuery);
        $urlPath = parse_url($uri, PHP_URL_PATH);        $url = $urlPath . '?' . $strQuery;
        $url = urldecode($url);
        $page = new AdminPage($intRowCount, $this->intPageLimit, $intPage, $url, $offset);
        return $page->myde_write();    }
    
}