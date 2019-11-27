<?php
namespace Nezimi\db\builder;

use Nezimi\db\Builder;
use Nezimi\db\Query;

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