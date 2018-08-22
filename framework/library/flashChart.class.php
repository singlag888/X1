<?php

/**
 * flashChart 统计图类
 * 实现功能:
 *    根据数据表现形式, 输出 XML 形式数据文档
 *    中文数据需用 iconv 转码
 *
 * TODO: 未完成的统计图
 *   统计概况- 饼图+曲线图
 *   时段分析- 今日统计, 竖向柱状图+同期对比
 *   来路分析- 来路域名 饼图
 *   地区分布- 中国地图
 *   插件安装- 横向柱状图
 *
 * 使用范例:
 * 		$fc = new flashChart();
 * 	    $fc->addLabels( array( '0:00', '1:00', '2:00', '3:00', '4:00' ) );
 * 	    $fc->addData( array('tooltext1'=>1,'tooltext2'=>2,'tooltext3'=>3,'tooltext4'=>4,'tooltext5'=>5,'tooltext6'=>6), 'line1' );
 * 	    $fc->addData( array('tooltext'=>11,23,35,46,57,68), 'line2', 'FF0000');
 * 	    $fc->addData( array(19,22,36,84,53,96), 'line3', '3BD12E');
 * 	    $fc->display();
 *
 * @author	William
 */
class flashChart
{

    private $chart = array();
    private $labels = array();  // 标签数组, 共多少个数据节点 [位于横坐标]
    private $datas = array();  // 数据数组, 2维 $datas[0] =>
                                            // array (
                                            //        'seriesname' => 'newData',        // 线条名
                                            //        'anchorbordercolor' => '0033CC',  // 线条节点圈颜色
                                            //        'color'      => '0033CC'          // 线条颜色
                                            //        datas = array( '[tooltext]'=> value )
                                            // )
    public function __construct()
    {
        $this->init();
    }

    /**
     * 增加新标签
     * @param array $arr  array( '0:00', '1:00', '2:00', '3:00', '4:00' );
     * @author William
     */
    public function addLabels($arr = array())
    {
        $this->labels = $arr;
    }

    /**
     * 输出 XML 结果
     * @author William
     */
    public function display()
    {
        $sCharts = '';
        foreach ($this->chart as $k => $v) {
            $sCharts .= "$k='$v' ";
        }
        $out = "<chart $sCharts>\n";

        // step 02, 解析标签 labels
        if (!is_array($this->labels) || empty($this->labels)) {
            throw new exception2('labels is empty');
        }
        $labels = "<categories>\n";
        foreach ($this->labels as $v) {
            $labels .= "<category label='" . htmlspecialchars(@iconv('UTF-8', 'GB2312', $v)) . "' />\n";
        }
        $labels .= "</categories>\n\n";
        $out .= $labels;

        // step 03, 解析数据内容
        $str = '';
        if (is_array($this->datas) && !empty($this->datas)) {
            foreach ($this->datas as $datas) {
                //print_rr($datas);exit;
                $str .= '<dataset seriesName=\'' . $datas['seriesname']
                        . '\' color=\'' . $datas['color']
                        . '\' anchorBorderColor=\'' . $datas['anchorbordercolor'] . '\'>' . "\n";
                if (is_array($datas['datas']) && !empty($datas['datas'])) {
                    //print_rr($datas['datas']);
                    foreach ($datas['datas'] AS $k => $v) {
                        if (!is_numeric($k)) { // 数组键值不为数字时, 则判断为 tooltext, 鼠标浮动说明
                            $str .= '<set value=\'' . $v . '\' tooltext=\'' . $k . '\' />';
                        }
                        else {
                            $str .= '<set value=\'' . $v . '\' />';
                        }
                        $str .= "\n";
                    }
                }
                $str .= "</dataset>\n\n";
            }
        }
        $out .= $str;

        $out .= "\n<styles><definition><style name='myLegendFont' type='font' size='12' /></definition>" .
                "<application><apply toObject='Legend' styles='myLegendFont' /></application></styles>";
        $out .= "</chart>";
//logdump($out);
        $out = iconv('UTF-8', 'GB2312', $out);
        echo $out;
        exit;
    }

