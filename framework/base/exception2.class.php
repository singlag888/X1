<?php

/**
 * 异常类
 */
class exception2 extends Exception
{

    private $langKey = ''; // 前台显示文本
    private $msg = '';
    private $cause = null;
    private $errno = 0;
    private $logStr = ''; // 日志记录文本

    public function __construct($langKey, $errno = 0,$logStr = '')
    {
        $this->langKey = $langKey;
        $this->errno = $errno;
        $this->message = $this->langKey;
        $this->code = $this->errno;
        $this->logStr = $logStr;
    }

    public function getCause()
    {
        return $this->cause;
    }

    public function getLangKey()
    {
        return $this->langKey;
    }

    public function getLangKeys()
    {
        $langKeys = array();
        $e = $this;
        do {
            $langKeys[] = $e->getLangKey();
            $e = $e->getCause();
        } while ($e);

        return array_reverse($langKeys);
    }

    public function getErrno()
    {
        return $this->errno;
    }

    public function getLogStr()
    {
        return $this->logStr;
    }

    public function getMessages()
    {
        $msgs = array();
        $e = $this;
        do {
            $msgs[] = $e->getMessage();
            $e = $e->getCause();
        } while ($e);

        return array_reverse($msgs);
    }

    public function getThrowTrace()
    {
        $traceInfos = array();

        $i = 0;
        $e = $this;
        do {
            $traceInfos[$i]['langKey'] = $e->getLangKey();
            $traceInfos[$i]['msg'] = $e->getMessage();
            $traceInfos[$i]['code'] = $e->getCode();
            $traceInfos[$i]['thrown_at'] = 'thrown at [' . $e->getFile() . ':' . $e->getLine() . ']';
            $traceInfos[$i]['trace'] = $e->getTrace();
            $i++;
        } while ($e = $e->getCause());

        return $traceInfos;
    }

    public function formatThrowTrace($lf = "\r\n")
    {
        $string = '';

        $traceInfos = $this->getThrowTrace();
        $i = 0;
        foreach ($traceInfos as $item) {
            if ($i++ > 0) {
                $string .= "Caused by: ";
            }

            $at = '@';
            $ld = '';
            $rd = '';

            $string .= "An exception has occured ({$item['langKey']}{$ld},code={$item['code']})$lf";

            $j = 0;
            // 外层捕获返回友好页面,这里信息记录到日志
            //if (RUN_ENV < 3) {
                $string .= "\t\t{$item['thrown_at']}$lf";
            //}
            $j++;

            foreach ($item['trace'] as $traceInfo) {
                $class = empty($traceInfo['class']) ? '' : $traceInfo['class'];
                $type = empty($traceInfo['type']) ? '' : $traceInfo['type'];

                $args = '';
                $k = 0;
                if (!empty($traceInfo['args'])) {
                    foreach ($traceInfo['args'] as $arg) {
                        if (is_array($arg)) {
                            $args .= 'Array';
                        }
                        elseif (is_object($arg)) {
                            $args .= 'Object';
                        }
                        else {
                            $args .= $arg;
                        }

                        if ($k++ < count($traceInfo['args']) - 1) {
                            $args .= ', ';
                        }
                    }
                }

                $position = empty($traceInfo['file']) ? '' : "called at [{$traceInfo['file']}:{$traceInfo['line']}]";

                //测试机打印更多信息
                // 外层捕获返回友好页面,这里信息记录到日志
                if (RUN_ENV < 3) {
                    $string .= "{$class}{$type}{$traceInfo['function']}($args) " . "{$position}$lf";
                }
                $j++;
            }
        }

        return $string;
    }

}
?>
