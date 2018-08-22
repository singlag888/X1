<?php

/**
 * 生成验证码图象
 *
 * 功能描述：
 * ~~~~~~~~~~~~~~~~~~~~~
 *            -根据设置生成验证码图象
 *
 *
 * 该验证码程序采用OOP设计理念，灵活性比较强，可以通过选项设置：
 *        --验证码字符（包括数字，大小写字母）默认为数字
 *        --字符是否偏转以及字符是否彩色
 *        --生成验证码的字符长度，默认为4
 *        --验证码的字体信息（字符间隔，字体大小，第一个字符距离图像最左边的象素(px)，
 *            字符距离图像最上边的象素 (px)，字体文件的路径）
 *        --生成的图片信息（宽px，高px，图像类型 (选项: 'png', 'gif', 'wbmp', 'jpg') 默认为'png'）
 *        --干扰类型（干扰密度:normal,muchness,fewness，干扰类型:both, point, line）
 *        --背景色：R(0-255) G(0-255) B(0-255)   默认 255 255 255
 *        --前景色:即是字体颜色 ,如果设置了字符为彩色，则这个无效  R(0-255) G(0-255) B(0-255)   默认0 0 0
 *        注意：初始化时背景色和前景色是相反的，建议采用相反的颜色，不会产生瑕疵型干扰
 *
 *
 * 使用范例：
 * ..........................
 *        $validate = new validation();
 *        $validate->setImage(array('width'=>120,'height'=>30,'type'=>'png'));
 *        $validate->setCode( array('characters'=>'0-9,a-z,A-Z','length'=>4,'deflect'=>FALSE,'multicolor'=>FALSE) );
 *        $validate->setFont( array("space"=>10,"size"=>18,"left"=>10,"top"=>25,"file"=>'') );
 *        $validate->setMolestation( array("type"=>FALSE,"density"=>'fewness') );
 *        $validate->setBgColor( array('r'=>111,'g'=>155,'b'=>255) );
 *        $validate->setFgColor( array('r'=>0,'g'=>100,'b'=>144) );
 *        // 输出到浏览器
 *        $validate->paint();
 *
 *
 *
 */
class captcha
{
    /* 验证码字符设置 array 包括以下设置
     * characters    string  允许的字符 ，每个字符用,隔开
     * length        int     验证码长度
     * deflect       boolean 字符是否偏转
     * multicolor    boolean 字符是否彩色
     */

    private $codes = array();

    /* 字体信息
     *   space  int     字符间隔 (px)
     *   size   int     字体大小 (px)
     *   left   int     第一个字符距离图像最左边的象素 (px)
     *   top    int     字符距离图像最上边的象素 (px)
     *   file   string  字体文件的路径
     */
    private $fonts = array();

    /* 图像信息
     *   type   string  图像类型 (选项: 'png', 'gif', 'wbmp', 'jpg') 默认为'png'
     *   width  int     图像宽 (px)
     *   height int     图像高 (px)
     */
    private $images = array();

    /* 干扰信息
     *  type    string  干扰类型 (选项: false, 'point', 'line')
     *  density string  干扰密度 (选项: 'normal', 'muchness', 'fewness')
     */
    private $molestation = array();
    //背景色 (RGB)  r: 红色 (0 - 255) g: 绿色 (0 - 255) b: 蓝色 (0 - 255)
    private $bgColor = array();
    //前景色 (RGB)  r: 红色 (0 - 255) g: 绿色 (0 - 255) b: 蓝色 (0 - 255)
    private $fgColor = array();
    //字体文件默认路径
    private $fontDir = '';
    //生成的验证码字符信息(保存生成的验证码，用于验证输入匹配)
    private $verifyCode = '';
    //过期时间
    private $expire = 600;
    // 前缀
    private $redisPrefix = 'captcha_';

    /*
     * 构造函数
     */
    function __construct()
    {
        $this->fontDir = FRAMEWORK_PATH . 'fonts/';
        $this->setCode();
        $this->setMolestation();
        $this->setImage();
        $this->setFont();
        $this->setBgColor();
    }

    /**
     * 获取产生的authcode
     * @return string
     */
    public function getcode()
    {
        return $this->verifyCode;
    }

