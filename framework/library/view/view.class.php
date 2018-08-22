<?php

/**
 * 视图渲染类
 *
 * 功能：
 * 初始化模板，渲染输出
 *
 * @package view
 */
require_once dirname(__FILE__) . '/view.base.php';

// 来自于apf的view类，lightPHP中没有app和module的概念，因此需要改一遍

class view extends viewBase
{

    /**
     * 当前使用的模板的名字，是post不是post.phtml
     */
    private $themeName = '';
    private $project = '';
    static $templateStyle = 'default';
    private $themePath = NULL;
    /**
     * 实例化模板变量
     * @var <object>
     */
    private $_template = null;

    public function __construct($style = 'default', $project = PROJECT)
    {
        $style || $style = 'default';
        $this->project = $project;
        self::setTemplateStyle($style);
    }

    static public function getThemePath()
    {
        $themePath = PROJECT_PATH . 'view/' . self::$templateStyle;
        if (!is_readable($themePath)) {
            throw new exception2('can_not_locate_page_path');
        }

        return $themePath;
    }

    /**
     * 实例化一个具体的模板引擎，默认使用系统自带的
     * @param <type> $templateName
     */
    public function template($fullTemplateName, $contentType)
    {
        if ($GLOBALS['PROJECTS'][PROJECT]['template_engine'] === '') {
            require_once dirname(__FILE__) . '/template.class.php';
            $this->_template = new template($fullTemplateName, $contentType, $this->project);
        }
        else {
            //待续：载入自定义模板引擎
            throw new exception2('Dont support the defined template engine now.wait soon...');
        }

        return $this->_template;
    }

    /**
     * 输出视图
     * 1.先找到用什么模板
     * 2.把模板变量传给该模板，调用其解析好内容并输出
     * @param <type> $templateName
     * @param <type> $isCache 是否启用缓存文件
     * @param <type> $domainPerfix 是否开启域名前缀
     * @param <type> $contentType
     */
    public function render($templateName = '', $isCache = false, $domainPerfix = '', $contentType = 'text/html; charset=utf-8')
    {
        // 屏蔽notice和warning错误
        error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);

        if ($templateName == '') {
            $templateName = strtolower(CONTROLLER . '_' . ACTION);
        }

        $themePath = self::getThemePath();

        //设置一些全局模板变量，便于应用端使用
        // 算出完整的$templateName,实例化传入模板
        $fullTemplateName = $themePath . '/' . $templateName . $GLOBALS['PROJECTS'][$this->project]['template_postfix'];
        if ($this->_template === null) {
            $this->template($fullTemplateName, $contentType);
        }

        $this->_template->assign($this->getAllVars());
        $this->_template->display($isCache, $domainPerfix);
    }

    /**
     * 输出视图
     * 1.先找到用什么模板
     * 2.把模板变量传给该模板，调用其解析好内容并输出
     * @param <type> $templateName
     * @param <type> $contentType
     */
    public function fetch($templateName, $contentType = 'text/html; charset=utf-8')
    {
        // 屏蔽notice和warning错误
        error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);

        if (empty($templateName) || empty($contentType)) {
            throw new exception2('invalid_args');
        }

        $themePath = self::getThemePath();

        //设置一些全局模板变量，便于应用端使用
        // 算出完整的$templateName,实例化传入模板
        $fullTemplateName = $themePath . '/' . $templateName . $GLOBALS['PROJECTS'][$this->project]['template_postfix'];
        if ($this->_template === null) {
            $this->template($fullTemplateName, $contentType);
        }

        $this->_template->assign($this->getAllVars());

        return $this->_template->fetch();
    }

    /**
     * 设置模板风格
     * @param <type> $style
     */
    static public function setTemplateStyle($style)
    {
        self::$templateStyle = $style;
    }

    /**
     * 防攻击输入静态文件，并且过滤非法请求参数,实现非法攻击输出静态，合法的ajax本页请求不走缓存
     * @param $cacheConf  缓存配置
     * @param $controller  请求控制器
     * @param $action  请求方法
     */
    public function cacheOutPut($cacheConf, $controller, $action, $request)
    {
        if(!$cacheConf || !$controller || !$action){
            throw new exception2('invalid_cache_args');
        }
        //如果请求的页面不在静态配置中直接返回
        if (!array_key_exists($controller, $cacheConf) || !array_key_exists($action, $cacheConf[$controller])){
            return true;
        }
        //请求的静态文件也分两种情况：1纯加载 2页面中发送到本页面的AJAX不能走静态缓存,另外过滤掉非法的参数请求
        $paramWay = $cacheConf[$controller][$action]['way'];
        $paramName = $cacheConf[$controller][$action]['paramName'];
        $paramData = $cacheConf[$controller][$action]['paramData'];
        $domainPerfix = $cacheConf[$controller][$action]['domainPerfix'];
        if($paramWay && $paramName && $paramData){//如有值则默认有带参数的请求
            $fun = 'get'.ucfirst($paramWay);
            $tmpVar = $request->$fun($paramName,$cacheConf[$controller][$action]['paramType']);
            if($tmpVar == $paramData){//如果是合法请求参数正常退出不走静态缓存
                return true;
            }
        }
        $cacheFile = $controller . '_' . $cacheConf[$controller][$action]['tpl'] . '.html';
        //这里针对未登录首页弹窗做域名cache
        $cacheDir = $domainPerfix == true ? CACHE_DIR .  $_SERVER['HTTP_HOST'] . '_' . $cacheFile : CACHE_DIR . $cacheFile ;
        if(file_exists($cacheDir)){
            include $cacheDir;
            ob_end_flush();
            exit;
        }
    }

}
