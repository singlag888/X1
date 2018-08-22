<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 控制器： 管理
 */
class guestBookController extends sscAdminController
{

    public $titles = array(
        'guestBookList' => '留言列表',
        'viewGuestBook' => '查看留言',
        'deleteGuestBook' => '删除留言',
        'setGuestBookDealed' => '设置留言已处理',
        'replyGuest' => '回复留言',
    );

    public function init()
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
    }

    //公告列表
    public function guestBookList()
    {
//        $startPos = ($this->request->getGet('curPage', 'intval', 1) - 1) * DEFAULT_PER_PAGE;
        $username = $this->request->getGet('username', 'trim');
        $status = $this->request->getGet('status', 'intval') != 0 ? $this->request->getGet('status', 'intval') : 0;
        $guestBooksNumber = guestBooks::getItemsNumber($username, $status);

        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $curPage = $this->request->getGet('curPage', 'intval', 1);
        //>>判断输入的页码是否超过最大值.
        $startPos = getStartOffset($curPage, $guestBooksNumber);
        /*********************** snow 判断传入的最大的页码值是否超过最大值.*************************************/
        $guestBooks = guestBooks::getItems($username, $status, $startPos, DEFAULT_PER_PAGE);

        self::$view->setVar('status', $status);
        self::$view->setVar('username', $username);
        self::$view->setVar('guestBooks', $guestBooks);
        self::$view->setVar('guestBooksNumber', $guestBooksNumber);
        self::$view->setVar('pageList', getPageList($guestBooksNumber, DEFAULT_PER_PAGE));

        self::$view->setVar('canView', adminGroups::verifyPriv(array(CONTROLLER, 'viewGuestBook')));
        self::$view->setVar('canDelete', adminGroups::verifyPriv(array(CONTROLLER, 'deleteGuestBook')));
        self::$view->render('guestbook_list');
    }

    public function viewGuestBook()
    {
        $locations = array(0 => array('title' => '返回留言列表', 'url' => url('guestBook', 'guestBookList')));
        
        if (!$gb_id = $this->request->getGet('gb_id', 'intval')) {
            showMsg("参数无效！", 1, $locations);
        }
        if (!$guestbook = guestBooks::getItem($gb_id)) {
            showMsg('该留言不存在');
        }
        //修改为已读
        if ($guestbook['status'] == 1) {//如果是未读才修改
            guestBooks::updateItem($gb_id, array('status' => 2));
            $guestbook = guestBooks::getItem($gb_id);
        }
        if ($guestbook['msg_id']) {
            $msg = messages::getItem($guestbook['msg_id']); //查看有没有被客服回复 
            self::$view->setVar('msg', $msg);
        }
        self::$view->setVar('guestbook', $guestbook);
        self::$view->render('guestbook_info');
    }

    public function deleteGuestBook()
    {
        $locations = array(0 => array('title' => '返回留言列表', 'url' => url('guestBook', 'guestBookList')));
        if (!$gb_id = $this->request->getGet('gb_id', 'intval')) {
            showMsg("参数无效！", 1, $locations);
        }

        if (!guestBooks::updateItem($gb_id, array('status' => 4))) {
            showMsg("删除数据失败！", 0, $locations);
        }

        showMsg("删除数据成功！", 0, $locations);
    }

 
    /**
     * 设置为已处理
     */
    public function setGuestBookDealed()
    {
        $locations = array(0 => array('title' => '返回留言列表', 'url' => url('guestBook', 'guestBookList')));
        if (!$gb_id = $this->request->getGet('gb_id', 'intval')) {
            showMsg("参数无效！", 1, $locations);
        }

        if (!guestBooks::updateItem($gb_id,array('status'=>1,'deal_time'=>date('Y-m-d H:i:s'),'deal_admin_id'=>$GLOBALS['SESSION']['admin_id']))) {
            showMsg("设置为已处理失败！", 0, $locations);
        }

        showMsg("设置为已处理成功！", 0, $locations);
    }
    
    public function replyGuest()
    {
        //工作人员提交的回复
        $title = $this->request->getPost('title', 'trim');
        $content = $this->request->getPost('content', 'trim');
        $to_user_id = $this->request->getPost('to_user_id', 'intval');
        $gb_id = $this->request->getPost('gb_id', 'intval');

        if (!$title || !$content || !$to_user_id || !$gb_id) {
            showMsg("参数无效");
        }
        if (guestBooks::replyGuestBook($gb_id, $title, $content, $to_user_id)) {
            $locations = array(0 => array('title' => '返回留言列表', 'url' => url('guestBook', 'guestBookList')));
            showMsg("消息发送成功！", 1, $locations);
        }
        showMsg("消息发送失败！");
    }

}

?>