    /**
     * 增加新数据项
     *
     * @param array  $data   ['tooltext'] => value
     * @param string $name
     * @param string $lineColor   FF0000 | 3BD12E |
     * @param string $borderColor
     * @author William
     */
    public function addData($data, $name='NewData', $lineColor='0033CC', $borderColor='0033CC')
    {
        if (!is_array($data) || empty($data)) {
            throw new exception2('data isnot array');
        }
        if ($borderColor == '0033CC' && $lineColor != '0033CC') {
            $borderColor = $lineColor;
        }
        $tmp = array();
        $tmp['seriesname'] = htmlspecialchars(@iconv('UTF-8', 'GB2312', $name));   // 线条名字 <dataset seriesname="xxx" ..
        $tmp['color'] = $lineColor;         // 线条颜色
        $tmp['anchorbordercolor'] = $borderColor;    // 线条圆圈颜色
        $tmp['datas'] = $data;
        $this->datas[] = $tmp;

        return true;
    }

    /**
     * 设置私有属性 $chart 的值
     * @param string $key
     * @param string $value
     * @author William
     */
    public function setChart($key, $value)
    {
        if (isset($this->chart[$key])) {
            $this->chart[$key] = $value;
        }
    }

    /**
     * 初始化参数数组
     * @author William
     */
    public function init()
    {
        $this->chart = array
            (
            'showfcmenuitem' => 0, // 未知
            'linethickness' => '2', // SWF 曲线宽度
            'showvalues' => '0', // 是否在SWF中直接显示变量值(默认浮动显示)
            'anchorradius' => '4', // 数据节点圆圈半径
            'divlinealpha' => '20', // 背景方格的透明度(百分比)
            'divlinecolor' => 'CC3300', // 背景方格线颜色
            'divlineisdashed' => '1', // 背景方格的边线是否为虚线
            'showalternatehgridcolor' => '1', // 背景方格(行)是否颜色交替显示
            'alternatehgridalpha' => '5', // (行)交替交替颜色的透明度
            'alternatehgridcolor' => 'CC3300', // 交替行颜色值
            'shadowalpha' => '40', // 无效?
            'labelstep' => '1', // [横] 坐标值显示时的跳跃步伐
            'numvdivlines' => '25', // 背景方格显示的数量, 从0开始计数, 25意味着26格
            'showalternatevgridcolor' => '1', // 背景方格(列)是否颜色交替显示
            'chartsshowshadow' => '1', // 无效?
            'chartrightmargin' => '20', // SWF 图表, 与右的边距
            'charttopmargin' => '15', // SWF 图表, 与上的边距
            'chartleftmargin' => '0', // SWF 图表, 与左的边距
            'chartbotWilliammargin' => '3', // SWF 图表, 与下的边距
            'bgcolor' => 'FFFFFF', // 外圈(非绘图区) 的背景色
            'canvasborderthickness' => '1', // 外圈边框线宽度
            'showborder' => '0', // 无效?
            'legendborderalpha' => '0', // 图例区边框透明度
            'bgangle' => '360', // 无效?
            'showlegend' => '1', // 是否显示图例区
            'bordercolor' => 'DEF3F3', // 无效?
            'tooltipbordercolor' => 'cccc99', // 无效
            'canvaspadding' => '0', // 曲线图, 离两端的距离
            'tooltipbgcolor' => 'ffffcc', // 无效
            'legendShadow' => '0', // 图例区是否显示阴影
            'baseFontSize' => '12', // 数据节点上, 文字大小
            'canvasBorderAlpha' => '20', // 外边框透明度
            'outCnvbaseFontSize' => '10', // 周边文字大小
            'outCnvbaseFontColor' => '000000', // 周边文字颜色
            'numberScaleValue' => '10000,1,1,1000', // 数字格式
            'formatNumberScale' => '1', // 是否显示大写中文,例: 2.6千万
            'palette' => '2', //
            'numberScaleUnit' => ' , ,万,千万',
            'lineColor' => 'AFD8F8'               // 无效?
        );
    }

}

?>