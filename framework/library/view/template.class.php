<?php

require_once dirname(__FILE__) . '/template.interface.php';

/**
 * @package view
 */
class template implements templateInterface
{
    private $project = '';
    /**
     * 存储模板变量
     */
    private $tplVars = array();
    /**
     * 模板文件的路径 eg: /path/to/template.phtml
     */
    protected $fullPathTemplateFile;
    /**
     * 模板的MimeType eg: text/html
     */
    protected $contentType;
    /**
     * 保存模板的输出内容
     */
    public $content;
    public $dateFormat = 'Y-m-d';
    public $timeFormat = 'Y-m-d H:i';
    /**
     * 模板的输出时候的一些处理开关，比如fullPathTemplateFile是否压缩，是否静态化URL
     */
    private $echoPolicy = 0;

    const STATICIZE_URLS = 1;
    const REBUILD_EXTERNAL_REFS = 2;
    const HTML_COMPRESSOR = 4;

    /**
     * 两个模板常量
     */
    public static $THEME_DOMAIN = NULL;
    /**
     * 模板使用的插件
     */
    private $plugIn = array();

    /**
     * 默认的 Content-type
     */
    const DEFAULT_CONTENT_TYPE = 'text/html; charset=utf-8';

    function __construct()
    {
        $args = func_get_args();
        $count = count($args);
        switch ($count) {
            case 0:
                $this->fullPathTemplateFile = '';
                $this->contentType = self::DEFAULT_CONTENT_TYPE;
                $this->project = PROJECT;
                break;

            case 1:
                if ($args[0]{0} === '/') {
                    $this->fullPathTemplateFile = $args[0];
                }
                else {
                    $this->content = $args[0];
                }

                $this->contentType = self::DEFAULT_CONTENT_TYPE;
                $this->project = PROJECT;
                break;

            case 2:
                $this->fullPathTemplateFile = $args[0];
                $this->contentType = $args[1];
                $this->project = PROJECT;
                break;

            case 3:
                $this->fullPathTemplateFile = $args[0];
                $this->contentType = $args[1];
                $this->project = $args[2];
                break;

            default:
                throw new exception2('invalid_args');
        }
    }

    /**
     * 支持引入header,footer等模板文件 08.10
     * @param <string> $appTemplate
     */
    public function import($subPageName, $isCache =  false)
    {
        $fullTemplateName = view::getThemePath() . "/$subPageName" . $GLOBALS['PROJECTS'][$this->project]['template_postfix'];
        $sub = new template($fullTemplateName, self::DEFAULT_CONTENT_TYPE, $this->project);

        $sub->assign($this->getTemplateVars());
        $sub->setAllEchoPolicyOff();
        $sub->display($isCache);
    }

    public function assign()
    {
        $args = func_get_args();

        switch (count($args)) {
            case 1:
                foreach ($args[0] AS $name => $val) {
                    $this->tplVars[$name] = $val;
                }
                break;

            case 2:
                $this->tplVars[$args[0]] = $args[1];
                break;

            default:
                throw new exception2('Invalid Template->assign()');
                break;
        }

        return $this;
    }

    public function registerPlugIn()
    {
        $args = func_get_args();
        if (count($args) > 0) {
            $arg = $args[0];
            unset($args[0]);
            $this->plugIn[$arg] = $args;
        }
    }

    public function unregisterPlugIn($func)
    {
        $args = func_get_args();
        if (count($args) > 0) {
            unset($this->plugIn[$args[0]]);
        }
    }

    public function getTemplateVars()
    {
        return $this->tplVars;
    }

