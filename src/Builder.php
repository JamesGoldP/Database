<?php
/**
 * Created by PhpStorm.
 * User: PengYilong
 * Date: 2018/10/27
 * Time: 2:07 AM
 */

namespace Nezumi;

class Builder{

    /**
     * 
     */
    protected $selectSql = 'SELECT %FIELDS% FROM %TABLE% %JOIN% %WHERE% %GROUP% %HAVING% %ORDER% %LIMIT%'; 

    /**
     * 
     */
    protected $deleteSql = 'DELETE FROM %TABLE% %WHERE%';  
    
    /**
     * 
     */
    protected $updateSql = 'UPDATE %TABLE% SET %DATA% %WHERE%';  
    
    /**
     * 
     */
    protected $insertSql = '%INSERT% INTO %TABLE%(%FIELDS% ) values(%VALUES%)';  
   
    /**
     * 
     */
    public function delete($query)
    {
        $options = $query->options;
        $search = ['%TABLE%', '%WHERE%'];
        $replace = [$options['table'], $options['where']];
        return str_replace($search, $replace, $this->deleteSql);
    }

    /**
     * 
     */
    public function select($query)
    {
        $options = $query->options;
        $search = ['%FIELDS%', '%TABLE%', '%JOIN%', '%WHERE%', '%GROUP%', '%HAVING%', '%ORDER%', '%LIMIT%'];
        $replace = [$options['fields'], $options['table'], $options['join'], $options['where'], $options['group'], $options['having'], $options['order'], $options['limit']];
        return str_replace($search, $replace, $this->selectSql);
    }

    /**
     * 
     */
    public function update($query)
    {
        $options = $query->options;
        $data_sql = '';
        $data = $options['data'];
        foreach ($data as $key => $values) {
            $data_sql .= $this->addBackquote($key).'='.$this->addQuotes($values).',';
        }
        $data_sql = substr($data_sql, 0, -1);
        $sql = 'UPDATE '.$options['table'].' SET '.$data_sql.$options['where'];
        $search = ['%TABLE%', '%DATA%', '%WHERE%'];
        $replace = [$options['table'], $data_sql, $options['where']];
        return str_replace($search, $replace, $this->updateSql);
    }
    
    /**
     * 
     */
    public function insert($query, $replace)
    {
        $options = $query->options;
        $data = $options['data'];
        $fields = array_keys($data);
        $values = array_values($data);

        array_walk($fields, [$this, 'addBackquote']);
        array_walk($values, [$this, 'addQuotes']);

        $fields_str = implode(',', $fields);
        $values_str = implode(',', $values);
        $method = $replace ? 'REPLACE' : 'INSERT';
        // $insert_sql = $method.' INTO '.$this->options['table'].'('.$fields_str.')'.' values('.$values_str.')';
        $search = ['%INSERT%', '%TABLE%', '%FIELDS%', '%VALUES%'];
        $replace = [$method, $options['table'], $fields_str, $values_str];
        return str_replace($search, $replace, $this->insertSql);
    }

    /**
     * Add backquote
     *
     * @param string $fields
     *
     * @return string
     *
     */
    public function addBackquote(&$value){
        if( strpos($value,'`') === false ){
            $value = '`'.trim($value).'`';
        }
        return $value;
    }

    /**
     * Add ''
     *
     * @param string $fields
     *
     * @return string
     *
     */
    public function addQuotes(&$value, $key = '' , $user_data = '', $quotation=1){
        if($quotation){
            $quot = '\'';
        } else {
            $quot = '';
        }
        $value = $quot.$value.$quot;
        return $value;
    }


    /**
     * Parse fields
     *
     * @param string or array
     *
     * @return string
     */
    public function parseFields($data){
        $str = '';
        if( is_string($data) && trim($data)== '*'){
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
     * Parse fields
     *
     * @param string or array
     *
     * @return string
     */
    public function parseTable($str){
        return $str;
    }

    /**
     * Parse where
     *
     * @param string $where
     *
     * @return string
     *
     */
    public function parseWhere($data)
    {
        $str = '';
        if( $data == '' ){
            return $str;
        } else if( is_string($data) ){
            $str = ' WHERE '.$data;
        } else if( is_array($data) ){
            $i = 0;
            $str .= ' WHERE ';
            foreach ($data as $key => $values) {
                $link = $i!=0 ? ' AND ' : '';
                $str .= $link.$this->addBackquote($key).'='.$this->addQuotes($values);
                $i++;
            }
        }
        return $str;
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
        $group_str = '';
        if( $group == '' ){
            return $group_str;
        } else if( is_string($group) ){
            $group_str = ' GROUP BY '.$group;
        } else if( is_array($group) ){
            $group_str = ' GROUP BY '.implode(',', $group);
        }
        return $group_str;
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
        $having_str = '';
        if( $having == '' ){
            return $having_str;
        } else if( is_string($having) ){
            $having_str = ' HAVING '.$having;
        }
        return $having_str;
    }

    /**
     *
     *
     * @param
     *
     * @return string
     *
     */
    public function parseJoin($data)
    {
        $str = '';
        if( $data == '' ){
            return $str;
        } else if( is_string($data) ){
            $str = ' LEFT JOIN '.$data;
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
        $order_str = '';
        if( $order == '' ){
            return $order_str;
        } else if( is_string($order) ){
            $order_str = ' ORDER BY '.$order;
        } else if( is_array($order) ){
            $order_str = ' ORDER BY '.implode(',', $order);
        }
        return $order_str;
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
}