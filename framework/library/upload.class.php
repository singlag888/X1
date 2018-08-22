<?php

class upload
{
    var $dir; //附件存放物理目录
    var $time; //自定义文件上传时间
    var $allow_types; //允许上传附件类型
    var $maxsize; //最大允许文件大小，单位为KB
    var $thumb_width; //缩略图宽度
    var $thumb_height; //缩略图高度
    var $watermark_file; //水印图片地址
    var $watermark_pos; //水印位置
    var $watermark_trans;//水印透明度

    //构造函数
    //$types : 允许上传的文件类型 , $maxsize : 允许大小 , $time : 自定义上传时间
    function __construct($types = 'jpg|png|gif', $maxsize = 300, $time = '')
    {
        ini_set("gd.jpeg_ignore_warning", true);  //>运行图片上传时,动态设置 忽略jpeg错误.
        $this->allow_types = explode('|', $types);
        $this->maxsize = $maxsize * 1024;
        $this->time = $time ? $time : time();
    }

    public function getError($errCode)
    {
        $str = '';
        switch ($errCode) {
            case 4:
                $str .= '没有文件被上传';
                break;
            case 3:
                $str .= '文件只被部分上传';
                break;
            case 2:
                $str .= '上传文件超过了HTML表单中MAX_FILE_SIZE选项指定的值';
                break;
            case 1:
                $str .= '上传文件超过了php.ini 中upload_max_filesize选项的值';
                break;
            case -1:
                $str .= '不支持的图片类型';
                break;
            case -2:
                $str .= '文件过大，上传文件不能超过' . ($this->maxsize / 1024) . 'KB';
                break;
            case -3:
                $str .= '上传失败';
                break;
            case -4:
                $str .= '建立存放上传文件目录失败，请重新指定上传目录';
                break;
            case -5:
                $str .= '必须指定上传文件的路径';
                break;
            default:
                $str .= '未知错误';
        }
        return $str;
    }

    //用来检查文件上传路径
    private function checkFilePath()
    {
        if (empty($this->filepath)) {
            $this->setOption('errorNum', -5);
            return false;
        }
        if (!file_exists($this->filepath) || !is_writable($this->filepath)) {
            if (!@mkdir($this->filepath, 0777)) {
                $this->setOption('errorNum', -4);
                return false;
            }
        }
        return true;
    }

    //用来检查文件上传的大小
    private function checkFileSize()
    {
        if ($this->fileSize > $this->maxsize) {
            $this->setOPtion('errorNum', '-2');
            return false;
        } else {
            return true;
        }
    }

    //用于检查文件上传类型
    private function checkFileType()
    {
        if (in_array(strtolower($this->fileType), $this->allowtype)) {
            return true;
        } else {
            $this->setOption('errorNum', -1);
            return false;
        }
    }
    //设置并创建文件具体存放的目录
    //$basedir : 基目录，必须为物理路径
    //$filedir : 自定义子目录，可用参数{y}、{m}、{d}
    function set_dir($basedir, $filedir = '')
    {
        $dir = $basedir;
        !is_dir($dir) && @mkdir($dir, 0777);
        if (!empty($filedir)) {
            $filedir = str_replace(array('{y}', '{m}', '{y}'), array(date('Y', $this->time), date('m', $this->time), date('d', $this->time)), strtolower($filedir));
            $dirs = explode('/', $filedir);
            foreach ($dirs as $d) {
                $dir .= $d . '/';
                $this->directory($dir);
            }
        }

        $this->dir = $dir;
    }

    function directory($dir)
    {
        if (is_dir($dir) || @mkdir($dir, 0777)) {
            // echo $dir."创建成功<br>";
        } else {
            $dirArr = explode('/', $dir);
            array_pop($dirArr);
            $newDir = implode('/', $dirArr);
            $this->directory($newDir);
            @mkdir($dir, 0777);
        }
    }
    //图片缩略图设置，如果不生成缩略图则不用设置
    //$width : 缩略图宽度 , $height : 缩略图高度
    function set_thumb($width = 0, $height = 0)
    {
        $this->thumb_width = $width;
        $this->thumb_height = $height;
    }
    //图片水印设置，如果不生成添加水印则不用设置
    //$file : 水印图片 , $pos : 水印位置 , $trans : 水印透明度
    function set_watermark($file, $pos = 6, $trans = 80)
    {
        $this->watermark_file = $file;
        $this->watermark_pos = $pos;
        $this->watermark_trans = $trans;
    }

