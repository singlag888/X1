<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

/**
 * 控制器：管理员组别管理
 */
class floatController extends sscAdminController
{
    //方法概览
    public $titles = array(
        'index' => '浮窗管理',
        'edit' => '浮窗编辑',
        'flush' => '清除图片',
    );

    public function init($init = 0)
    {
        parent::init(parent::INIT_TEMPLATE | parent::INIT_SESSION);
    }

    /**
     * 展示
     */
    public function index()
    {
        $floatConfig = (new float())->getConfig();
        $floatConfig['wechat_qr'] && $floatConfig['wechat_qr'] = $this->_thumbImg($floatConfig['wechat_qr']);
        $floatConfig['left_img'] && $floatConfig['left_img'] = $this->_thumbImg($floatConfig['left_img']);
        $floatConfig['right_img'] && $floatConfig['right_img'] = $this->_thumbImg($floatConfig['right_img']);

        self::$view->setVar('floatConfig', $floatConfig);
        self::$view->render('float_config');
    }

    /**
     * 编辑
     */
    public function edit()
    {
        $float = new float();
        $floatConfig = $float->getConfig();

        $data = [
            'qq_number' => $this->request->getPost('qq_number', 'trim'),
            'email_address' => $this->request->getPost('email_address', 'trim'),
            'wechat_number' => $this->request->getPost('wechat_number', 'trim'),
            'service_url' => $this->request->getPost('service_url', 'trim'),
        ];

        /* 左边 */
        $leftHeight = $this->request->getPost('left_height', 'array');
        $leftUrl = $this->request->getPost('left_url', 'array');
        $leftFakeUrl = $this->request->getPost('left_fake_url', 'array');
        $leftUrlTarget = $this->request->getPost('left_url_target', 'array');

        // 带着key正序排列
        asort($leftHeight);

        $leftTarget = [];
        if (count($leftHeight) > 1 || current($leftHeight)) {
            foreach ($leftHeight as $key => $item) {
                // 判断排除空数据
                if (strlen($item) > 0) { // 可以留空就不加这个  && (strlen($leftUrl[$key]) > 0 || strlen($leftFakeUrl[$key] > 0))
                    $leftTarget[] = [
                        'height' => intval($item),
                        'url' => $leftUrl[$key],
                        'fake_url' => $leftFakeUrl[$key],
                        'target' => $leftUrlTarget[$key],
                    ];
                }
            }
        }

        $data['left_target'] = $leftTarget;

        /* 右边 */
        $rightHeight = $this->request->getPost('right_height', 'array');
        $rightUrl = $this->request->getPost('right_url', 'array');
        $rightFakeUrl = $this->request->getPost('right_fake_url', 'array');
        $rightUrlTarget = $this->request->getPost('right_url_target', 'array');

        // 带着key正序排列
        asort($rightHeight);

        $rightTarget = [];
        if (count($rightHeight) > 1 || current($rightHeight)) {
            foreach ($rightHeight as $key => $item) {
                // 判断排除空数据
                if (strlen($item) > 0) {// 可以留空就不加这个  && (strlen($rightUrl[$key]) > 0 || strlen($rightFakeUrl[$key] > 0))
                    $rightTarget[] = [
                        'height' => intval($item),
                        'url' => $rightUrl[$key],
                        'fake_url' => $rightFakeUrl[$key],
                        'target' => $rightUrlTarget[$key],
                    ];
                }
            }
        }

        $data['right_target'] = $rightTarget;

        if ($_FILES) {
            $up = new upload();
            $up->set_thumb(100, 80);
            $fs = $up->execute();

            foreach ($fs as $name => &$item) {
                if ($item['flag'] != 1) {

                    // 这里返回报错了就
                    $error = '上传' . $item['name'] . '时出错。';
                    $error .= $up->getError($item['flag']);
                    response([$error], 'MSG');

                    $data[$name] = '';
                    continue;
                }
                $data[$name] = $item['dir'] . $item['name'];
                // 上传七牛
                $qiniu = new uptoqiniu($item['name'], $item['dir']);
                $qiniu->upload();

                //>>上传到阿里云存储
                $aliyun = new uploadaliyun($item['name'], $item['dir']);
                if(($result = $aliyun->upload()) !== true){
                    showMsg($result);exit;
                }
                // 如果上传了新图则删除旧图
                is_file($floatConfig[$name]) && unlink($this->_thumbImg($floatConfig[$name]));
            }
        }

        // 这一步合并很重要
        $data = array_merge($floatConfig, $data);
        $result = $float->update($data);

        if ($result === false) {
            response(['更新失败'], 'MSG');
        }

        response(['更新成功'], 'MSG');
    }

    /**
     * 清除图片
     */
    public function flush()
    {
        $field = $this->request->getGet('field', 'trim');
        $float = new float();
        $floatConfig = $float->getConfig();

        // 先删除图片
        is_file($floatConfig[$field]) && unlink($this->_thumbImg($floatConfig[$field]));

        // 清除图片数据
        $floatConfig[$field] = '';
        // 清除链接数据
        if (in_array($field, ['left_img', 'right_img'])) {
            list($direction,) = explode('_', $field);
            $floatConfig[$direction . '_target'] = [];
        }

        $result = $float->update($floatConfig);

        if ($result === false) {
            response(['更新失败'], 'MSG');
        }

        response(['更新成功'], 'MSG');
    }

    private function _thumbImg($srcImg)
    {
        if ($srcImg == '') {
            return $srcImg;
        }
        preg_match('@.*(images_fh.*)$@', $srcImg, $macth);
        if (isset($macth[1])) {
            $tmp = explode('/', $macth[1]);
            $srcName = $tmp[count($tmp) - 1];
            unset($tmp[count($tmp) - 1]);
            $newImg = implode('/', $tmp);
            $img = $newImg . '/thumb_' . $srcName;

            //return $newImg.'/thumb_'.$srcName;
            //由于系统jpeg类库问题先将jpg图片的缩略图转换成png格式
            /************* snow 搞事情嘛转什么嘛*********************************/
//            if (preg_match('@^.*jpg$@', $img)) {
//                $img = substr($img, 0, strrpos($img, '.') + 1) . 'png';
//            }
            /************* snow 搞事情嘛转什么嘛*********************************/
        } else {
            $img = '';
        }

        return $img;
    }
}