    /**
     * 绘制图像
     *
     * @access  public
     * @param   string $sFilename 文件名, 留空表示输出到浏览器
     * @return  void
     */
    public function paint($filename = '')
    {
        // 创建图像
        $rsIm = imagecreatetruecolor($this->images['width'], $this->images['height']);

        // 设置图像背景
        $temp_bgColor = imagecolorallocate($rsIm, $this->bgColor['r'], $this->bgColor['g'], $this->bgColor['b']);
        imagefilledrectangle($rsIm, 0, 0, $this->images['width'], $this->images['height'], $temp_bgColor);

        // 生成验证码相关信息
        $temp_code = $this->generateCode();

        // 向图像中写入字符
        $temp_num = count($temp_code);
        $temp_currentLeft = $this->fonts['left'];
        $temp_currentTop = $this->fonts['top'];
        $temp_theCode = '';
        for ($i = 0; $i < $temp_num; $i++) {
            $temp_fontCcolor = imagecolorallocate(
                $rsIm,
                $temp_code[$i]['color']['r'],
                $temp_code[$i]['color']['g'],
                $temp_code[$i]['color']['b']
            );
            //logdump($this->fonts['file']);
            //如果提示Warning: imagettftext() [<a href='function.imagettftext'>function.imagettftext</a>]: Problem loading glyph
            // 在win下字体文件明明存在，此时把apache重启下就可以了。
            imagettftext($rsIm, $this->fonts['size'], $temp_code[$i]['angle'],
                $temp_currentLeft, $temp_currentTop, $temp_fontCcolor,
                $this->fonts['file'], $temp_code[$i]['char']);

            $temp_currentLeft += $this->fonts['size'] + $this->fonts['space'];

            $temp_theCode .= $temp_code[$i]['char'];
        }
        $this->verifyCode = $temp_theCode; //保存authcode
        // 绘制图像干扰
        $this->paintMolestation($rsIm);

        // 输出 图象到文件
        if (isset($filename) && $filename != '') {
            $this->images['func']($rsIm, $filename . $this->images['type']);
        } else {
            header("Cache-Control: no-cache, must-revalidate");
            header("Content-type: " . $this->images['mime']);
            $this->images['func']($rsIm);
        }

        imagedestroy($rsIm);
        $this->recordHook();
    }

    /**
     * 记录钩子,用来实现存储验证码.需要修改存储方式只需编辑此方法与验证方法即可.
     * id用来指定不同验证码,现用sessionId来标识到浏览器即可.如有需求,可在paint()方法处传入id值.
     * @param string $id
     */
    private function recordHook($id = '')
    {
        $id || $id = session_id();

        /**
         * @$GLOBALS['redis'] \redisCache
         */
        $GLOBALS['redis']->setex($this->redisPrefix . $id, $this->expire, $this->verifyCode);
    }

    /**
     * 验证码统一验证入口
     * @param string $code
     * @param string $id
     * return bool
     */
    public function verifying($code, $id = '')
    {
        $id || $id = session_id();
        /**
         * @$GLOBALS['redis'] \redisCache
         */
        $verifyCode = $GLOBALS['redis']->get($this->redisPrefix . $id);
        $result = $verifyCode && $code == $verifyCode;
        // $result && $GLOBALS['redis']->del($id);
        return $result;
    }

    /**
     * app删除redis验证码
     * @param string $id
     * @return mixed
     */
    public function delVerifyCode($id=''){
        $id || $id = session_id();
        return $GLOBALS['redis']->del($this->redisPrefix . $id);
    }

