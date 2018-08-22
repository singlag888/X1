<?php
/**
 * class mysqlpdo实例驱动
 * @author asta
 */
class Mysqlpdo extends db{

	var $link_id = NULL;
	var $link_write_id = NULL;
	var $link_read_id = NULL;
	var $settings = '';
	var $writeSettings = '';
	var $charset = 'utf8';
	var $starttime = 0;
	var $dbhash = '';
	var $isTransactionInProgress = false;
	var $debug;
	var $affected_rows;
	var $trans;
	var $poolIns;
	var $swooleIp;
	var $swoolePort;
	var $isShare;

	public function __construct($settings, $charset = 'utf8', $debug = false, $isShare = false) {
		$this->settings = $settings;
		if(RUN_ENV < 3){
			$this->writeSettings = $this->settings;
		} else {
	        $this->writeSettings = $this->decodeConf($GLOBALS['PROJECTS']['ssc']['db_host']['write_host'], 'write');
		}
		$this->isShare = $isShare;
		$this->charset = $charset;
		$this->debug = $debug;
		$swooleConf = explode(':', $GLOBALS['PROJECTS']['ssc']['swoole_client']);
		$this->swooleIp = preg_match('@^(\d{1,3}\.){3}\d{1,3}$@', $swooleConf[0]) > 0 ? $swooleConf[0] : '127.0.0.1';
		$this->swoolePort = preg_match('@^\d+$@', $swooleConf[1]) ? $swooleConf[1] : '9503';
	}

	private function decodeConf($conf, $flag = 'read'){
		if(RUN_ENV < 3){
			return $GLOBALS['PROJECTS']['ssc']['db_host']['*'];
		}
		if (!preg_match('`^\w+://\w+:([^@]+)@\S+$`Uim', $conf, $match)) {
            throw new exception($flag . 'config set error');
        }

        if (!$pa = authcode($match[1], 'DECODE', PROJECT)) {
            throw new exception($flag . 'config2 set error');
        }

        return str_replace($match[1], $pa, $conf);
	}

	public function __destruct() {
		@$this->poolIns->close(true);
		$this->close();
	}

	public function connect() {
		$set = @parse_url($this->settings);
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
		} catch (PDOException $e) {
			logdump('MYSQL Connection failed: ' . $e->getMessage().'connet info:'.'mysql:host='.$set['host'].'; port='.$set['port'].'; dbname='.$set['query'].'user:'. $set['user'] .'pwd:'.$set['pass']);
			trigger_error('MYSQL Connection failed: ' . $e->getMessage(), E_USER_ERROR);
		}

		$this->dbhash = md5($set['host'] . ':' . $set['port'] . $set['user'] . $set['pass']);
		$this->starttime = time();

