<?php
/**
 * Created by PhpStorm.
 * User: PengYilong
 * Date: 2018/10/27
 * Time: 2:07 AM
 */

namespace zero\db;
use Exception;
use PDO;

class Builder{

    /**
     * @var object Connection
     */
    protected $conncection;

    /**
     * 
     */
    protected $operator = [
        'parseCompare' => ['=', '<>', '<', '<=', '>', '>='],
        'parseLike'    => ['LIKE', 'NOT LIKE'],
        'parseBetween' => ['NOT BETWEEN', 'BETWEEN'],
        'parseIn'      => ['NOT IN', 'IN'],
        'parseNull'    => ['NOT NULL', 'NULL'],
    ];

    protected $opEscaped = [
        'EQ'  => '=',
        'NEQ' => '<>',
        'GT'  => '>',
        'EGT' => '>=',
        'LT'  => '<',
        'ELT' => '<=',
        'NOTLIKE' => 'NOT LIKE',
        'NOTIN' => 'NOT IN',
        'NOTBETWEEN' => 'NOT BETWEEN',
        'NOTEXISTS' => 'NOT EXISTS',
        'NOTNULL' => 'NOT NULL',
        'NOTBETWEEN TIME' => 'NOT BETWEEN TIME', 
    ];
   
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * 
     */
    public function delete($query)
    {
        $options = $query->getOptions();
        $search = ['%TABLE%', '%WHERE%'];
        $replace = [
            $this->parseTable($query, $options['table']), 
            $this->parseWhere($query, $options['where']), 
        ];
        return str_replace($search, $replace, $this->deleteSql);
    }

    /**
     * build a select sql 
     */
    public function select($query)
    {
        $options = $query->getOptions();
        $search = ['%FIELD%', '%TABLE%', '%JOIN%', '%WHERE%', '%GROUP%', '%HAVING%', '%ORDER%', '%LIMIT%'];
        $replace = [
            $this->parseField($query, $options['field']), 
            $this->parseTable($query, $options['table']), 
            $this->parseJoin($query, $options['join']), 
            $this->parseWhere($query, $options['where']), 
            $this->parseGroup($options['group']), 
            $this->parseHaving($options['having']), 
            $this->parseOrder($options['order']), 
            $this->parseLimit($options['limit']),
        ];
        $sql = str_replace($search, $replace, $this->selectSql);
        return $sql;
    }

    /**
     * build a update sql
     */
    public function update(Query $query)
    {
        $options = $query->getOptions();
        $dataSql = '';
        $data = $options['data'];
        foreach ($data as $key => $values) {
            $dataSql .= $this->addSymbol($key, '`') . '=' . $this->addSymbol($values).',';
        }
        $dataSql = substr($dataSql, 0, -1);
        $search = ['%TABLE%', '%DATA%', '%WHERE%'];
        $replace = [
            $this->parseTable($query, $options['table']), 
            $dataSql, 
            $this->parseWhere($query, $options['where']),
        ];
        return str_replace($search, $replace, $this->updateSql);
    }
    
    /**
     * build a insert sql
     */
    public function insert(Query $query, $replace)
    {
        $options = $query->getOptions();
        $data = $options['data'];

        if( empty($data) ){
            return false;
        }
        $data = $this->parseData($query, $data);

        $field = array_keys($data);
        $values = array_values($data);

        $field_str = implode(',', $field);
        $values_str = implode(',', $values);
        $method = $replace ? 'REPLACE' : 'INSERT';
        // $insert_sql = $method.' INTO '.$this->options['table'].'('.$field_str.')'.' values('.$values_str.')';
        $search = ['%INSERT%', '%TABLE%', '%FIELD%', '%DATA%'];
        $replace = [
            $method, 
            $this->parseTable($query, $options['table']), 
            $field_str, 
            $values_str
        ];
        return str_replace($search, $replace, $this->insertSql);
    }

    /**
     * parse data
     *
     * @param Query $query
     * @param array $data   
     * @param array $fields 
     * @param array $bind   绑定类型 like PDO::PARAM_INT|
     * @return void
     */
    public function parseData(Query $query, array $data = [], array $fields = [], $bind = [])
    {
        $result = [];

        if( empty($data) ){
            return $result;
        }

        $options = $query->getOptions();

        if( empty($bind) ){
            $bind = $this->connection->getTableInfo($query->getOptions('table'), 'bind');
        }

        foreach($data as $key => $val){
            $item = $this->parseKey($query, $key);
            if( !in_array($key, $fields, true) ){
                throw new Exception('field not exists:['. $key . ']');
            } elseif( is_scalar($val) ){
                $bindType = $bind[$item] ?? PDO::PARAM_STR;
                $v = $query->bind($val, $bindType);
                $result[$item] = $v; 
            }
            
        }
        return $result;
    }