    /**
     * 生成随机验证码
     *
     * @access  private
     * @return  array  生成的验证码
     */
    private function generateCode()
    {// 创建允许的字符串
        $temp_characters = explode(',', $this->codes['characters']);
        $temp_num = count($temp_characters);
        for ($i = 0; $i < $temp_num; $i++) {
            if (substr_count($temp_characters[$i], '-') > 0) {//设定单个范围
                $temp_characterRange = explode('-', $temp_characters[$i]);
                for ($j = ord($temp_characterRange[0]); $j <= ord($temp_characterRange[1]); $j++) {
                    $temp_arrayAllow[] = chr($j);
                }
            } else {
                $temp_arrayAllow[] = $temp_characters[$i];
            }
        }
        $temp_index = 0;
        while (list($key, $val) = each($temp_arrayAllow)) {
            $array_allow_tmp[$temp_index] = $val;
            $temp_index++;
        }
        $temp_arrayAllow = $array_allow_tmp;

        // 生成随机字符串
        //mt_srand((double)microtime() * 1000000); //播种随机数
        $temp_code = array();
        $temp_index = 0;
        $i = 0;
        while ($i < $this->codes['length']) {
            $temp_index = mt_rand(0, count($temp_arrayAllow) - 1);
            $temp_code[$i]['char'] = $temp_arrayAllow[$temp_index];
            if ($this->codes['deflect']) {
                $temp_code[$i]['angle'] = mt_rand(-30, 30);
            } else {
                $temp_code[$i]['angle'] = 0;
            }
            if ($this->codes['multicolor']) {
                $temp_code[$i]['color']['r'] = mt_rand(0, 255);
                $temp_code[$i]['color']['g'] = mt_rand(0, 255);
                $temp_code[$i]['color']['b'] = mt_rand(0, 255);
            } else {
                $temp_code[$i]['color']['r'] = $this->fgColor['r'];
                $temp_code[$i]['color']['g'] = $this->fgColor['g'];
                $temp_code[$i]['color']['b'] = $this->fgColor['b'];
            }
            $i++;
        }
        return $temp_code;
    }

    /**
     * 绘制图像干扰
     *
     * @access  private
     * @param   resource $rsIm 图像资源
     * @return  void
     */
    private function paintMolestation(&$rsIm)
    {
        // 总象素
        $temp_numOfPels = ceil($this->images['width'] * $this->images['height'] / 5);
        switch ($this->molestation['density']) {
            case 'fewness':
                $temp_density = ceil($temp_numOfPels / 3);
                break;
            case 'muchness':
                $temp_density = ceil($temp_numOfPels / 3 * 2);
                break;
            case 'normal':
                $temp_density = ceil($temp_numOfPels / 2);
                break;
            default:
                $temp_density = ceil($temp_numOfPels / 2);
                break;
        }

        switch ($this->molestation['type']) {
            case 'point':
                $this->paintPoints($rsIm, $temp_density);
                break;
            case 'line':
                $temp_density = ceil($temp_density / 30);
                $this->paintLines($rsIm, $temp_density);
                break;
            case 'both':
                $temp_density = ceil($temp_density / 2);
                $this->paintPoints($rsIm, $temp_density);
                $temp_density = ceil($temp_density / 30);
                $this->paintLines($rsIm, $temp_density);
                break;
            default:
                break;
        }
    }

    /**
     * 画点
     *
     * @access  private
     * @param   resource $rsIm 图像资源
     * @param   int $iQuantity 点的数量（密度）
     * @return  void
     */
    private function paintPoints(&$rsIm, $iQuantity)
    {
        //mt_srand( (double)microtime()*1000000 );

        for ($i = 0; $i < $iQuantity; $i++) {
            $temp_randcolor = imagecolorallocate($rsIm, mt_rand(0, 255),
                mt_rand(0, 255), mt_rand(0, 255));

            imagesetpixel($rsIm, mt_rand(0, $this->images['width']),
                mt_rand(0, $this->images['height']), $temp_randcolor);
        }
    }

    /**
     * 画线
     *
     * @access  private
     * @param   resource $rsIm 图像资源
     * @param   int $iQuantity 点的数量（密度）
     * @return  void
     */
    private function paintLines(&$rsIm, $iQuantity)
    {
        //mt_srand( (double)microtime()*1000000 );

        for ($i = 0; $i < $iQuantity; $i++) {
            $temp_randcolor = imagecolorallocate($rsIm, mt_rand(0, 255),
                mt_rand(0, 255), mt_rand(0, 255));

            imageline($rsIm, mt_rand(0, $this->images['width']),
                mt_rand(0, $this->images['height']),
                mt_rand(0, $this->images['width']),
                mt_rand(0, $this->images['height']), $temp_randcolor);
        }
    }

