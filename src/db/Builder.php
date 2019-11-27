<?php
/**
 * Created by PhpStorm.
 * User: PengYilong
 * Date: 2018/10/27
 * Time: 2:07 AM
 */

namespace Nezimi\db;
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
            $this->parseTable($options['table']), 
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
            $this->parseField($options['field']), 
            $this->parseTable($options['table']), 
            $this->parseJoin($options['join']), 
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
            $this->parseTable($options['table']), 
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
        $search = ['%INSERT%', '%TABLE%', '%FIELD%', '%VALUES%'];
        $replace = [
            $method, 
            $this->parseTable($options['table']), 
            $field_str, 
            $values_str
        ];
        return str_replace($search, $replace, $this->insertSql);
    }

    public function parseData(Query $query, $data)
    {
        $result = [];

        $binds = $this->connection->getTableInfo($query->getOptions('table'), 'bind');

        foreach($data as $key=>$value){
            $k = $this->parseKey($query, $key);
            $bindType = $binds[$k] ?? PDO::PARAM_STR;
            $val = $query->bind($value, $bindType);
            $result[$k] = $val; 
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
    public function parseField($data){
        $str = '';
        if( is_string($data) && trim($data) == '*'){
            $str = '*';
        } else if( is_string($data) ){
            $arr = explode(',', $data);
            $str = implode(',', $arr);
        } else if( is_array($data)  ){
            $str = implode(',', $data);
        } else {
            $str = '*';
        }
        return $str;
    }

    /**
     * Parse field
     *
     * @param string or array
     *
     * @return string
     */
    public function parseTable($str){
        return $str;
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
     *
     *
     * @param
     *
     * @return string
     *
     */
    public function parseJoin($data, $condition = NULL, $type = 'INNER')
    {
        $str = '';
        if( !empty($data) ){
            $str = ' '.$type.' JOIN '.$data.' ON '.$condition;
        }
        return $str;
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
     * @param string $limit
     *
     * @return string
     *
     */
    public function parseLimit($limit)
    {
        $limit_str = '';
        if( $limit == '' ){
            return $limit_str;
        } else if( is_string($limit) || is_numeric($limit) ){
            $limit_str = ' LIMIT '.$limit;
        } else if( is_array($limit) ){
            if( count($limit)==1 ){
                $limit_str = ' LIMIT '.$limit[0];
            } else {
                $limit_str = ' LIMIT '.$limit[0].','.$limit[1];
            }
        }
        return $limit_str;
    }

    public function parseCompare(Query $query, $field, $operator, $value, $bindType)
    {
        return $field . ' ' . $operator . ' ' . $value;
    }

    public function parseLike(Query $query, $field, $operator, $value, $bindType)
    {
        return $field . ' ' . $operator . ' ' . $value;
    }

    public function parseBetween(Query $query, $field, $operator, $value, $bindType)
    {
        $data = is_array($value) ? $value : explode(',', $value);
        $min = $query->bind($data[0], $bindType);
        $max = $query->bind($data[1], $bindType);
        return $field . ' ' . $operator . ' ' . $min . ' AND ' . $max;
    }
}