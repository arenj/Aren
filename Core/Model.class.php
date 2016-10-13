<?php
namespace Aren\Core;

use PDO;
use PDOException;

class Model
{
    /**
     * 数据库配置信息
     * @var array
     */
    protected $config = [];
    protected $cache;
    /**
     * 主库数据库对象
     * @var \PDO
     */
    private $master;
    /**
     * 从库数据库对象
     * @var \PDO
     */
    private $slave;
    /**
     * 当前数据库对象
     * @var \PDO
     */
    private $pdo;

    /**
     * 数据集对象
     * @var \PDOStatement
     */
    private $stmt;

    /**
     * 标识当前连接的数据库类型
     * @var
     */
    private $flag;

    private $sql;

    private $data = array();

    private $condition = array();

    private $isFirstSet = true;

    private $inCondition = false;

    private $conditionIsTrue = false;

    public $magicQuote;

    /**
     * 构造函数
     * @internal param array $config 配置信息
     */
    public function __construct()
    {
        #构造时添加配置数据
        $this->config['master'] = Config::get('DB.master');
        $this->config['slave'] = Config::get('DB.slave');
        $this->magicQuote = get_magic_quotes_gpc();
        //初始设为主库
        $this->flag = 'master';
    }

    public function getConnect()
    {
        if($this->flag == 'slave'){
            $this->flag = 'slave';
            if(is_null($this->slave)){
                $this->slave = $this->connectMySQL($this->flag);
            }
            $this->pdo = $this->slave;
        }else{
            $this->flag = 'master';
            if(is_null($this->master)){
                $this->master = $this->connectMySQL($this->flag);
            }
            $this->pdo = $this->master;
        }
        return $this->pdo;
    }
    /**
     * 连接到MySQL
     * @param $type
     * @return bool
     */
    private function connectMySQL($type)
    {
        try {

            $dsn = 'mysql:host=' . $this->config[$type]['host'] . ';port=' . $this->config[$type]['port'] . ';dbname=' . $this->config[$type]['dbname'];
            #MySQL需要处理编码问题
            $options = array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                PDO::ATTR_CASE => PDO::CASE_NATURAL,
                PDO::ATTR_PERSISTENT => true, //长连接,
                PDO::MYSQL_ATTR_INIT_COMMAND => 'set names ' . $this->config[$type]['charset']
            );
            $link = new PDO($dsn, $this->config[$type]['user'], $this->config[$type]['password'], $options);
            return $link;
        } catch (PDOException $e) {
            echo 'Errors occur when query data!\n';
            echo $e->getMessage();
        }
    }

    /**
     * 数据类型绑定
     * @var array
     */
    protected $paramType = array(
        'bool' => PDO::PARAM_BOOL,
        'integer' => PDO::PARAM_INT,
        'float' => PDO::PARAM_STR,
        'string' => PDO::PARAM_STR,
        'lob' => PDO::PARAM_LOB,
        'binary' => PDO::PARAM_LOB,
        'null' => PDO::PARAM_NULL,
        'other' => PDO::PARAM_STR
    );

    /**
     * 获取数据类型
     * @param mixed $value
     * @return int
     */
    private function getDataType($value)
    {
        $type = gettype($value);
        $type = $this->paramType[$type];
        return !$type ? $this->paramType['other'] : $type;
    }

    /**
     * 最后添加的ID
     * @return string
     */
    public function lastId()
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * 返回上一个SQL语句影响的行数
     * @return int
     */
    public function rowCount()
    {
        return !$this->stmt ? 0 : $this->stmt->rowCount();
    }

    /**
     * 选择字段
     * @param string $fields
     * @return $this
     */
    public function select($fields = '*')
    {
        if(Config::get('DB.mode') === 'r-w'){
            $this->flag = 'slave';
        }else{
            $this->flag = 'master';
        }
        $this->sql = "SELECT $fields ";
        return $this;
    }

    public function from($table)
    {
        $this->sql .= " FROM `" . $this->config[$this->flag]['prefix'] . $table . "` ";
        return $this;
    }

    public function alias($alias)
    {
        $this->sql .= " AS `$alias` ";
        return $this;
    }

    public function data($data)
    {
        $this->data = $data;
        foreach ($data as $field => $value) {
            $this->sql .= " `$field` = :" . $field . ',';
        }
        $this->sql = rtrim($this->sql, ',');    // Remove the last ','.
        return $this;
    }

    public function beginIF($condition)
    {
        $this->inCondition = true;
        $this->conditionIsTrue = $condition;
        return $this;
    }


    public function fi()
    {
        $this->inCondition = false;
        $this->conditionIsTrue = false;
        return $this;
    }

    public function where($field, $operator, $value, $isAnd = false)
    {
        if ($this->inCondition and !$this->conditionIsTrue) return $this;
        if ($isAnd) {
            $where = ' AND ';
        } else {
            $where = ' WHERE ';
        }
        //处理表别名
        $operator = strtoupper(trim($operator));
        switch ($operator) {
            case 'IN':
                if (is_array($value)) {
                    $in = "IN ('" . join("','", $value) . "')";
                } else {
                    $in = "IN ('" . str_replace(',', "','", str_replace(' ', '', $value)) . "')";
                }
                if (strpos($field, '.') === false) {
                    $this->sql .= " $where `$field` " . $in;
                } else {
                    list($alias, $fieldx) = explode('.', $field);
                    $this->sql .= $where . $alias . '.`' . $fieldx . '` ' . $in;
                }
                break;
            case 'NOT IN':
                if (is_array($value)) {
                    $in = "NOT IN ('" . join("','", $value) . "')";
                } else {
                    $in = "NOT IN ('" . str_replace(',', "','", str_replace(' ', '', $value)) . "')";
                }
                if (strpos($field, '.') === false) {
                    $this->sql .= " $where `$field` " . $in;
                } else {
                    list($alias, $fieldx) = explode('.', $field);
                    $this->sql .= $where . $alias . '.`' . $fieldx . '` ' . $in;
                }
                break;
            default:
                if (strpos($field, '.') === false) {
                    $this->condition['oCx_' . $field] = $value;
                    $this->sql .= " $where `$field` $operator :oCx_" . $field;
                } else {
                    list($alias, $fieldx) = explode('.', $field);
                    $this->condition['oCx_' . $alias . '_' . $fieldx] = $value;
                    $this->sql .= $where . $alias . '.`' . $fieldx . '` ' . $operator . ' :oCx_' . $alias . '_' . $fieldx;
                }
                break;
        }
        // select * from blog where id IN (1,2,3);

        return $this;
    }

    public function andWhere($field, $operator, $value)
    {
        return $this->where($field, $operator, $value, true);
    }

    public function batchWhere($fields)
    {
        foreach ($fields as $key => $field) {
            if ($key == 0) {
                $this->where($field[0], $field[1], $field[2]);
            } else {
                $this->andWhere($field[0], $field[1], $field[2]);
            }
        }
        return $this;
    }

    public function set($set)
    {
        if ($this->isFirstSet) {
            $this->sql .= " $set ";
            $this->isFirstSet = false;
        } else {
            $this->sql .= ", $set";
        }
        return $this;
    }

    public function leftJoin($table)
    {
        $this->sql .= ' LEFT JOIN `' . $this->config[$this->flag]['prefix'] . $table . '`';
        return $this;
    }

    public function rightJoin($table)
    {
        $this->sql .= ' RIGHT JOIN `' . $this->config[$this->flag]['prefix'] . $table . '`';
        return $this;
    }

    public function innerJoin($table)
    {
        $this->sql .= ' INNER JOIN `' . $this->config[$this->flag]['prefix'] . $table . '`';
        return $this;
    }

    public function on($condition)
    {
        $this->sql .= " ON $condition ";
        return $this;
    }




    public function update($table)
    {
        $this->sql = "UPDATE " . $this->config[$this->flag]['prefix'] . $table . " SET ";
        return $this;
    }

    public function insert($table)
    {
        $this->sql = "INSERT INTO " . $this->config[$this->flag]['prefix'] . $table . " SET ";
        return $this;
    }

    public function replace($table)
    {
        $this->sql = "REPLACE " . $this->config[$this->flag]['prefix'] . $table . " SET ";
        return $this;
    }

    public function delete()
    {
        $this->sql = "DELETE ";
        return $this;
    }

    public function order($order)
    {
        $order = str_replace(array('|', '', '_'), ' ', $order);
        $order = str_replace('left', '`left`', $order); // process the left to `left`.

        $this->sql .= " ORDER BY $order";
        return $this;
    }

    public function limit($start, $pagesize = 0)
    {
        if ($pagesize == 0) {
            $this->sql .= " LIMIT $start ";
        } else {
            $this->sql .= " LIMIT " . ($start - 1) * $pagesize . ",  $pagesize ";
        }
        return $this;
    }

    public function quote($value)
    {
        $this->getConnect();
        if ($this->magicQuote) $value = stripslashes($value);
        return $this->pdo->quote($value);
    }

    public function exec()
    {
        $this->getConnect();
        if (empty($this->data) && empty($this->condition)) {
            $this->reset();
            return $this->pdo->exec($this->sql);
        } else {
            //合并绑定数据
            if (!empty($this->condition)) {
                $param = array_merge($this->data, $this->condition);
            } else {
                $param = $this->data;
            }
            $stmt = $this->pdo->prepare($this->sql);
            foreach ($param as $name => $value) {
                #按照类型绑定数据
                $stmt->bindValue(':' . $name, $value, $this->getDataType($value));
            }
            //print_r($param);
            $this->reset();
            return $stmt->execute();
        }
    }

    public function fetch()
    {
        $this->getConnect();
        if ($this->condition) {
            $this->stmt = $this->pdo->prepare($this->sql);
            foreach ($this->condition as $name => $value) {
                $this->stmt->bindValue(':' . $name, $value, $this->getDataType($value));
            }
            $this->stmt->execute();
            $this->reset();
            return $this->stmt->fetch();
        }
        $this->stmt = $this->pdo->query($this->sql);
        $this->reset();
        return $this->stmt->fetch();
    }

    public function fetchAll()
    {
        $this->getConnect();
        if ($this->condition) {
            $this->stmt = $this->pdo->prepare($this->sql);
            foreach ($this->condition as $name => $value) {
                $this->stmt->bindValue(':' . $name, $value, $this->getDataType($value));
            }
            $this->stmt->execute();
            $this->reset();
            return $this->stmt->fetchAll();
        }
        $this->stmt = $this->pdo->query($this->sql);
        $this->reset();
        return $this->stmt->fetchAll();
    }

    /**
     * 执行事务, 回调函数返回false则回滚事务
     * $actions 回调函数 $db->action(function($db){$db->insert(...)})
     * @param $actions
     * @return bool
     */
    public function action($actions)
    {
        if (is_callable($actions)) {
            $this->getConnect();
            $this->pdo->beginTransaction();
            $result = $actions($this);
            if ($result === false) {
                $this->pdo->rollBack();
            } else {
                $this->pdo->commit();
            }
        } else {
            return false;
        }
    }

    /**
     * 执行原生SQL语句
     * @param $sql
     * @return \PDOStatement
     */
    public function query($sql)
    {
        if(Config::get('DB.mode') === 'r-w' && strtolower(substr(trim($sql), 0, 6)) == 'select'){
            $this->flag = 'slave';
        }else{
            $this->flag = 'master';
        }
        return $this->getConnect()->query($sql);
    }

    private function reset()
    {
        $this->sql = '';
        $this->data = [];
        $this->condition = [];
        $this->isFirstSet = true;
        $this->inCondition = false;
        $this->conditionIsTrue = false;
    }
    /** ================================================================================= */

    /**
     * destruct 关闭数据库连接
     */
    public function destruct()
    {
        $this->pdo = null;
    }

}