    /**
     * Add ''
     *
     * @param string $value
     *
     * @return string
     *
     */
    public function addSymbol($value, $symbol = '\''){
        if( strpos($value, $symbol) === false ){
            $value = $symbol.trim($value).$symbol;
        }
        return $value;
    }

    /**
     * Parse field
     *
     * @param string or array
     *
     * @return string
     */
    public function parseField(Query $query, $fields){
        if('*' == $fields || empty($fields)){
            $fieldStr = '*';
        } elseif( is_array($fields) ){
            //支持 字段 => 别名 这样定义字段
            $array = [];
            foreach($fields as $key => $field){
                if( !is_numeric($key) ){
                    $array[] = $this->parseKey($query, $key) . ' AS ' . $this->parseKey($query, $field, true);
                } else {
                    $array[] = $this->parseKey($query, $field);
                }
            }
            $fieldStr = implode(',', $array);
        }
        
        return $fieldStr;
    }

    /**
     * Parse field
     *
     * @param string or array
     *
     * @return string
     */
    public function parseTable(Query $query, $tables){
        $item = [];
        $options = $query->getOptions();

        foreach((array) $tables as $key => $table){
            if( !is_numeric($key) ){
                //通过table 带过来的alias参数
                $key = $this->connection->parseSqlTable($key);
                $item[] = $this->parseKey($query, $key) . ' ' . $this->parseKey($query, $table);
            } else {
                //通过alias函数带过来的
                $table = $this->connection->parseSqlTable($table);

                if( isset($options['alias'][$table]) ){
                    $item[] = $this->parseKey($query, $table) . ' ' . $this->parseKey($query, $options['alias'][$table]);
                } else {
                    $item[] = $this->parseKey($query, $table);
                }
            }
        }
        return implode(',', $item);
    }

    protected function parseWhere(Query $query, $where)
    {
        $whereStr = $this->buildWhere($query, $where);
        //soft delete


        return empty($where) ? '' : ' WHERE ' . $whereStr;
    }

    /**
     * Parse where
     *
     * @param string $where
     *
     * @return string
     *
     */
    public function buildWhere(Query $query, $where)
    {
        if( empty($where) ){
            $data = [];
        }
        $binds = $this->connection->getTableInfo($query->getOptions('table'), 'bind');
        $whereStr = '';
        foreach($where as $logic=>$value){
            $str = [];
            foreach($value as $val){
                if( $val instanceof Expression ){
                    $str[] = ' ' . $logic . ' ( ' . $val->getValue(). ' )';
                    continue;   
                }   

                if( is_array($val) ){
                    $field = array_shift($val);
                } else if( !($val instanceof \Closure) ){
                    throw new Exception('where express error:'. var_export($val, true));
                }
                
                if( $val instanceof \Closure ){
                    //Closure
                    $newQuery = $query->newInstance();
                    $val($newQuery); 
                    $whereClause = $this->buildWhere($query, $newQuery->getOptions('where'));
                    if( !empty($whereClause) ){
                        $str[] = ' ' . $logic . ' ' . '(' . $whereClause .')';
                    }
                } else if( is_array($field) ){  

                } else if( strpos($field, '|') ){ 
                    $array = explode('|', $field); 
                    $item = [];
                    foreach($array as $v){
                        $item[] = $this->parseWhereItem($query, $v, $val, '', $binds);
                    }
                    $str[] = ' ' . $logic . ' ( ' . implode(' OR ', $item). ' ) '; 
                    //OR
                } else if( strpos($field, '&') ){  
                    //AND
                    $array = explode('&', $field); 
                    $item = [];
                    foreach($array as $v){
                        $item[] = $this->parseWhereItem($query, $v, $val, '', $binds);
                    }
                    $str[] = ' ' . $logic . ' ( ' . implode(' AND ', $item). ' ) '; 
                } else {
                    $str[] = ' ' . $logic . ' ' .$this->parseWhereItem($query, $field, $val, $logic, $binds);
                }
            }
            $whereStr .= empty($whereStr) ? substr(implode('', $str), strlen($logic)+2) : implode('', $str);
        }
        return $whereStr;
    }