    public function display($isCache = false, $domainPerfix = false)
    {
        if (empty($this->contentType)) {
            throw new exception2('invalid_content_type');
        }

        header2('Content-Type: ' . $this->contentType, true);

        //设置模板文件的路径
        $tplFile =  $this->fullPathTemplateFile;
        //后台不用缓存技术
        if(strpos($tplFile, 'sscadmin') !== false){
            $this->buildOutput();
            $this->handleFinalOutput();

            echo $this->content;
        } else {
            if(!file_exists(TPL_C_DIR)){
                mkdir(TPL_C_DIR,0777,true);
            }
            if(!file_exists(CACHE_DIR)){
                mkdir(CACHE_DIR,0777,true);
            }
            $file = str_replace('.phtml','',substr($tplFile,strrpos($tplFile,'/')+1));

            if (!file_exists($tplFile)) {
                throw new exception2('模板文件找不到啦！', 1, 'ERROR:template file does not exists:' . $tplFile);
            }

            $cFile  = TPL_C_DIR . md5($file) . $file . '.php';

            $cacheFile =  $domainPerfix == true ? CACHE_DIR . $_SERVER['HTTP_HOST'] . '_' . $file . '.html' :  CACHE_DIR . $file . '.html' ;

            if($isCache){
                if(file_exists($cacheFile) && file_exists($cFile)){
                    if(filemtime($cacheFile)>=filemtime($cFile) && filemtime($cFile)>filemtime($tplFile)){
                        include $cacheFile;
                        return;
                    }
                }
            }

            extract($this->tplVars);
            //此处$cFile和$tplFile没有区别，但为今后完善编译缓存预留
            if(!file_exists($cFile) || (filemtime($cFile) < filemtime($tplFile)) || $isCache == false){
                if(!$this->content = file_get_contents($tplFile)){
                    throw new exception2('ERROR:get tpl content failed');
                }

                if(!file_put_contents($cFile, $this->content)){
                    throw new exception2('ERROR:create complie file failed');
                }

            }

            include $cFile;
            if($isCache){

                file_put_contents($cacheFile, ob_get_contents());
                ob_end_clean();

                include $cacheFile;
            }
        }
    }

    public function fetch()
    {
        $this->buildOutput();
        $this->handleFinalOutput();

        return $this->content;
    }

    private function buildOutput()
    {
        extract($this->tplVars);

        // ob_start();

        require $this->fullPathTemplateFile;

        $this->content = ob_get_clean();

        foreach ($this->plugIn as $plugin => $args) {
            array_unshift($args, $this);
            call_user_func_array($plugin, $args);
        }
    }

    // 待整理
    private function handleFinalOutput()
    {
        if (($this->echoPolicy & self::STATICIZE_URLS) === self::STATICIZE_URLS) {
            $this->content = self::staticizeUrls($this->content);
        }

        if (!empty(self::$THEME_DOMAIN) && ($this->echoPolicy & self::REBUILD_EXTERNAL_REFS) === self::REBUILD_EXTERNAL_REFS) {
            $this->rebuildScriptExternalRefs();
        }

        if (!empty(self::$THEME_DOMAIN) && ($this->echoPolicy & self::REBUILD_EXTERNAL_REFS) === self::REBUILD_EXTERNAL_REFS) {
            $this->rebuildStyleExternalRefs();
        }

        if (($this->echoPolicy & self::HTML_COMPRESSOR) === self::HTML_COMPRESSOR) {
            $this->content = self::compressHTML($this->content);
        }
    }

    static public function compressHTML($content)
    {
        $r_tag = uniqid(__FILE__ . '::' . __FUNCTION__);

        if (preg_match_all('~(?:<pre.*?>.*?</pre>)|(?:<textarea.*?>.*?</textarea>)|(?:<!--COMPRESSRESERVED-->.*?<!--/COMPRESSRESERVED-->)~s', $content, $matches)) {
            $r_count = count($matches[0]);
            $r_replace = array();
            for ($i = 0; $i < $r_count; ++$i) {
                $r_replace[$i] = '__' . $i . '__' . $r_tag . '__';
            }
            $content = str_replace($matches[0], $r_replace, $content);
        }
        else {
            $r_count = 0;
        }

        /* 需要更多测试 */
        $content = preg_replace('/<!--\s*[^[\]\n]+\s*-->/', '', $content); //  去单行注释
        // TODO
        $content = preg_replace_callback('%(<script.*?</script>)%s', 'strip_js_callback', $content);

        // 目前只去掉行前空白
        $content = preg_replace("/[\n\r]+\s*/", '', $content);

        if ($r_count > 0) {
            $content = str_replace($r_replace, $matches[0], $content);
        }

        return $content;
    }

    /**
     * 设置输出策略。策略选项如下：
     * STATICIZE_URLS，REBUILD_EXTERNAL_REFS，HTML_COMPRESSOR
     *
     * @param int option
     * @param boolean $value
     */
    public function setEchoPolicy($option, $value)
    {
        if ($value === true) {
            $this->echoPolicy |= $option;
        }
        else {
            if (($this->echoPolicy & $option) === $option) {
                $this->echoPolicy ^= $option;
            }
        }
    }

    public function setAllEchoPolicyOff()
    {
        $this->echoPolicy = 0;
    }

    public function setTemplateFile($file)
    {
        $this->fullPathTemplateFile = $file;
    }

    public function setTemplateContent($content)
    {
        $this->content = $content;
    }