    /**
     * 设置验证码
     *
     * @access  public
     * @param   array $codes 字符信息
     * characters    string  允许的字符
     * length        int     验证码长度
     * deflect       boolean 字符是否偏转
     * multicolor    boolean 字符是否彩色
     * @return  void
     */
    public function setCode($codes = '')
    {
        if (is_array($codes)) {//用户指定设置
            if (!isset($codes['characters']) || !is_string($codes['characters'])) {
                $codes['characters'] = '0-9';
            }
            if (!isset($codes['length']) || !(is_integer($codes['length']) || $codes['length'] <= 0)) {
                $codes['length'] = 4;
            }
            if (!isset($codes['deflect']) || !is_bool($codes['deflect'])) {
                $codes['deflect'] = TRUE;
            }
            if (!isset($codes['multicolor']) || !is_bool($codes['multicolor'])) {
                $codes['multicolor'] = FALSE;
            }
        } else {//默认设置
            $codes = array('characters' => '0-9', 'length' => 4, 'deflect' => TRUE, 'multicolor' => FALSE);
        }
        $this->codes = $codes;
    }

    /**
     * 设置字体信息
     *
     * @access  public
     * @param   array $fonts 字体信息
     *   space  int     字符间隔 (px)
     *   size   int     字体大小 (px)
     *   left   int     第一个字符距离图像最左边的象素 (px)
     *   top    int     字符距离图像最上边的象素 (px)
     *   file   string  字体文件的路径
     * @return  void
     */
    public function setFont($fonts = '')
    {
        if (is_array($fonts)) {//用户指定设置
            if (!isset($fonts['space']) || !is_integer($fonts['space']) || $fonts['space'] < 0) {
                $fonts['space'] = 5;
            }
            if (!isset($fonts['size']) || !is_integer($fonts['size']) || $fonts['size'] < 0) {
                $fonts['size'] = 12;
            }
            if (!isset($fonts['left']) || !is_integer($fonts['left']) || $fonts['left'] < 0 ||
                $fonts['left'] > $this->images['width']
            ) {
                $fonts['left'] = 5;
            }
            if (!isset($fonts['top']) || !is_integer($fonts['top']) || $fonts['top'] < 0 ||
                $fonts['top'] > $this->images['height']
            ) {
                $fonts['top'] = $this->images['height'] - 5;
            }
            if (!isset($fonts['file']) || !file_exists($fonts['file'])) {
                $fonts['file'] = $this->fontDir . 'arial.ttf';
            }
        } else {//默认设置
            $fonts = array('space' => 5, 'size' => 12, 'left' => 5,
                'top' => 15,
                'file' => $this->fontDir . 'alpha_thin.ttf');
        }
        $this->fonts = $fonts;
    }

    /**
     * 设置图像信息
     *
     * @access  public
     * @param   array $images 图像信息
     *   type   string  图像类型 (选项: 'png', 'gif', 'wbmp', 'jpg') 默认为'png'
     *   width  int     图像宽 (px)
     *   height int     图像高 (px)
     * @return  void
     */
    public function setImage($images = '')
    {
        if (is_array($images)) {
            if (!isset($images['width']) || !is_integer($images['width']) || $images['width'] <= 0) {
                $images['width'] = 70;
            }
            if (!isset($images['height']) || !is_integer($images['height']) || $images['height'] <= 0) {
                $images['height'] = 20;
            }
            if (!isset($images['type'])) {
                $images['type'] = 'png';
            }
            $temp_information = $this->getImageType($images['type']);
            if (is_array($temp_information)) {
                $images['mime'] = $temp_information['mime'];
                $images['func'] = $temp_information['func'];
            } else {
                $images['type'] = 'png';
                $temp_information = $this->getImageType('png');
                $images['mime'] = $temp_information['mime'];
                $images['func'] = $temp_information['func'];
            }
        } else {
            $temp_information = $this->getImageType('png');
            $images = array(
                'type' => 'png',
                'mime' => $temp_information['mime'],
                'func' => $temp_information['func'],
                'width' => 70,
                'height' => 20);
        }
        $this->images = $images;
    }

