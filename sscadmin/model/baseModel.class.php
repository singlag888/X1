<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class baseModel
{
    protected $tableName = ''; // 表名
    protected $pk = 'id'; // 主键
    protected $autoinc = false; // 主键是否自动增长
    protected $fields = []; // 字段
    protected $tablePrefix = ''; // 表前缀
    protected $trueTableName = ''; // 真实表名,带前缀
    protected $name = ''; // 模型名称
    protected $dbName = ''; // 数据库名称
    protected $autoCheckFields = true; // 是否自动检测数据表字段信息
    protected $connection = 'db'; // 数据库链接标识
    /**
     * 当前数据库操作对象
     * @var Mysqlpdo $db
     */
    protected $db = null;
    // 数据库对象池
    private $_db = [];
    /**
     * @index table 表名参数
     * @index limit 分页参数
     * @index where 条件参数(数组)
     * @index whereStr 条件参数(字符串)
     * @index order 排序参数
     * @index group 分组参数
     * @index field 字段参数
     * @index alias 别名参数
     * @index join 连表参数
     * @index index 索引参数 一般为主键
     * @index bind 绑定参数
     */
    protected $options = []; // 查询表达式参数
    protected $data = []; // 数据参数
    protected $error = '';

    private $transactions = 0; // 事物层级

    const METHOD_SELECT = 1;
    const METHOD_INSERT = 2;
    const METHOD_UPDATE = 4;
    const METHOD_DELETE = 8;

    /**
     * 架构函数
     * 取得DB类的实例对象 字段检查
     * @access public
     * @param string $name 模型名称
     * @param string $tablePrefix 表前缀
     * @param mixed $connection 数据库连接信息
     */
    public function __construct($name = '', $tablePrefix = '', $connection = '')
    {
        // 模型初始化
        $this->_initialize();
        // 获取模型名称
        if (!empty($name)) {
            if (strpos($name, '.')) { // 支持 数据库名.模型名的 定义
                list($this->dbName, $this->name) = explode('.', $name);
            } else {
                $this->name = $name;
            }
        } elseif (empty($this->name)) {
            $this->name = $this->getModelName();
        }
        // 设置表前缀
        if ($tablePrefix != '') {
            $this->tablePrefix = $tablePrefix;
        } else {
            // TODO: 取配置
        }

        $this->db(empty($this->connection) ? $connection : $this->connection);
    }

    // 回调方法 初始化模型
    protected function _initialize()
    {
    }

    /**
     * 切换当前的数据库连接
     * @access public
     * @param mixed $connection 数据库链接标识
     * @return baseModel
     */
    public function db($connection = '')
    {
        if ('' === $connection) {
            return $this;
        }

        if (!isset($this->_db[$connection])) {
            // 创建一个新的实例
            // 其实现在这个就这样也好,因为不需要去获取实例,引用就好.
            $this->_db[$connection] = &$GLOBALS[$connection];
        }

        // elseif (NULL === $connection) {
        // $this->_db[$connection]->close(); // 关闭数据库连接
        // unset($this->_db[$connection]);
        // return;
        // }

        // 切换数据库连接
        $this->db = &$this->_db[$connection];
        // 字段检测
        if (!empty($this->name) && $this->autoCheckFields && $this->name != 'baseModel') $this->_checkTableInfo();
        return $this;
    }

    /**
     * get one row
     * @param int|string $id single primary key
     * @param bool|array $option
     * @return array
     */
    public function find($id = '', $option = [])
    {
        $id && $this->where([$this->pk => $id]);
        $this->limit(1);
        $data = $this->select($option);
        return is_array($option) ? current($data) : $data;
    }

    /**
     * get one col
     * @param string $field
     * @return mixed
     */
    public function getField($field)
    {
        #TODO: 这里抽出来统一查询入口
        $sql = 'SELECT ' . $field . ' FROM `' . $this->getOptionTable() . '`';
        list($WHERE, $PARAM) = $this->parseWhere();
        $sql .= $WHERE;
        $this->options = [];

        $sql = $this->formatSql($sql, self::METHOD_SELECT);
        $this->recordSql($sql, $PARAM, self::METHOD_SELECT);
        return current($this->db->getCol($sql, $field, $PARAM));
    }

    /**
     * execute SQL query
     * @param bool|array $option
     * @return array|string
     */
    public function select($option = [])
    {
        $FIELD = isset($this->options['field']) ? $this->options['field'] : '*';

        $sql = 'SELECT ' . $FIELD . ' FROM `' . $this->getOptionTable() . '`';
        isset($this->options['alias']) && $sql .= ' AS `' . $this->options['alias'] . '` ';

        if (isset($this->options['join'])) {
            if (is_array($this->options['join'])) {
                $sql .= implode(' ', $this->options['join']);
            } else {
                $sql .= $this->options['join'];
            }
        }

        list($WHERE, $PARAM) = $this->parseWhere();
        $sql .= $WHERE;
        isset($this->options['bind']) && $PARAM = array_merge($PARAM, $this->options['bind']);

        isset($this->options['group']) && $sql .= $this->options['group'];
        isset($this->options['having']) && $sql .= $this->options['having'];
        isset($this->options['order']) && $sql .= $this->options['order'];
        isset($this->options['limit']) && $sql .= ' LIMIT ' . $this->options['limit'];
        $index = isset($this->options['index']) ? $this->options['index'] : '';

        $sql = $this->formatSql($sql, self::METHOD_SELECT);

        $this->options = [];
        if ($option === false) {
            return [$sql, $PARAM];
        } else if ($option === true) {
            return $this->parseSql($sql, $PARAM);
        }

        $this->recordSql($sql, $PARAM, self::METHOD_SELECT);
        return $this->db->getAll($sql, $PARAM, $index);
    }

    /**
     * insert one
     * @param array $data
     * @param bool|array $option
     * @return int | array | bool $insert_id
     */
    public function insert($data = [], $option = [])
    {
        in_array($data, [false, true], true) && $option = $data;
        (is_bool($data) || empty($data)) && $data = $this->data;
        $this->data = [];

        if (!is_array($data)) {
            $this->error = 'invalid args';
            return false;
        }

        $this->_autoComplete($data, static::METHOD_INSERT);
        if ($this->_autoValidate($data, static::METHOD_INSERT) === false) {
            return false;
        }

        $fields = '(`' . implode('`,`', array_keys($data)) . '`)';
        $valStr = '(' . implode(',', array_fill(0, count($data), '?')) . ')';
        $sql = "INSERT IGNORE INTO {$this->getOptionTable()} {$fields} VALUES {$valStr}";

        $sql = $this->formatSql($sql, self::METHOD_INSERT);

        $this->options = [];
        if ($option === false) {
            return [$sql, $data];
        } else if ($option === true) {
            return $this->parseSql($sql, $data, self::METHOD_INSERT);
        }

        $this->recordSql($sql, $data, self::METHOD_INSERT);
        $result = $this->db->query($sql, array_values($data), 'i');
        return $result !== false ? $this->db->insert_id() : $result;
    }

    /**
     * update
     * @param array $data
     * @param array $option
     * @return int|array|bool
     */
    public function update($data = [], $option = [])
    {
        in_array($data, [false, true], true) && $option = $data;
        (is_bool($data) || empty($data)) && $data = $this->data;
        $this->data = [];
        if (!is_array($data) || empty($data)) {
            $this->error = 'invalid args';
            return false;
        }

        $this->_autoComplete($data, static::METHOD_UPDATE);
        if ($this->_autoValidate($data, static::METHOD_UPDATE) === false) {
            return false;
        }

        $sql = 'UPDATE `' . $this->getOptionTable() . '` SET ';

        $set = array_keys($data);
        foreach ($set as &$key) {
            // 这里将set的绑定键加一个下划线
            $key = '`' . $key . '`' . ' = :_' . $key;
        }
        $sql .= implode($set, ', ');

        list($WHERE, $PARAM) = $this->parseWhere();
        $sql .= $WHERE;

        isset($this->options['bind']) && $PARAM = array_merge($PARAM, $this->options['bind']);

        // 处理绑定参数
        foreach ($data as $key => &$datum) {
            // 合并时注意同样加下划线
            $PARAM[':_' . $key] = $datum;
        }

        $sql = $this->formatSql($sql, self::METHOD_UPDATE);
        $this->options = [];
        if ($option === false) {
            return [$sql, $PARAM];
        } else if ($option === true) {
            return $this->parseSql($sql, $PARAM);
        }

        $this->recordSql($sql, $PARAM, self::METHOD_UPDATE);
        return $this->db->query($sql, $PARAM, 'u');
    }

    /**
     * delete
     * @param array $option
     * @return mixed
     */
    public function delete($option = [])
    {
        $sql = 'DELETE FROM `' . $this->getOptionTable() . '` ';

        list($WHERE, $PARAM) = $this->parseWhere();
        $sql .= $WHERE;
        if (empty($WHERE) && $option !== true && $option !== false) {
            // 当没有条件时,不允许删除.
            $this->error = 'Unconditional deletion';
            return false;
        }

        $sql = $this->formatSql($sql, self::METHOD_DELETE);
        $this->options = [];
        if ($option === false) {
            return [$sql, $PARAM];
        } else if ($option === true) {
            return $this->parseSql($sql, $PARAM);
        }

        $this->recordSql($sql, $PARAM, self::METHOD_DELETE);
        return $this->db->query($sql, $PARAM, 'd');
    }

    /**
     * set field parameter
     * @param $fields
     * @return $this
     */
    public function field($fields)
    {
        if (is_array($fields)) {
            // TODO:数组的情况下有个BUG,例如 ['name','SUM(field)']
            // 所以要使用函数写到字符串里
            // 解析出来就会是 `SUM(field)` 显然是没有这个字段的.
            foreach ($fields as &$field) {
                if (strpos($field, '.')) {
                    list($tab, $fid) = explode('.', $field);
                    $field = "`{$tab}`.`{$fid}`";
                } else {
                    $field = '`' . $field . '`';
                }
            }

            $this->options['field'] = implode(',', $fields);
        } elseif ($fields === true) {
            $this->options['field'] = $this->fields;
            unset($this->options['field']['_pk']);
            unset($this->options['field']['_type']);
            foreach ($this->options['field'] as &$field) {
                $field = '`' . $field . '`';
            }
            $this->options['field'] = implode(',', $this->options['field']);
        } else {
            $this->options['field'] = $fields;
        }
        return $this;
    }

    /**
     * 查询SQL组装 join
     * @access public
     * @param mixed $join
     * @param string $type JOIN类型
     * @return $this
     */
    public function join($join, $type = 'INNER')
    {
        $prefix = $this->tablePrefix;
        if (is_array($join)) {
            foreach ($join as $key => &$_join) {
                $_join = preg_replace_callback("/__([A-Z0-9_-]+)__/sU", function ($match) use ($prefix) {
                    return $prefix . strtolower($match[1]);
                }, $_join);
                $_join = false !== stripos($_join, 'JOIN') ? $_join : $type . ' JOIN ' . $_join;
            }
            $this->options['join'] = $join;
        } elseif (!empty($join)) {
            //将__TABLE_NAME__字符串替换成带前缀的表名
            $join = preg_replace_callback("/__([A-Z0-9_-]+)__/sU", function ($match) use ($prefix) {
                return $prefix . strtolower($match[1]);
            }, $join);
            $this->options['join'][] = false !== stripos($join, 'JOIN') ? $join : $type . ' JOIN ' . $join;
        }
        return $this;
    }

    /**
     * set order parameter
     * @param $orderParam
     * @return $this
     */
    public function order($orderParam)
    {
        $orderStr = ' ORDER BY ';
        if (is_array($orderParam)) {
            foreach ($orderParam as $field => &$item) {
                $orderStr != ' ORDER BY ' && $orderStr .= ',';
                $orderStr .= $field . ' ' . $item;
            }
        } else {
            $orderStr .= $orderParam;
        }

        $this->options['order'] = $orderStr;
        return $this;
    }

    /**
     * set limit parameter
     * @param int|array|string $offset 10  |  [0,10]  |  '0,10'
     * @param int $length
     * @return $this
     */
    public function limit($offset, $length = null)
    {
        if (is_array($offset)) {
            list($offset, $length) = $offset;
        } elseif (is_null($length) && strpos($offset, ',')) {
            list($offset, $length) = explode(',', $offset);
        }
        $this->options['limit'] = intval($offset) . ($length ? ',' . intval($length) : '');
        return $this;
    }

    /**
     * set group parameter
     * @param array|string $groupParam
     * @return $this
     */
    public function group($groupParam)
    {
        $groupStr = ' GROUP BY ';
        if (is_array($groupParam)) {
            $groupStr .= implode(',', $groupParam);
        } else {
            $groupStr .= $groupParam;
        }

        $this->options['group'] = $groupStr;
        return $this;
    }

    /**
     * set having parameter
     * @param string $havingParam
     * @return $this
     */
    public function having($havingParam = '')
    {
        $havingParam && $this->options['having'] = ' HAVING ' . $havingParam;
        return $this;
    }

    /**
     * set where parameter
     * @param array|string $where
     * @param array $parse
     * @return $this
     */
    public function where($where, $parse = [])
    {
        if (!is_null($parse) && is_string($where)) {
            if (!is_array($parse)) {
                $parse = func_get_args();
                array_shift($parse);
            }
            $parse = array_map([$this, 'escapeString'], $parse);
            $where = vsprintf($where, $parse);
        }

        if (is_string($where) && '' != $where) {
            $this->options['whereStr'] = $where;
            return $this;
        }

        if (isset($this->options['where'])) {
            $this->options['where'] = array_merge($this->options['where'], $where);
        } else {
            $this->options['where'] = $where;
        }

        return $this;
    }

    /**
     * set alias parameter
     * @param $alias
     * @return $this
     */
    public function alias($alias)
    {
        $this->options['alias'] = $alias;
        return $this;
    }

    /**
     * set index key for select
     * @param bool|string $index
     * @return $this
     */
    public function index($index = true)
    {
        if ($index === true) {
            $this->options['index'] = $this->pk;
        } else {
            $this->options['index'] = $index;
        }
        return $this;
    }

    /**
     * 参数绑定
     * @access public
     * @param string|array $key 参数名
     * @param mixed $value 绑定的变量及绑定参数
     * @return $this
     */
    public function bind($key, $value = false)
    {
        if (is_array($key)) {
            $this->options['bind'] = $key;
        } else {
            $num = func_num_args();
            if ($num > 2) {
                $params = func_get_args();
                array_shift($params);
                $this->options['bind'][$key] = $params;
            } else {
                $this->options['bind'][$key] = $value;
            }
        }
        return $this;
    }

    /**
     * 设置操作参数
     * @param $name
     * @param $value
     * @return $this
     */
    public function options($name, $value)
    {
        $this->options[$name] = $value;
        return $this;
    }

    /**
     * 设置数据参数
     * @param $data
     * @return $this
     */
    public function data($data)
    {
        if ($this->data) {
            $this->data = array_merge($this->data, $data);
        } else {
            $this->data = $data;
        }
        return $this;
    }

    /**
     * 设置操作表表名
     * @param $table
     * @return $this
     */
    public function table($table)
    {
        $tableName = !empty($this->tablePrefix) ? $this->tablePrefix : '';
        $tableName .= parse_name($table);
        $this->options['table'] = (!empty($this->dbName) ? $this->dbName . '.' : '') . strtolower($tableName);
        return $this;
    }

    /**
     * 获取执行表名
     * @return mixed|string
     */
    protected function getOptionTable()
    {
        return (isset($this->options['table']) && $this->options['table'])
            ? $this->options['table'] : $this->getTableName();
    }

    /**
     * generate where string and bind params
     * @param array $conditions
     * @param bool $header Whether to add a where
     * @return array
     */
    protected function parseWhere($conditions = [], $header = true)
    {
        (!$conditions && isset($this->options['where'])) && $conditions = $this->options['where'];
        $sql = '';
        $param = [];
        foreach ($conditions as $key => $value) {
            // 因为参数绑定不能有点,所有将点换成下划线,用于有别名的情况 eg: :table.filed -> :table_field
            $bindKey = strpos($key, '.') !== false ? str_replace('.', '_', $key) : $key;

            if (is_array($value)) {
                // 当值是数组是有几种情况
                // 1.操作符 2.值 3.逻辑符
                // eg: ['>', 100, 'OR']
                // notice:逻辑符只在非第一顺位有效(即不在where数组中的第一位)
                if (count($value) > 2) {
                    list($exp, $val, $logic) = $value;
                } else {
                    list($exp, $val) = $value;
                    $logic = 'AND';
                }

                if (in_array(strtoupper($exp), ['IN', 'NOT IN'])) {
                    is_string($val) && $val = explode(',', $val);
                    foreach ($val as $flag => $item) {
                        $param[':' . $bindKey . $flag] = $item;
                    }
                } elseif (in_array(strtoupper($exp), ['BETWEEN', 'NOT BETWEEN'])) {
                    $param[':' . $bindKey . '_LEFT'] = $val[0];
                    $param[':' . $bindKey . '_RIGHT'] = $val[1];
                } else {
                    $param[':' . $bindKey] = $val;
                }
            } else {
                list($exp, $val, $logic) = ['=', null, 'AND'];
                $param[':' . $bindKey] = $value;
            }

            // 设置公式左边
            if (strpos($key, '.') !== false) {
                list($tab, $fid) = explode('.', $key);
                $sql ? $sql .= "{$logic} `{$tab}`.`{$fid}`" : $sql .= "`{$tab}`.`{$fid}`";
            } else {
                $sql ? $sql .= "{$logic} `{$key}`" : $sql .= "`{$key}`";
            }

            // 设置公式右边
            if (in_array(strtoupper($exp), ['IN', 'NOT IN'])) { // IN, NOT IN
                foreach ($val as $flag => &$item) {
                    $item = ':' . $bindKey . $flag;
                }

                $sql .= " {$exp} (" . implode(',', $val) . ') ';
            } elseif (in_array(strtoupper($exp), ['BETWEEN', 'NOT BETWEEN'])) { // BETWEEN, NOT BETWEEN
                $sql .= " {$exp} :{$bindKey}_LEFT AND :{$bindKey}_RIGHT ";
            } else { // >, <, >=, <=, <>, =, LIKE, NOT LIKE
                $sql .= " {$exp} :{$bindKey} ";
            }
        }

        if (isset($this->options['whereStr'])) {
            $sql .= $sql ? ' AND ' . $this->options['whereStr'] . ' ' : ' ' . $this->options['whereStr'] . ' ';
        }

        ($sql && $header) && $sql = ' WHERE ' . $sql;
        return [$sql, $param];
    }

    /**
     * 清除SQL中多余的格式
     * @param $sql
     * @param $action
     * @return string
     */
    protected function formatSql($sql, $action)
    {
        switch ($action) {
            case self::METHOD_INSERT:
            case self::METHOD_UPDATE:
            case self::METHOD_DELETE:
                break;
            case self::METHOD_SELECT:
                // 即使多一个空格,也会将整个SQL看做是不同的语句.从新建立解析.
                // 清除字符串参数中的回车
                $sql = preg_replace("/\r|\n|\r\n/", '', $sql);
                // 清除多余空格
                // $sql = preg_replace('/,\s+/', ',', $sql);
                $sql = preg_replace('/\s{2,}/', ' ', $sql);
                break;
        }

        return trim($sql);
    }

    /**
     * 记录执行SQL
     * @param $sql
     * @param $PARAM
     * @param $method
     */
    protected function recordSql($sql, $PARAM = [], $method = 0)
    {
        $sql = $this->parseSql($sql, $PARAM, $method);

        !isset($GLOBALS['_sql']) && $GLOBALS['_sql'] = [];
        $GLOBALS['_sql'][] = $sql;
    }

    /**
     * 解析成SQL
     * @param string $sql
     * @param array $PARAM
     * @param int $method
     * @return string
     */
    protected function parseSql($sql, $PARAM = [], $method = 0)
    {
        if ($PARAM) {
            foreach ($PARAM as $key => &$item) {
                if ($method & self::METHOD_INSERT) {
                    if (($position = strpos($sql, '?')) !== false) {
                        switch (true) {
                            case is_string($item):
                            case is_array($item) && is_string($item[1]):
                                $sql = substr_replace($sql, '"' . $item . '"', $position, 1);
                                break;
                            default:
                                $sql = substr_replace($sql, $item, $position, 1);
                        }
                    }
                } else {
                    switch (true) {
                        case is_string($item):
                        case is_array($item) && is_string($item[1]):
                            $sql = str_replace($key, '"' . $key . '"', $sql);
                            break;
                        default:
                    }
                }
            }

            if ($method & (self::METHOD_SELECT | self::METHOD_UPDATE | self::METHOD_DELETE)) {
                $sql = str_replace(array_keys($PARAM), array_values($PARAM), $sql);
            }
        }

        return $sql;
    }

    /**
     * 获取sql记录
     * @param int|bool $index
     * @return mixed|string
     */
    public function sql($index = false)
    {
        if ($index === true) {
            return $GLOBALS['_sql'];
        } else if (is_numeric($index) && $index >= 0) {
            return isset($GLOBALS['_sql'][$index]) ? $GLOBALS['_sql'][$index] : '';
        } else if (is_numeric($index) && $index < 0) {
            $pos = count($GLOBALS['_sql']) + $index - 1;
            return isset($GLOBALS['_sql'][$pos]) ? $GLOBALS['_sql'][$pos] : '';
        } else {
            return end($GLOBALS['_sql']);
        }
    }

    /**
     * 获取sql执行数
     * @return int
     */
    public function sqlCount()
    {
        return isset($GLOBALS['_sql']) ? count($GLOBALS['_sql']) : 0;
    }

    /**
     * 启动事务
     * @access public
     */
    public function startTrans()
    {
        ++$this->transactions;
        if ($this->transactions == 1) {
            $this->db->startTransaction();
        }
    }

    /**
     * 提交事务
     * @access public
     */
    public function commit()
    {
        if ($this->transactions == 1) $this->db->commit();
        --$this->transactions;
    }

    /**
     * 事务回滚
     * @access public
     */
    public function rollback()
    {
        if ($this->transactions == 1) {
            $this->transactions = 0;
            $this->db->rollback();
        } else {
            --$this->transactions;
        }
    }

    /**
     * when insert and update,let the data auto completed.
     * @param array $data
     * @param int $action
     */
    protected function _autoComplete(&$data, $action = 0)
    {
        // Parent doesn't need to do anything
    }

    /**
     * when insert and update,validating the data.
     * @param array $data
     * @param int $action
     * @return bool
     */
    protected function _autoValidate(&$data, $action = 0)
    {
        // Parent doesn't need to do anything
        return true;
    }

    /**
     * get the error info
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * get the primary key
     * @return string
     */
    public function getPk()
    {
        return $this->pk;
    }

    // ---------- MAGIC ----------

    public function __call($method, $args)
    {
        if (in_array(strtolower($method), array('count', 'sum', 'min', 'max', 'avg'), true)) {
            $field = isset($args[0]) ? $args[0] : '*';
            return $this->getField(strtoupper($method) . '(' . $field . ')');
        } elseif (strtolower(substr($method, 0, 5)) == 'getby') {
            // 根据某个字段获取记录
            $field = parse_name(substr($method, 5));
            $where[$field] = $args[0];
            return $this->where($where)->find();
        } elseif (strtolower(substr($method, 0, 10)) == 'getfieldby') {
            // 根据某个字段获取记录的某个值
            $name = parse_name(substr($method, 10));
            $where[$name] = $args[0];
            return $this->where($where)->getField($args[1]);
        } else {
            throw new exception2('call to undefined method: ' . $method . '()');
        }
    }

    /**
     * 设置数据对象的值
     * @access public
     * @param string $name 名称
     * @param mixed $value 值
     * @return void
     */
    public function __set($name, $value)
    {
        // 设置数据对象属性
        $this->data[$name] = $value;
    }

    /**
     * 获取数据对象的值
     * @access public
     * @param string $name 名称
     * @return mixed
     */
    public function __get($name)
    {
        return isset($this->data[$name]) ? $this->data[$name] : null;
    }

    /**
     * 检测数据对象的值
     * @access public
     * @param string $name 名称
     * @return boolean
     */
    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    /**
     * 销毁数据对象的值
     * @access public
     * @param string $name 名称
     * @return void
     */
    public function __unset($name)
    {
        unset($this->data[$name]);
    }

    // ---------- FUNCTION ----------

    /**
     * SQL指令安全过滤
     * @access public
     * @param string $str SQL指令
     * @return string
     */
    public function escapeString($str)
    {
        return str_ireplace("'", "''", $str);
    }

    /**
     * 得到完整的数据表名
     * @access public
     * @return string
     */
    public function getTableName()
    {
        if (empty($this->trueTableName)) {
            $tableName = !empty($this->tablePrefix) ? $this->tablePrefix : '';
            if (!empty($this->tableName)) {
                $tableName .= $this->tableName;
            } else {
                $tableName .= parse_name($this->name);
            }
            $this->trueTableName = strtolower($tableName);
        }
        return (!empty($this->dbName) ? $this->dbName . '.' : '') . $this->trueTableName;
    }

    /**
     * 得到当前的数据对象名称
     * @access public
     * @return string
     */
    public function getModelName()
    {
        if (empty($this->name)) {
            // 当有模型后缀的时候要清除后缀
            // 比如 UserModel 要清除Model
            // $name = substr(get_class($this), 0, 0);
            $name = get_class($this);

            if ($pos = strrpos($name, '\\')) {//有命名空间
                $this->name = substr($name, $pos + 1);
            } else {
                $this->name = $name;
            }
        }
        return $this->name;
    }

    /**
     * 自动检测数据表信息
     * @access protected
     * @return void
     */
    protected function _checkTableInfo()
    {
        // 如果不是Model类 自动记录数据表信息
        // 只在第一次执行记录
        if (empty($this->fields)) {
            // 如果数据表字段没有定义则自动获取
            if (true) { // defined('DB_FIELDS_CACHE') && DB_FIELDS_CACHE 现在默认开启
                $db = $this->dbName ? $this->dbName . '.' : '';
                $group = '_fields';
                $key = strtolower($db . $this->tablePrefix . $this->name);
                $fields = $GLOBALS['redis']->hGet($group, $key);

                if ($fields) {
                    $fields = json_decode($fields, true);
                    $this->fields = $fields;
                    if (!empty($fields['_pk'])) {
                        $this->pk = $fields['_pk'];
                    }
                    return;
                }
            }
            // 每次都会读取数据表信息
            $this->flush();
        }
    }

    /**
     * 取得数据表的字段信息
     * @param $tableName
     * @access public
     * @return array
     */
    public function getFields($tableName)
    {
        list($tableName) = explode(' ', $tableName);
        if (strpos($tableName, '.')) {
            list($dbName, $tableName) = explode('.', $tableName);
            $sql = 'SHOW COLUMNS FROM `' . $dbName . '`.`' . $tableName . '`';
        } else {
            $sql = 'SHOW COLUMNS FROM `' . $tableName . '`';
        }

        $result = $this->db->getAll($sql);

        $info = array();
        if ($result) {
            foreach ($result as $key => $val) {
                $val = array_change_key_case($val, CASE_LOWER);
                $info[$val['field']] = array(
                    'name' => $val['field'],
                    'type' => $val['type'],
                    'notnull' => (bool)($val['null'] === ''), // not null is empty, null is yes
                    'default' => $val['default'],
                    'primary' => (strtolower($val['key']) == 'pri'),
                    'autoinc' => (strtolower($val['extra']) == 'auto_increment'),
                );
            }
        }
        return $info;
    }

    /**
     * 获取字段信息并缓存
     * @access public
     * @return void
     */
    public function flush()
    {
        // 缓存不存在则查询数据表信息
        $fields = $this->getFields($this->getTableName());
        // 无法获取字段信息
        if (!$fields) {
            return;
        }
        $this->fields = array_keys($fields);
        unset($this->fields['_pk']);
        $type = [];
        foreach ($fields as $key => $val) {
            // 记录字段类型
            $type[$key] = $val['type'];
            if ($val['primary']) {
                // 增加复合主键支持
                if (isset($this->fields['_pk']) && $this->fields['_pk'] != null) {
                    if (is_string($this->fields['_pk'])) {
                        $this->pk = array($this->fields['_pk']);
                        $this->fields['_pk'] = $this->pk;
                    }
                    $this->pk[] = $key;
                    $this->fields['_pk'][] = $key;
                } else {
                    $this->pk = $key;
                    $this->fields['_pk'] = $key;
                }
                if ($val['autoinc']) $this->autoinc = true;
            }
        }
        // 记录字段类型信息
        $this->fields['_type'] = $type;

        // 缓存开关控制
        if (true) { // defined('DB_FIELDS_CACHE') && DB_FIELDS_CACHE 现在默认开启
            // 永久缓存数据表信息
            $db = $this->dbName ? $this->dbName . '.' : '';
            $group = '_fields';
            $key = strtolower($db . $this->tablePrefix . $this->name);
            $GLOBALS['redis']->hSet($group, $key, json_encode($this->fields, JSON_UNESCAPED_UNICODE));
        }
    }
}