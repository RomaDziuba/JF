<?php 
abstract class SqlJimboPlugin extends BaseJimboPlugin
{
    protected $db;
    
    protected $reservedWords = array(
        'NOW()',
        'NOT NULL',
        'NULL',
        'CURRENT_DATE()',
        'CURRENT_TIME()',
        'CURRENT_DATE',
        'CURRENT_TIME',
        'NOW'
    );
    
    public function onInit()
    {
        $this->db = $this->getDB();
    }
    
    abstract function &getDB();
    
    public function insert($table, $values, $is_update_dublicate = false)
    {
        $sql = $this->getInsertSQL($table, $values, $is_update_dublicate);
    
        $this->query($sql);
    
        return $this->getInsertID();
    }
    
    public function delete($table, $condition)
    {
        $where = $this->getSqlCondition($condition);
    
        $sql = "DELETE FROM ".$table." WHERE ".join(" AND ", $where);
    
        return $this->query($sql);
    }
    
    /**
     * Generate an mass insert
     *
     * @param string $table
     * @param mixed $values
     * @throws DatabaseException
     * @return mixed
     */
    public function massInsert($table, $values)
    {
        $rows = array();
        foreach ($values as $items) {
            $data = $this->getInsertValues($items);
            $rows[] = '('.join(', ', $data).')';
        }
         
        $sql = "INSERT INTO ".$table." (".join(", ", array_keys($values[0])).") VALUES ".join(', ', $rows);
    
        return $this->query($sql);
    } // end massInsert
    
    /**
     * Returns quoted values for insert sql
     *
     * @param array $values
     * @see Object::getInsertSQL()
     * @see Object::massInsert()
     * @return array
     */
    private function getInsertValues($values)
    {
        foreach ($values as &$item) {
            if ( is_null($item) ) {
                $item = 'NULL';
                continue;
            }
    
            if ( !in_array($item, $this->reservedWords) ) {
                $item = $this->quote($item);
            }
        }
        unset($item);
    
        return $values;
    } // end getInsertValues
    
    public function update($table, $values, $condition = array())
    {
        $sql = $this->getUpdateSQL($table, $values, $condition);
    
        return $this->query($sql);
    }
    
    /**
     * Returns the generated SQL query to insert data
     *
     * @param string $table
     * @param array $values
     * @param boolean $is_update_dublicate
     * @return string SQL string
     */
    public function getInsertSQL($table, $values, $isUpdateDublicate = false)
    {
        $values = $this->getInsertValues($values);
    
        $sql = "INSERT INTO ".$table." (".join(", ", array_keys($values)).") VALUES (".join(", ", $values).")";
    
        if ($isUpdateDublicate) {
            $sql .= " ON duplicate KEY UPDATE ";
            $rows = array();
            foreach ($values as $field => $value) {
                $rows[] = $field." = ".$value;
            }
    
            $sql .= join(", ", $rows);
        }
    
        return $sql;
    } // end getInsertSQL
    
    /**
     * Returns the generated SQL query to update the data
     *
     * @param string $table
     * @param array $values
     * @param string|array $where
     * @return string
     */
    public function getUpdateSQL($table, $values, $condition = array())
    {
        foreach ($values as $key => &$item) {
    
            if (is_null($item)) {
                $item = $key." = NULL";
                continue;
            }
    
            $lastSymbol = mb_substr($key, mb_strlen($key) - 1, 1);
            if (in_array($lastSymbol, array('+', '-'))) {
                $key = str_replace($lastSymbol, "", $key);
                $item = $key." = ".$key." ".$lastSymbol." ".$this->quote($item);
                continue;
            }
    
            if (!$this->reservedWords || !in_array($item, $this->reservedWords)) {
                $item = $key." = ".$this->quote($item);
            } else {
                $item = $key." = ".$item;
            }
        }
        unset($item);
    
        $sql = "UPDATE ".$table." SET ".join(", ", $values);
    
        if (is_array($condition)) {
            $sqlCondition = $this->getSqlCondition($condition);
            if($sqlCondition) {
                $sql .= " WHERE ".join(' AND ', $sqlCondition);
            }
        } else {
            $sql .= " WHERE ".$condition;
        }
    
        return $sql;
    } // end getUpdateSQL
    
    public function getSqlCondition($obj = array(), $is_default = true)
    {
        $result = $is_default ? array('TRUE') : array();
    
        foreach ($obj as $key => $item) {
            $buffer = explode("&", $key);
            $action = !empty($buffer[1]) ? $buffer[1] : "=";
    
            if (in_array($action, array('IN', 'NOT IN'))) {
                $values = array();
                if (!$item) {
                    continue;
                }
                $item = is_scalar($item) ? array($item) : $item;
                foreach($item as $val) {
                    $values[] = $this->quote($val);
                }
    
                if($values) {
                    $result[] = $buffer[0]." ".$action." (".join(', ', $values).')';
                }
                continue;
            }
    
            if(strtolower($action) == 'or_sql') {
                $result[] = '('.join(' OR ', $item).')';
                continue;
            }
    
            if ($key == 'sql_or') {
    
                $search = array();
                foreach ($item as $row) {
                    if (is_scalar($row)) {
                        $search[] = $row;
                    } else {
                        $search[] = join(' AND ', $this->getSqlCondition($row, false));
                    }
                }
    
                $result[] = '(('.join(' ) OR (', $search).'))';
                continue;
            }
    
            if ($key == 'sql_and') {
    
                $search = array();
                foreach ($item as $row) {
                    if (is_scalar($row)) {
                        $search[] = $row;
                    } else {
                        $search[] = join(' AND ', $this->getSqlCondition($row, false));
                    }
                }
    
                $result[] = join(' AND ', $search);
                continue;
            }
    
    
            if (strtolower($action) == 'or') {
    
                list($value, $others) = $item;
    
                $action = empty($buffer[2]) ? '&=' : '&'.$buffer[2];
    
                $condition = array($buffer[0].$action => $value);
                $ors = $this->getSqlCondition($condition, false);
    
                $others = $this->getSqlCondition($others, false);
                $conditions = array_merge($ors, $others);
                $result[] = '('.join(' OR ', $conditions).')';
    
                continue;
            }
    
            if (!in_array($item, $this->reservedWords)) {
                $result[] = $buffer[0]." ".$action." ".$this->quote($item);
            } else {
                $result[] = $buffer[0]." ".$action." ".$item;
            }
        }
    
        return $result;
    } // end getSqlCondition
    
}
?>