    /*----------------------------------------------------------------
    执行文件上传，处理完返回一个包含上传成功或失败的文件信息数组，
    其中：name 为文件名，上传成功时是上传到服务器上的文件名，上传失败则是本地的文件名
          dir 为服务器上存放该附件的物理路径，上传失败不存在该值
          size 为附件大小，上传失败不存在该值
          flag 为状态标识，1表示成功，-1表示文件类型不允许，-2表示文件大小超出
    ----------------------------------------------------------------- */
    function execute()
    {
        $files = array(); //成功上传的文件信息
        $keys = array_keys($_FILES);
        foreach ($keys as $key) {
            if (!$_FILES[$key]['name']) continue;
            $per = explode('_', $key);
            $project = $per[0] == 'm' ? 'sscmobile' : 'ssc';

            $this->set_dir(ROOT_PATH . $project . '/images_fh/upload/', '{y}/{m}');

            $fileext = $this->fileext($_FILES[$key]['tmp_name']); //获取文件扩展名
            $filename = $this->time . mt_rand(100, 999) . '.' . $fileext; //生成文件名
            $filedir = $this->dir; //附件实际存放目录
            $filesize = $_FILES[$key]['size']; //文件大小

            //文件类型不允许
            if (!in_array($fileext, $this->allow_types)) {
                $files[$key]['name'] = $_FILES[$key]['name'];
                $files[$key]['flag'] = -1;
                continue;
            }
            //文件大小超出
            if ($filesize > $this->maxsize) {
                $files[$key]['name'] = $_FILES[$key]['name'];
                $files[$key]['flag'] = -2;
                continue;
            }
            $files[$key]['name'] = $filename;
            $files[$key]['dir'] = $filedir;
            $files[$key]['size'] = $filesize;
            //保存上传文件并删除临时文件
            if (is_uploaded_file($_FILES[$key]['tmp_name'])) {

                move_uploaded_file($_FILES[$key]['tmp_name'], $filedir . $filename);

                @unlink($_FILES[$key]['tmp_name']);
                $files[$key]['flag'] = 1;
                //对图片进行加水印和生成缩略图
                if (in_array($fileext, $this->allow_types)) {
                    if ($this->thumb_width) {
                        $this->set_dir(ROOT_PATH . 'sscadmin/images_fh/upload/', '{y}/{m}');
                        $thumbfiledir = $this->dir;
                        $srcFile = $filedir . $filename;
                        $thumbFile = $thumbfiledir . 'thumb_' . $filename;
                        //由于系统jpeg类库问题先将jpg图片的缩略图转换成png格式
                        if (preg_match('@^.*jpg_mpeg$@', $srcFile)) {
                            $tmp_file = substr($srcFile, 0, strrpos($srcFile, '.') + 1) . 'png';
                            exec("convert {$srcFile} {$tmp_file}");
                            @unlink($srcFile);
                            $srcFile = $tmp_file;
                            $thumbFile = substr($thumbFile, 0, strrpos($thumbFile, '.') + 1) . 'png';
                        }

                        if ($this->create_thumb($srcFile, $thumbFile)) {
                            $files[$key]['thumb'] = 'thumb_' . $filename; //缩略图文件名
                            //由于系统jpeg类库问题先将jpg图片的缩略图转换成png格式
                            if (preg_match('@^.*jpg_mpeg$@', $files[$key]['thumb'])) {
                                $files[$key]['thumb'] = substr('thumb_' . $filename, 0, strrpos('thumb_' . $filename, '.') + 1) . 'png';
                            }
                        }
                    }
                    // $this->create_watermark($filedir.$filename);
                }
            }

            //由于系统jpeg类库问题先将jpg图片的缩略图转换成png格式
            if (preg_match('@^.*jpg_mpeg$@', $files[$key]['name'])) {
                $files[$key]['name'] = substr($files[$key]['name'], 0, strrpos($files[$key]['name'], '.') + 1) . 'png';
            }
        }

        return $files;
    }
    //创建缩略图,以相同的扩展名生成缩略图
    //$src_file : 来源图像路径 , $thumb_file : 缩略图路径
    function create_thumb($src_file, $thumb_file)
    {
        $t_width = $this->thumb_width;
        $t_height = $this->thumb_height;
        if (!file_exists($src_file)) return false;

        $src_info = getImageSize($src_file);
        //如果来源图像小于或等于缩略图则拷贝源图像作为缩略图
        if ($src_info[0] <= $t_width && $src_info[1] <= $t_height) {
            if (!copy($src_file, $thumb_file)) {
                return false;
            }
            return true;
        }
        //按比例计算缩略图大小
        if ($src_info[0] - $t_width > $src_info[1] - $t_height) {
            $t_height = ($t_width / $src_info[0]) * $src_info[1];
        } else {
            $t_width = ($t_height / $src_info[1]) * $src_info[0];
        }
        //取得文件扩展名
        $fileext = $this->fileext($src_file);
        switch ($fileext) {
            case 'jpg' :
                $src_img = @ImageCreateFromJPEG($src_file);
                if (!$src_img) {
                    $src_img = imagecreatefromstring(file_get_contents($src_file));
                }
                break;
            case 'png' :
                $src_img = ImageCreateFromPNG($src_file);
                break;
            case 'gif' :
                $src_img = ImageCreateFromGIF($src_file);
                break;
        }
        //创建一个真彩色的缩略图像
        $thumb_img = @ImageCreateTrueColor($t_width, $t_height);
        //ImageCopyResampled函数拷贝的图像平滑度较好，优先考虑
        if (function_exists('imagecopyresampled')) {
            @ImageCopyResampled($thumb_img, $src_img, 0, 0, 0, 0, $t_width, $t_height, $src_info[0], $src_info[1]);
        } else {
            @ImageCopyResized($thumb_img, $src_img, 0, 0, 0, 0, $t_width, $t_height, $src_info[0], $src_info[1]);
        }
        //生成缩略图
        switch ($fileext) {
            case 'jpg' :
                ImageJPEG($thumb_img, $thumb_file);
                break;
            case 'gif' :
                ImageGIF($thumb_img, $thumb_file);
                break;
            case 'png' :
                ImagePNG($thumb_img, $thumb_file);
                break;
        }
        //销毁临时图像
        @ImageDestroy($src_img);
        @ImageDestroy($thumb_img);
        return true;
    }
    //为图片添加水印
    //$file : 要添加水印的文件
    function create_watermark($file)
    {
        //文件不存在则返回
        if (!file_exists($this->watermark_file) || !file_exists($file)) return;
        if (!function_exists('getImageSize')) return;

        //检查GD支持的文件类型
        $gd_allow_types = array();
        if (function_exists('ImageCreateFromGIF')) $gd_allow_types['image/gif'] = 'ImageCreateFromGIF';
        if (function_exists('ImageCreateFromPNG')) $gd_allow_types['image/png'] = 'ImageCreateFromPNG';
        if (function_exists('ImageCreateFromJPEG')) $gd_allow_types['image/jpeg'] = 'ImageCreateFromJPEG';
        //获取文件信息
        $fileinfo = getImageSize($file);
        $wminfo = getImageSize($this->watermark_file);
        if ($fileinfo[0] < $wminfo[0] || $fileinfo[1] < $wminfo[1]) return;
        if (array_key_exists($fileinfo['mime'], $gd_allow_types)) {
            if (array_key_exists($wminfo['mime'], $gd_allow_types)) {
                //从文件创建图像
                $temp = $gd_allow_types[$fileinfo['mime']]($file);
                $temp_wm = $gd_allow_types[$wminfo['mime']]($this->watermark_file);
                //水印位置
                switch ($this->watermark_pos) {
                    case 1 : //顶部居左
                        $dst_x = 0;
                        $dst_y = 0;
                        break;
                    case 2 : //顶部居中
                        $dst_x = ($fileinfo[0] - $wminfo[0]) / 2;
                        $dst_y = 0;
                        break;
                    case 3 : //顶部居右
                        $dst_x = $fileinfo[0];
                        $dst_y = 0;
                        break;
                    case 4 : //底部居左
                        $dst_x = 0;
                        $dst_y = $fileinfo[1];
                        break;
                    case 5 : //底部居中
                        $dst_x = ($fileinfo[0] - $wminfo[0]) / 2;
                        $dst_y = $fileinfo[1];
                        break;
                    case 6 : //底部居右
                        $dst_x = $fileinfo[0] - $wminfo[0];
                        $dst_y = $fileinfo[1] - $wminfo[1];
                        break;
                    default : //随机
                        $dst_x = mt_rand(0, $fileinfo[0] - $wminfo[0]);
                        $dst_y = mt_rand(0, $fileinfo[1] - $wminfo[1]);
                }
                if (function_exists('ImageAlphaBlending')) ImageAlphaBlending($temp_wm, True); //设定图像的混色模式
                if (function_exists('ImageSaveAlpha')) ImageSaveAlpha($temp_wm, True); //保存完整的 alpha 通道信息
                //为图像添加水印
                if (function_exists('imageCopyMerge')) {
                    ImageCopyMerge($temp, $temp_wm, $dst_x, $dst_y, 0, 0, $wminfo[0], $wminfo[1], $this->watermark_trans);
                } else {
                    ImageCopyMerge($temp, $temp_wm, $dst_x, $dst_y, 0, 0, $wminfo[0], $wminfo[1]);
                }
                //保存图片
                switch ($fileinfo['mime']) {
                    case 'image/jpeg' :
                        @imageJPEG($temp, $file);
                        break;
                    case 'image/png' :
                        @imagePNG($temp, $file);
                        break;
                    case 'image/gif' :
                        @imageGIF($temp, $file);
                        break;
                }
                //销毁零时图像
                @imageDestroy($temp);
                @imageDestroy($temp_wm);
            }
        }
    }

    //获取文件扩展名
    function fileext($file)
    {
        $file_dimensions = getimagesize($file);
        $file_type = strtolower($file_dimensions['mime']);

        switch ($file_type) {
            case 'image/jpeg' :
            case 'image/pjpeg' :
                return 'jpg';
            case 'image/png' :
                return 'png';
            case 'image/gif' :
                return 'gif';
        }
    }
}

?>