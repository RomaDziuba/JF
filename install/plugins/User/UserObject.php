<?php 
class UserObject extends Object
{
    public function get($search = array())
    {
        $sql = $this->getSelectSQL($search);
       
        return $this->getRow($sql);
    }
    
    protected function getSql()
    {
        $sql = "SELECT * FROM users";
        
        return $sql;
    }
    
    public function add($values)
    {
        return $this->insert('users', $values);
    }
    
    public function change($data, $where)
    {
        $this->update('users', $data, $where);
    }
    
    public function search($search = array())
    {
        $sql = $this->getSelectSQL($search);
        
        return $this->getAll($sql);
    }
}
?>