    public function parseWhereItem(Query $query, $field, $val, $rule = '', $binds = [])
    {
        
        list($op, $value) = $val;
        if( is_array($op) ){
            //同一字段不同条件
            $logic = array_pop($val);
            foreach($val as $v){
                $valStr[] = $this->parseWhereItem($query, $field, $v, '', $binds);
            }
            $valResult = '( ' .implode(' ' . $logic . ' ', $valStr). ' )';
            return $valResult;
        }

        $op = strtoupper($op);
        $op = $this->opEscaped[$op] ?? $op;
        
        $bindType = $binds[$field] ?? PDO::PARAM_STR;

        if( is_scalar($value) && !in_array($op, ['BETWEEN', 'NOT BETWEEN', 'IN', 'NOT IN', 'NULL', 'NOT NULL']) ){
            $value = $query->bind($value, $bindType);
        }
        
        foreach($this->operator as $k => $v){
            if(in_array($op, $v)){
                $whereStr = $this->$k( $query, $this->addSymbol($field, '`'), $op, $value, $bindType);
                break;
            }
        }
        return $whereStr;
    }

    /**
     * Parse group
     *
     * @param string $group
     *
     * @return string
     *
     */
    public function parseGroup($group)
    {
        $str = '';
        if( $group == '' ){
            return $str;
        } else if( is_string($group) ){
            $str = ' GROUP BY '.$group;
        } else if( is_array($group) ){
            $str = ' GROUP BY '.implode(',', $group);
        }
        return $str;
    }

    /**
     * Parse having
     *
     * @param string $having
     *
     * @return string
     *
     */
    public function parseHaving($having)
    {
        $str = '';
        if( $having == '' ){
            return $str;
        } else if( is_string($having) ){
            $str = ' HAVING '.$having;
        }
        return $str;
    }

    /**
     * parse the array of the join
     *
     * @param Query $query
     * @param array $join
     * @return void
     */
    protected function parseJoin(Query $query, array $join)
    {
        $joinStr = '';
        
        foreach($join as $item){
            list($table, $type, $on) = $item;
            $condition = [];

            foreach((array) $on as $val){
                if( strpos($val, '=') ){
                    list($table1, $table2) = explode('=', $val);
                    $condition[] = $this->parseKey($query, $table1) . '=' . $this->parseKey($query, $table2);
                } else {
                    $condition = $val;
                }
            }

            $table = $this->parseTable($query, $table);
            $joinStr .= ' ' . $type .  ' JOIN ' . $table . ' ON ' . implode(' AND ', $condition);
        }
        return $joinStr;
    }

    /**
     * Parse order
     *
     * @param string $order
     *
     * @return string
     *
     */
    public function parseOrder($order)
    {
        $str = '';
        if( $order == '' ){
            return $str;
        } else if( is_string($order) ){
            $str = ' ORDER BY '.$order;
        } else if( is_array($order) ){
            $str = ' ORDER BY '.implode(',', $order);
        }
        return $str;
    }

    /**
     * Parse limit
     *
     * @param mixed $limit
     * @return string
     */
    protected function parseLimit($limit): string
    {
        return $limit ? ' LIMIT ' . $limit : '';
    }

    public function parseCompare(Query $query, string $field, string $operator, $value, $bindType)
    {
        return $field . ' ' . $operator . ' ' . $value;
    }

    public function parseLike(Query $query, string $field, string $operator, $value, $bindType)
    {
        return $field . ' ' . $operator . ' ' . $value;
    }

    public function parseBetween(Query $query, string $field, string $operator, $value, $bindType)
    {
        $data = is_array($value) ? $value : explode(',', $value);
        $min = $query->bind($data[0], $bindType);
        $max = $query->bind($data[1], $bindType);
        return $field . ' ' . $operator . ' ' . $min . ' AND ' . $max;
    }

    protected function parseIn(Query $query, string $field, string $operator, $value, $bindType)
    {
        $value = array_unique( is_array($value) ? $value : explode(',', $value) );
        $array = [];

        foreach ($value as $k => $v) {
            $array[] = $query->bind($v, $bindType);
        }

        if( count($array) == 1 ) {
            return $field . ('IN' == $operator ? ' = ' : ' <> ' ) . $array[0];
        } else {
            $zone = implode(',', $array);
            $value = $zone ?: '\'\'';
        }
        
        return $field . ' ' . $operator .  ' (' . $value . ')';
    }
}