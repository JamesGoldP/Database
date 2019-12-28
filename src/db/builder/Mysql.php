<?php
namespace zero\db\builder;

use zero\db\Builder;
use zero\db\Query;

class Mysql extends Builder
{

    /**
     * 
     */
    protected $selectSql = 'SELECT %FIELD% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT%'; 

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
    protected $insertSql = '%INSERT% INTO %TABLE%(%FIELD%) values(%VALUES%)';
    
    public function parseKey(Query $query, $key)
    {
        return $this->addSymbol($key, '`');
    }
}