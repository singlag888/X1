<?php
define('RUN_ENV', 1);
define('PROJECT', 'ssc');
define('IN_LIGHT', true);
date_default_timezone_set('Asia/Shanghai');
define('PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
$rootPath = substr(PATH,0,strpos(PATH,'framework'));

require_once($rootPath . '/projects.config.php');
require_once($rootPath . '/framework/library/common.lib.php');

class server{

	private $serv;
	private $pdoIns;
	private $settings;
	private $writeSettings;
	private $shareSettings;
	private $charset = 'utf8';
	private $link_id = NULL;
	private $link_write_id = NULL;
	private $link_read_id = NULL;
	private $share_read_id = NULL;

	public function __construct(){

		//$this->settings = $GLOBALS['PROJECTS']['ssc']['db_host'];
		$swooleConf = explode(':', $GLOBALS['PROJECTS']['ssc']['swoole_server']);
		$this->serv = new swoole_server($swooleConf[0], $swooleConf[1]);
		$this->serv->set(array(
		    'worker_num' => 8,    //根据CPU核数来
		    'task_worker_num' => 8,
		    'max_request' => 10000,
		    'dispatch_mode'=>3, //抢占
		    //'package_eof' => "\r\n\r\n",  //http协议就是以\r\n作为结束符的
		    //'open_eof_check' => 1,
		    'heartbeat_check_interval' => 5,
    		'heartbeat_idle_time' => 10,
    		'debug_mode' => 1,
    		'buffer_output_size' => 50 * 1024 *1024,
    		'socket_buffer_size' =>20 * 1024 *1024,
    		//'daemonize' => 1,
    		'log_file' => '/tmp/swoole.log',
		));
		$this->serv->on('Workerstart', array($this,'onWorkerstart'));
		$this->serv->on('Connect', array($this,'onConnect'));
		$this->serv->on('Receive', array($this,'onReceive'));
		$this->serv->on('Close', array($this,'onClose'));
		$this->serv->on('Task', array($this,'onTask'));
		$this->serv->on('Finish', array($this,'onFinish'));
		$this->serv->start();
	}

	private function initSetting(){

		$this->settings = $GLOBALS['PROJECTS']['ssc']['db_host']['*'];

		if(RUN_ENV < 3){
			$this->writeSettings = $this->shareSettings = $this->settings;
		} else {
            $this->settings = $this->decodeConf($this->settings);
	        $this->writeSettings = $this->decodeConf($GLOBALS['PROJECTS']['ssc']['db_host']['write_host'], 'write');
	        $this->shareSettings = $this->decodeConf($GLOBALS['PROJECTS']['ssc']['db_host']['share_host']);
		}

		$this->connect();
	}

	private function decodeConf($conf, $flag = 'read'){
		if (!preg_match('`^\w+://\w+:([^@]+)@\S+$`Uim', $conf, $match)) {
            throw new exception($flag . 'config set error');
        }

        if (!$pa = authcode($match[1], 'DECODE', PROJECT)) {
            throw new exception($flag . 'config2 set error');
        }

        return str_replace($match[1], $pa, $conf);
	}

	private function connect($isShare = false) {

		$set = $isShare == false ? @parse_url($this->settings) : @parse_url($this->shareSettings);
		if (isset($set['port']) == false) {
			$set['port'] = '3306';
		}

		if (isset($set['query']) == false) {
			$set['query'] = '';
		}

		try {
			$this->link_id = new PDO('mysql:host='.$set['host'].'; port='.$set['port'].'; dbname='.$set['query'], $set['user'], $set['pass']);
			$this->link_id->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			$this->link_id->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->link_id->exec("SET NAMES 'utf8'");
			return true;
		} catch (PDOException $e) {
			$this->log('SWOOLE MYSQL Connection failed: ' . $e->getMessage().'connet info:'.'mysql:host='.$set['host'].'; port='.$set['port'].'; dbname='.$set['query'].'user:'. $set['user']);
			trigger_error('SWOOLE MYSQL Connection failed: ' . $e->getMessage(), E_USER_ERROR);
		}

		return true;
	}

	public function query($sql , $paramsArr = array() , $type = 's', $isShare = false) {
		if(!is_array($paramsArr)){
			if($this->debug){
				throw new exception2($sql . '执行参数出错!');
			}
		}

		if($type == 'i' || $type == 'u' || $type == 'd'){
			return $this->_query_write($sql, $paramsArr, $type);
		} else {
			return $this->_query_read($sql, $paramsArr, $isShare);
		}
	}

	private function _query_read($sql, $paramsArr, $isShare = false){
		if($isShare == true){
			if($this->share_read_id === NULL){
				$this->connect(true);
				$this->share_read_id = $this->link_id;
			}
		}else {
			if ($this->link_read_id === NULL) {
				$this->connect();
				$this->link_read_id = $this->link_id;
			}
		}

		try{
			$explain_time = microtime(true);
			if($isShare == true){
				$stmt = $this->share_read_id->prepare($sql);
			} else {
				$stmt = $this->link_read_id->prepare($sql);
			}

			$execute = empty($paramsArr) ? $stmt->execute() : $stmt->execute($paramsArr);

		}catch(PDOException $e){
			$traceMsg = formatThrowTrace($e, '<br />');
			$this->log($sql.$traceMsg);
			exit;
		}

		if (!$execute) {
			if(RUN_ENV < 3){
				$traceMsg = formatThrowTrace($e, '<br />');
			}
			$this->log($sql.$traceMsg);
			return false;
		}

		if ((microtime(true) - $explain_time) > 3.000) {

		}

		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		unset($paramsArr);
		unset($sql);
		return $stmt;
	}

	private function _query_write($sql,$paramsArr,$type){
		if ($this->link_write_id === NULL) {
			$this->settings = $this->writeSettings;
			$this->connect();
			$this->link_write_id = $this->link_id;
		}

		try{
			$explain_time = microtime(true);
			$stmt = $this->link_write_id->prepare($sql);
			$execute = empty($paramsArr) ? $stmt->execute() : $stmt->execute($paramsArr);
		}catch(PDOException $e){
			$traceMsg = formatThrowTrace($e, '<br />');
			$this->getPDOError($sql,$traceMsg);
			exit;
		}
		if (!$execute) {
			if(RUN_ENV < 3){
				$traceMsg = formatThrowTrace($e, '<br />');
			}
			$this->getPDOError($sql,$traceMsg);
			return false;
		}
		if ((microtime(true) - $explain_time) > 3.000) {

		}
		switch ($type) {
			case 'i':
				$this->affected_rows = $this->link_write_id->lastInsertId();
				if($this->affected_rows == 0){
					$this->affected_rows = $stmt->rowCount();
				}
				return $this->affected_rows;
				break;
			case 'u':
			case 'd':
				$this->affected_rows = $stmt->rowCount();
				return $this->affected_rows;
				break;
		}
	}

	private function _getOne($sql, $paramsArr = array(), $isShare = false) {
		$sql = trim($sql . ' LIMIT 1');

		$res = $this->query($sql, $paramsArr, 's', $isShare);
		if ($res !== false) {
			$row = $res->fetchColumn(0);

			if ($row !== NULL) {
				return $row;
			} else {
				return '';
			}
		} else {
			return false;
		}
	}

	private function _getAll($sql, $paramsArr = array(), $assocation = '', $isShare = false) {

		$res = $this->query($sql, $paramsArr, 's', $isShare);

		if ($res !== false) {
			$array = array();
			while ($row = $res->fetch()) {
				if ($row === NULL) {
					$array[] = array();
				} else {
					if ($assocation !== '') {
						$array[$row[$assocation]] = $row;
					} else {
						$array[] = $row;
					}
				}
			}

			return $array;
		} else {
			return false;
		}
	}

	private function _getRow($sql, $paramsArr = array(), $isShare = false) {
		if (stripos($sql, ' LIMIT ') === false) {
			$sql = trim($sql . ' LIMIT 1');
		}

		$res = $this->query($sql, $paramsArr, 's', $isShare);

		if ($res !== false) {
			$result = $res->fetch();
			if ($result == NULL) {
				return array();
			} else {
				return $result;
			}
		} else {
			return false;
		}
	}

	private function _getCol($sql, $col,$paramsArr = array(), $isShare = false) {

		$res = $this->query($sql, $paramsArr, 's', $isShare);

		if ($res !== false) {
			$array = array();
			while ($row =  $res->fetch() ) {
				if ($row === NULL) {
					$array[] = '';
				} else {
					$array[] = $row[$col];
				}
			}

			return $array;
		} else {
			return false;
		}
	}

	public function onConnect($serv, $fd, $from_id){
		return true;
	}

	public function onClose($serv, $fd, $from_id){
        return true;
	}

	public function onWorkerstart($serv, $worker_id){
		try{
			if($serv->taskworker){
				//创建PDO对象
				$this->initSetting();
			}
		}catch(PDOException $e){
			$this->log('SWOOLE PDO Connection failed: ' . $e->getMessage());
			return false;
		}
	}
	//$data = array('sql' => $sql, 'params' => $paramsArr, 'type' => $type , 'func' => $func , 'field' => $field);
	public function onReceive(swoole_server $serv, $fd, $from_id, $data){
		$datas = json_decode($data, true);
		$datas['fd'] = $fd;

		$serv->task(json_encode($datas));
		unset($datas);
        return true;
	}

	public function onTask($serv, $task_id, $from_id, $data){
		$datas = json_decode($data, true);

		try{
			$res = false;
			if(isset($datas['func'])){
				switch ($datas['func']) {
					case 'getOne':
						$res = $this->_getOne($datas['sql'], $datas['params'], $datas['isShare']);
						break;
					case 'getAll':
						$res = $this->_getAll($datas['sql'], $datas['params'], $datas['field'], $datas['isShare']);
						break;
					case 'getRow':
						$res = $this->_getRow($datas['sql'], $datas['params'], $datas['isShare']);
						break;
					case 'getCol':
						$res = $this->_getCol($datas['sql'], $datas['field'], $datas['params'], $datas['isShare']);
						break;
					default:
						echo '未发现的方法taskid:'.$task_id."-$from_id\n";
				}

				if($res !== false){
					$serv->send($datas['fd'], json_encode($res));
					$serv->finish("OK");
				} else {
					$serv->finish($datas['sql']);
				}
			}

            unset($datas);
            unset($res);
            return true;
		}catch(PDOException $e){
			$this->log('SWOOLE PDO Query failed: ' . $e->getMessage());
			return "false";
		}
	}

	public function onFinish($serv, $task_id, $data){
		if($data!='OK'){
			echo "Execute Failed.PID=" . posix_getpid() . 'SQL:'.$data.';'.PHP_EOL;
		}
		return true;
	}

	private function log($msg){
		error_log(date('Y-m-d H:i:s').'-'.$msg.PHP_EOL, 3, '/tmp/swoole_error.log');
		return true;
	}

}

new server();
?>