    // 修正CSS链接地址，其实可以不需要这里解析
    private function rebuildStyleExternalRefs()
    {
        if (preg_match_all('/<link.*?href="([^"]+\.css)".*?>/', $this->content, $match)) {
            if (strrpos($this->content, '</head>') !== false) {
                $this->content = str_replace(array_unique($match[0]), '', $this->content);
                $match[1] = str_replace(self::$THEME_DOMAIN, '', $match[1]);

                $css0 = $css1 = array();
                foreach ($match[1] AS $val) {
                    if ($val{0} === '/') {
                        $css0[] = $val;
                    }
                    else {
                        $css1[] = $val;
                    }
                }

                $out = '';
                if ($css0 !== array()) {
                    $css_cache_key = 'css_lasttime_' . md5(serialize($css0));
                    $css_lasttime = xc_get('Template', $css_cache_key);
                    if ($css_lasttime === false) {
                        $css_time_hash = '';
                        $css_lasttime = get_files_last_time($css0, $css_time_hash);

                        $css_lasttime = $css_time_hash;

                        if ($css_lasttime !== false) {
                            xc_add('Template', $css_cache_key, $css_lasttime, 60);
                        }
                    }
                    if ($css_lasttime !== false) {
                        $css_lasttime = '?' . $css_lasttime . '.css';
                    }
                    else {
                        $css_lasttime = '';
                    }
                    $out .= '<link href="' . self::$THEME_DOMAIN . implode(',', $css0) . $css_lasttime . '" rel="stylesheet" type="text/css" />';
                }
                foreach ($css1 AS $val) {
                    $out .= '<link href="' . $val . '" rel="stylesheet" type="text/css" />';
                }

                $this->content = str_replace('<head>', "<head>" . $out, $this->content);
            }
        }
    }

    protected function htmlOptions($arr, $selected = 0, $arg = NULL)
    {
        if (empty($arr)) {
            return '';
        }

        $fmt = '<option value="%s"%s>%s</option>';
        if ($arg === NULL) {
            $key = array_keys($arr);
            $val = array_values($arr);
        }
        else {
            $key = $arr;
            $val = $arg;
        }

        $rev = '';
        foreach ($key AS $k => $opt) {
            $sel = ($opt == $selected) ? " selected='true'" : '';
            $rev .= sprintf($fmt, $opt, $sel, htmlspecialchars($val[$k], ENT_QUOTES));
        }

        echo $rev;
    }

    /**
     * create html radio box
     *
     * @param string $name
     * @param array $arr
     * @param mixed $selected
     * @param string $extra
     * @param string $separation
     */
    protected function htmlRadio($name, $arr, $selected = null, $extra = '', $separation = "&nbsp;")
    {
        $this->htmlCheckboxAndRadio('radio', $name, $arr, $selected, $extra, $separation);
    }

    /**
     * create html checkbox
     *
     * @param string $name
     * @param array $arr
     * @param mixed $selected
     * @param string $extra
     * @param string $separation
     */
    protected function htmlCheckbox($name, $arr, $selected = null, $extra = '', $separation = "&nbsp;")
    {
        $this->htmlCheckboxAndRadio('checkbox', $name, $arr, $selected, $extra, $separation);
    }

    private function htmlCheckboxAndRadio($type, $name, $arr, $selected, $extra, $separation)
    {
        $rev = '';
        $fmt = '<input name="%s" type="%s" value="%s"%s' . $extra . '/> <span>%s</span>';
        foreach ($arr AS $key => $val) {
            if (!is_array($selected)) {
                $checked = ($key == $selected) ? " checked='true'" : '';
            }
            else {
                $checked = ((in_array("$key", $selected) && $selected !== array(0)) || ($selected === array(0) && $key == '0')) ? " checked='true'" : '';
            }
            $rev .= sprintf($fmt, $name, $type, $key, $checked, $val) . $separation;
        }

        echo $rev;
    }

    public function dateTimeFormat($time, $format = 'Y-m-d H:i:s')
    {
        echo date($format, $time);
    }

    public function setDateTimeFormat($format)
    {
        if (isset($format['date']))
            $this->dateFormat = $format['date'];
        if (isset($format['time']))
            $this->timeFormat = $format['time'];
        return $this;
    }

    public function dateFormat($date)
    {
        $this->loadLocalization();
        echo Localization::dateFormat($date);
    }

    public function timeFormat($time)
    {
        $this->loadLocalization();
        echo Localization::timeFormat($time);
    }

    public function currencyFormat($c, $decimal = false)
    {
        $this->loadLocalization();
        echo Localization::currencyFormat($c, $decimal);
    }

    private function loadLocalization()
    {
        // require_module(PROJECT . '.' . 'Localization');
    }

}
?>