		return true;
	}



	public function query($sql , $paramsArr = array() , $type = 's') {
		if(!is_array($paramsArr)){
			if($this->debug){
				throw new exception2($sql . '执行参数出错!');
			}
		}

		if (isset($GLOBALS['_PROFILE']->MYSQL['COUNT']) === true) {
			++$GLOBALS['_PROFILE']->MYSQL['COUNT'];

			if ($GLOBALS['_PROFILE']->MYSQL['COUNT'] <= 199) {
				$GLOBALS['_PROFILE']->DETAIL[] = 'MYSQL-> ' . $sql;
			}
		} else {
			$GLOBALS['_PROFILE']->MYSQL['COUNT'] = 1;
			$GLOBALS['_PROFILE']->DETAIL[] = 'MYSQL-> ' . $sql;
		}

		//return $this->_query($sql,$paramsArr,$type);

		if($type == 'i' || $type == 'u' || $type == 'd'){
			return $this->_query_write($sql, $paramsArr, $type);
		} else {
			return $this->_query_read($sql, $paramsArr);
		}
	}

	private function socketPool($sql, $paramsArr = array(), $type = 's', $func = '', $field = ''){
		try {
			if($this->connectPool()){
				$data = array('sql' => $sql, 'params' => $paramsArr, 'type' => $type , 'func' => $func , 'field' => $field, 'isShare' => $this->isShare);
			    $this->poolIns->send(json_encode($data));
			  	$res = @$this->poolIns->recv();
			  	$this->poolIns->close();
			  	return json_decode($res,true);
			}

			return false;
		} catch (Exception2 $e) {
			logdump($e->getMessage());
			return false;
		}
	}

	private function connectPool(){
		if(!$this->trans){
			$this->getPoolIns();
			if($this->poolIns->isConnected() === false){
				return @$this->poolIns->connect($this->swooleIp, $this->swoolePort);
			}

			return true;
		}

		return false;
	}

	private function getPoolIns(){
		if (!$this->poolIns){
			$this->poolIns = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC);
			$this->poolIns->set(array(
			     'socket_buffer_size' => 1024*1024*2,
			));
		}
	}

	private function _query_read($sql, $paramsArr){
		if ($this->link_read_id === NULL) {
			$this->connect();
			$this->link_read_id = $this->link_id;
		}

		if ($this->trans){//如果有事务则将当前查询迁移到写库上
			if ($this->link_write_id === NULL) {
				$this->settings = $this->writeSettings;
				$this->connect();
				$this->link_write_id = $this->link_id;
			}
			$this->link_read_id = $this->link_write_id;
		}

		try{
			$explain_time = microtime(true);

			$stmt = $this->link_read_id->prepare($sql);

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

		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		return $stmt;
	}

	private function _query_write($sql,$paramsArr,$type){
		if ($this->link_write_id === NULL) {
			if($this->writeSettings == ''){
				$this->writeSettings = $this->decodeConf($GLOBALS['PROJECTS']['ssc']['db_host']['write_host'], 'write');
			}
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

	private function _query($sql,$paramsArr,$type){
		if ($this->link_id === NULL) {
			$this->connect();
		}
		try{
			$explain_time = microtime(true);
			$stmt = $this->link_id->prepare($sql);
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
				$this->affected_rows = $this->link_id->lastInsertId();
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
			default:
				$stmt->setFetchMode(PDO::FETCH_ASSOC);
				return $stmt;
				break;
		}
	}


	public function affected_rows() {
		return $this->affected_rows;
	}


	public function insert_id() {
		return $this->affected_rows;
	}


	public function close() {
		$this->link_id = NULL;
		$this->link_read_id = NULL;
		$this->link_write_id = NULL;
		$this->settings = $this->writeSettings = '';
		$this->charset = 'utf8';
	}


	public function getDesc($table, $all, $cache_time = 5) {
		static $table_desc = array();

		if (isset($table_desc[$this->settings][$table]) === true) {
			$field_descs = $table_desc[$this->settings][$table];
		} else {
			if ($cache_time > 0) {
				$field_descs = xc_get('getdesc_' . $this->settings, $table);
				if ($field_descs === false) {
					$field_descs = $this->getAll('DESC ' . $table);
					if ($field_descs !== false) {
						xc_set('getdesc_' . $this->settings, $table, $field_descs, $cache_time);
						$table_desc[$this->settings][$table] = $field_descs;
					}
				}
			} else {
				$field_descs = $this->getAll('DESC ' . $table);
				if ($field_descs !== false) {
					$table_desc[$this->settings][$table] = $field_descs;
				}
			}
		}

		if ($all === true) {
			return $field_descs;
		} else {
			$array = array();
			foreach ($field_descs AS $value) {
				$array[] = $value['Field'];
			}

			return $array;
		}
	}

	public function getBestTableByStatus($tables, $cache_time = 5) {
		static $table_status = array();

		$compare_tables = $rows = $lengths = array();
		foreach ($tables AS $table => $setting) {
			if (isset($table_status[$setting]) === false) {
				if ($cache_time > 0) {
					$status = xc_get('getbesttable_', $setting);
					if ($status === false) {
						$set = @parse_url($setting);
						dbserver_connect($setting);

						$status = $this->getAll("SHOW TABLE STATUS FROM `" . $set['query'] . "`");
						if ($status !== false) {
							xc_set('getbesttable_', $setting, $status, $cache_time);
							$table_status[$setting] = $status;
						}
					}
				} else {
					$set = @parse_url($setting);
					dbserver_connect($setting);

					$status = $this->getAll("SHOW TABLE STATUS FROM `" . $set['query'] . "`");
					if ($status !== false) {
						$table_status[$setting] = $status;
					}
				}
			}

			foreach ($status AS $value) {
				if ($value['Name'] === $table) {
					$rows[$table] = $value['Rows'];
					$lengths[$table] = $value['Data_length'];

					$compare_tables[$table] = $value;
				}
			}
		}

		array_multisort($lengths, SORT_ASC, $rows, SORT_ASC, $compare_tables);

		return reset($compare_tables);
	}

	public function getOne($sql, $paramsArr = array(), $swooleSocket = true) {
		$sql = trim($sql . ' LIMIT 1');
		if($swooleSocket){
			$poolRes = $this->socketPool($sql, $paramsArr, 's', __FUNCTION__);
			if($poolRes){
				return $poolRes;
			}
		}

		$res = $this->query($sql, $paramsArr);
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

	public function getAll($sql, $paramsArr = array(), $assocation = '', $swooleSocket = true) {
		if($swooleSocket){
			$poolRes = $this->socketPool($sql, $paramsArr, 's', __FUNCTION__, $assocation);
			if($poolRes){
				return $poolRes;
			}
		}

		$res = $this->query($sql, $paramsArr);

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

	public function getRow($sql, $paramsArr = array(), $swooleSocket = true) {
		if (stripos($sql, ' LIMIT ') === false) {
			$sql = trim($sql . ' LIMIT 1');
		}
		if($swooleSocket){
			$poolRes = $this->socketPool($sql, $paramsArr, 's', __FUNCTION__);
			if($poolRes){
				return $poolRes;
			}
		}
		$res = $this->query($sql, $paramsArr);

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

	public function getCol($sql, $col,$paramsArr = array(), $swooleSocket = true) {
		if($swooleSocket){
			$poolRes = $this->socketPool($sql, $paramsArr, 's', __FUNCTION__, $col);
			if($poolRes){
				return $poolRes;
			}
		}
		$res = $this->query($sql, $paramsArr);

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

	public function insert($table, $data) {
		if (!is_array($data) || empty($data)) {
			throw new exception2('[db]invalid_arg');
		}
		$fields = "`" . implode("`,`", array_keys($data)) . "`";
		$paramsArr = array();
	   	$valStr = '';

        $fill = array_fill(0, count($data), '?');
        $valStr = '('.implode(',', $fill).')';
        foreach($data as $v){
        	$paramsArr[] = $v;
        }

		$sql = "INSERT IGNORE INTO {$table} ({$fields}) VALUES {$valStr}";

		$res = $this->query($sql , $paramsArr, 'i');

		return is_numeric($res) ? true : false;
	}

	public function replaceInsert($table, $data) {
		if (!is_array($data) || empty($data)) {
			throw new exception2('[db]invalid_arg');
		}
		$fields = "`" . implode("`,`", array_keys($data)) . "`";
		$paramsArr = array();
	   	$valStr = '';

        $fill = array_fill(0, count($data), '?');
        $valStr = '('.implode(',', $fill).')';
        foreach($data as $v){
        	$paramsArr[] = $v;
        }

		$sql = "REPLACE INTO {$table} ({$fields}) VALUES {$valStr}";

		return $this->query($sql , $paramsArr, 'i');
	}

	public function normalMultipInsert($table, $inserts) {
		//取出第一个要保存的数据的key值来拼field
		$fields = "`" . implode("`,`", array_keys(current($inserts))) . "`";

		//拼接要保存的值
		foreach ($inserts as $insert) {
			$insert = array_map('addslashes', $insert); //使用addslashes，是避免在保存的值中出现' "这些会影响sql语句的情况。一般情况下，mysql设置为：转义后的值在保存到数据库后会自动反转义。
			$values[] = "\"" . implode("\",\"", $insert) . "\""; //拼接数据
		}
		$valueStr = implode("),(", $values); //把数组数据拼接成字符串
		//注意要插入的数据可能已经存在
		$sql = "INSERT IGNORE INTO $table ($fields) VALUES ($valueStr)"; //重点是使用IGNORE,即遇到失败的插入直接跳过，如，纪录己存在

		return $this->doExec($sql); //自定义的一个数据插入方法
	}


	public function multipInsert($table, $data) {
		if (!is_array($data) || empty($data)) {
			throw new exception2('[db]invalid_args');
		}

		$fields = "`" . implode("`,`", array_keys(reset($data))) . "`";
		$paramsArr = array();
	   	$valStr = '';

	    foreach($data as $v){
	        $fill = array_fill(0, count($v), '?');
	        $valStr .= '('.implode(',', $fill).'),';
	        foreach($v as $vv){
	        	$paramsArr[] = $vv;
	        }
	    }
	    $valStr = rtrim($valStr, ',');

		$sql = "INSERT IGNORE INTO {$table} ({$fields}) VALUES {$valStr}";

		return $this->query($sql , $paramsArr, 'i');
	}

	//针对一些特殊语句
	public function doExec($sql) {

		if ($this->link_write_id === NULL) {
			$this->settings = $this->writeSettings;
			$this->connect();
			$this->link_write_id = $this->link_id;
		}
		try{
			return $this->link_write_id->exec($sql);
		}catch(PDOException $e){
			if(RUN_ENV < 3){
				$traceMsg = formatThrowTrace($e, '<br />');
			}
			$this->getPDOError($sql,$traceMsg);
			exit;
		}
	}


	//安全模式支持条件语句array('id>'=>1) | array('id >'=>1) | array('id'=>1) | array('id ='=>1) | array('id IN'=>array(1,2,3))
	//IN条件只针对整型字段
	public function updateSM($table , $data , $where = array() , $limit = 1) {
		if(!is_string($table) || !is_array($data) || empty($data) || !is_array($where) || !is_numeric($limit)){
			throw new exception2("Error Request up");
		}

		$whereOption = '';
		$paramsArr = $tmp = $wtmp = array();
        foreach ($data as $k => $v) {
            $tmp[] = "`$k`=:$k";
            $paramsArr[':'.$k] = $v;
        }

        if(!empty($where)){
			foreach($where as $kk => $vv) {
	        	if(preg_match('/^(\w+)\s*([><=]*)$/i',$kk,$match)){
	        		$operator = $match[2] != '' ? $match[2] : '=' ;
		        	$field = $match[1];
		        	if(array_key_exists($field, $data)){//如果set中有字段在where中需要处理一下，set name=:name where name=:names
		        		$wtmp[] = "`$field`".$operator.":$field".'s';
		        		$paramsArr[':'.$field.'s'] = $vv;
		            } else {
		            	$wtmp[] = "`$field`".$operator.":$field";
		        		$paramsArr[':'.$field] = $vv;
		            }
	        	} elseif(preg_match('/^(\w+)\s+(IN|in)$/',$kk,$match)) {
	        		$field = $match[1];
	        		if(is_array($vv) && !empty($vv)){
	        			if(is_numeric($vv[array_rand($vv,1)])){//随机选一个值代表，如果In的值是数字型
	        				$wtmp[] = "`$field` IN (" . implode(',', $vv) . ")";
	        			}elseif(is_string($vv[array_rand($vv,1)])){//随机选一个值代表，如果In的值是字符串
	        				$wtmp[] = "`$field` IN ('" . implode("','", $vv) . "')";
	        			}
	        		}else{
	        			throw new exception2("Error Request update in format");
	        		}
	        	} else {
					throw new exception2("Error Request update format");
	        	}
	        }
	        $whereOption = " WHERE " . implode(' AND ', $wtmp);
        }


        $sql = "UPDATE {$table} SET " . implode(',', $tmp) . $whereOption . ' LIMIT '.$limit;


        if ($this->query($sql, $paramsArr, 'u') === false) {
            return false;
        }

        return $this->affected_rows();
	}


	public function startTransaction() {

		if ($this->trans)
			return;

		if (is_null($this->link_write_id)){
			$this->settings = $this->writeSettings;
			$this->connect();
			$this->link_write_id = $this->link_id;
		}

		$this->trans = $this->link_write_id->beginTransaction();

		return $this->trans;
	}

	/**
	 * 回滚事务
	 * @return BOOL
	 */
	public function rollback() {

		if (!$this->trans)
		  return false;

		if (is_null($this->link_write_id)){
			$this->settings = $this->writeSettings;
			$this->connect();
			$this->link_write_id = $this->link_id;
		}

		$ret = $this->link_write_id->rollBack();
		$this->trans = false;

		return $ret;
	}

	/**
	 * 提交事务
	 * @return BOOL
	 */
	public function commit() {

		if (!$this->trans)
			return false;

		if (is_null($this->link_write_id)){
			$this->settings = $this->writeSettings;
			$this->connect();
			$this->link_write_id = $this->link_id;
		}

		$ret = $this->link_write_id->commit();
		$this->trans = false;

		return $ret;
	}

	public function optimizeTable($table) {
		$sql = "OPTIMIZE TABLE $table";
		$this->doExec ( $sql );
	}

	/**
	 * 将数据库错误信息返回到框架层级
	 * 捕获PDO错误信息
     * 返回:出错信息
     * 类型:字串
	 */
    private function getPDOError($sql, $traceMsg = '')
    {
        $this->debug && logdump('problem sql: ' . $sql);
        // $errorCode = $this->link_id->errorCode();

        //if ($errorCode != '00000') {
        $detailErr = $this->link_id->errorInfo();
        $errStr = "[MYSQL] $sql 执行出错, 错误信息: " . $detailErr[2] . ', 错误代码: ' . $detailErr[1] . '详细信息：' . $traceMsg;
        logdump($errStr);

        if (RUN_ENV < 3) {
            trigger_error($errStr, E_USER_ERROR);
        } else {
            exception_handler(new exception2($errStr));
            $text = ob_get_clean();
            $text = str_replace('<br />', PHP_EOL, $text);
            $dateTime = date('Y-m-d H:i:s') . PHP_EOL;
            $target = '盘口: ' . (defined('XY_PREFIX') ? XY_PREFIX : '未定义的盘口') . ',发生错误.' . PHP_EOL;

            defined('LOG_TAG') or define('LOG_TAG', 'mysql');
            // tofile('run_' . LOG_TAG . '_error.log', $dateTime . $target . $text, FILE_APPEND);
            toFileAtTmp('run_' . LOG_TAG . '_error.log', $dateTime . $target . $text, FILE_APPEND);

            if (LOG_TAG == 'admin') {
                $links = [[
                    'title' => '返回首页',
                    'url' => '/?a=welcome',
                ]];
                if (function_exists('showMsg')) {
                    showMsg('异常，请联系平台客服！<br />' . date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']), 0, $links);
                } else {
                    echo 'MysqlPdo执行异常,请检查错误日志.' . PHP_EOL;
                }
            } else if (LOG_TAG == 'app') {
                if (function_exists('showMsg')) {
                    showMsg(1, '异常，请联系平台客服！');
                } else {
                    echo json_encode([
                        'errno' => 1,
                        'errstr' => 'MysqlPdo执行异常,请检查错误日志.' . PHP_EOL
                    ], JSON_UNESCAPED_UNICODE);
                }
            } else {
                if (function_exists('showMsg')) {
                    showMsg('异常，请联系平台客服！<br />' . date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']));
                } else {
                    echo 'MysqlPdo执行异常,请检查错误日志.' . PHP_EOL;
                }
            }
        }

        exit;
    	//}
    }

}