    /**
     * 获取图像类型
     *
     * @access  private
     * @param   string $extension 扩展名
     * @return  [mixed] 错误时返回 false
     */
    private function getImageType($sExtension)
    {
        $temp_information = array();
        switch (strtolower($sExtension)) {
            case 'png':
                $temp_information['mime'] = image_type_to_mime_type(IMAGETYPE_PNG);
                $temp_information['func'] = 'imagepng';
                break;
            case 'gif':
                $temp_information['mime'] = image_type_to_mime_type(IMAGETYPE_GIF);
                $temp_information['func'] = 'imagegif';
                break;
            case 'wbmp':
                $temp_information['mime'] = image_type_to_mime_type(IMAGETYPE_WBMP);
                $temp_information['func'] = 'imagewbmp';
                break;
            case 'jpg':
                $temp_information['mime'] = image_type_to_mime_type(IMAGETYPE_JPEG);
                $temp_information['func'] = 'imagejpeg';
                break;
            case 'jpeg':
                $temp_information['mime'] = image_type_to_mime_type(IMAGETYPE_JPEG);
                $temp_information['func'] = 'imagejpeg';
                break;
            case 'jpe':
                $temp_information['mime'] = image_type_to_mime_type(IMAGETYPE_JPEG);
                $temp_information['func'] = 'imagejpeg';
                break;
            default:
                $temp_information = FALSE;
        }
        return $temp_information;
    }

    /**
     * 设置干扰信息
     *
     * @access  public
     * @param   array $molestation 干扰信息
     *  type    string  干扰类型 (选项: 'both', 'point', 'line')
     *  density string  干扰密度 (选项: 'normal', 'muchness', 'fewness')
     * @return  void
     */
    public function setMolestation($molestation = '')
    {
        if (is_array($molestation)) {
            if (!isset($molestation['type']) ||
                ($molestation['type'] != 'point' &&
                    $molestation['type'] != 'line' &&
                    $molestation['type'] != 'both')
            ) {
                $molestation['type'] = 'point';
            }
            if (!isset($molestation['density']) ||
                ($molestation['density'] != 'normal' &&
                    $molestation['density'] != 'muchness' &&
                    $molestation['density'] != 'fewness')
            ) {
                $molestation['density'] = 'normal';
            }
        } else {
            $molestation = array('type' => 'point', 'density' => 'normal');
        }
        $this->molestation = $molestation;
    }

    /**
     * 设置背景色
     *
     * @access  public
     * @param   array $aColor RGB 颜色
     *  r    int    红色
     *  g    int    绿色
     *  b    int    蓝色
     * @return  void
     */
    public function setBgColor($aColor = '')
    {
        if (is_array($aColor)) {
            if (!isset($aColor['r']) || !is_integer($aColor['r']) ||
                $aColor['r'] < 0 || $aColor['r'] > 255
            ) {
                $aColor['r'] = 255;
            }
            if (!isset($aColor['g']) || !is_integer($aColor['g']) ||
                $aColor['g'] < 0 || $aColor['g'] > 255
            ) {
                $aColor['g'] = 255;
            }
            if (!isset($aColor['b']) || !is_integer($aColor['b']) ||
                $aColor['b'] < 0 || $aColor['b'] > 255
            ) {
                $aColor['b'] = 255;
            }
        } else {
            $aColor = array('r' => 255, 'g' => 255, 'b' => 255);
        }
        $this->bgColor = $aColor;

        // 设置默认的前景色, 与背景色相反
        $temp_fgcolor = array(
            'r' => 255 - $this->bgColor['r'],
            'g' => 255 - $this->bgColor['g'],
            'b' => 255 - $this->bgColor['b']
        );
        $this->setFgColor($temp_fgcolor);
    }

    /**
     * 设置前景色
     *
     * @access  private
     * @param   array $aColor RGB 颜色
     * @return  void
     */
    public function setFgColor($aColor)
    {
        if (is_array($aColor)) {
            if (!isset($aColor['r']) || !is_integer($aColor['r']) ||
                $aColor['r'] < 0 || $aColor['r'] > 255
            ) {
                $aColor['r'] = 255;
            }
            if (!isset($aColor['g']) || !is_integer($aColor['g']) ||
                $aColor['g'] < 0 || $aColor['g'] > 255
            ) {
                $aColor['g'] = 255;
            }
            if (!isset($aColor['b']) || !is_integer($aColor['b']) ||
                $aColor['b'] < 0 || $aColor['b'] > 255
            ) {
                $aColor['b'] = 255;
            }
        } else {
            $aColor = array('r' => 0, 'g' => 0, 'b' => 0);
        }
        $this->fgColor = $aColor;
    }

}

?>