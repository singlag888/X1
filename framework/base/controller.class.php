<?php

abstract class controller
{
    /**
     * @var view
     */
    protected static $view = null;

    /**
     * @var request
     */
    protected $request = null;

    // 初始化
    abstract public function init();

    // 权限认证 这里不写成抽象方法了，php5.2.x不要写参数，php5.3.x要写参数
    public function validate()
    {

    }

    public function setRequestInfo($request)
    {
        $this->request = $request;
    }

    public function getView()
    {
        return self::$view;
    }

    public function setView($view)
    {
        return self::$view = $view;
    }
}

?>