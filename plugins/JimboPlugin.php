<?php 
class JimboPlugin extends Plugin
{
    public function main($table = false)
    {
        global $jimbo, $_sessionData;
        
        if(!defined('ENGINE_URL')) {
            throw new SystemException(_('Undefined ENGINE_URL const'));
        }
        
        if(!$table) {
            $table = DEFAULT_TABLE; 
        }
        
        $_sessionData['DB_CURRENT_TABLE'] = $table; 
        $_sessionData['DBA_SCRIPT'] = $jimbo->urlPrefix.ENGINE_URL.'/';
        
        $sql = "SELECT 
                    dbdrive_perms.value 
                FROM 
                    dbdrive_perms
                    INNER JOIN dbdrive_tables on (id_table = dbdrive_tables.id) 
                WHERE 
                    caption= ".$jimbo->db->quote($table)." 
                    AND id_role = ".$jimbo->user->getGroup();
        
        $perms = $jimbo->db->getOne($sql);    
        
        $GLOBALS['tblAction'] = new dbAction($jimbo->db, $table);
        $tblAction = &$GLOBALS['tblAction'];
        
        if (($perms & 8) == 8) {
            
        } elseif (($perms & 4) == 4) {
            // write
        } elseif (($perms & 2) == 2) {
            // read
            foreach ($tblAction->tableDefinition->actions as $key => $value) {
                if (in_array($key, array('edit', 'insert', 'remove', 'orderedit'))) {
                    unset($tblAction->tableDefinition->actions[$key]);
                }
            } // end foreach
        } else {
            throw new PermissionsException();
        }
        
        $displayer = new dbDisplayer($tblAction);
        $dbLogic = new dbLogic();
        
        $doAction = $dbLogic->detectPerformAction($tblAction);
        $status = $tblAction->performAction($doAction);
        
        $viewAction = $dbLogic->detectViewAction($tblAction, $status);
        
        $content = $displayer->performDisplay($viewAction);
        
        $template = isset($_GET['popup']) ? 'light.ihtml' : 'main.ihtml';
        
        $jimbo->display($content, $template);
    } // end main
    
    public function getFile($table, $filed, $id)
    {
        global $jimbo;
    
        $sql = "SELECT ".$jimbo->db->escape($filed)." FROM ".$jimbo->db->escape($table)." WHERE id = ".$jimbo->db->quote($id);
        $info = $jimbo->db->getOne($sql);
        
        $info = explode(";0;", $info);
        $info = array('filename' => $info[0], 'filetype' => $info[1]);
        if (isset($_GET['thumb'])) {
            $fname = FS_ROOT.'storage/'.$table.'/thumbs/'.$id.'_'.$filed;
        } else {
            $fname = FS_ROOT.'storage/'.$table.'/'.$id.'_'.$filed;
        }
        
        if (empty($info['filename']) || (!is_file($fname)) ) {
            header("HTTP/1.0 404 Not Found");
        } else {
            header('Content-Disposition: attachment; filename="'.$info['filename'].'"');
            header("Content-type:".$info['filetype']);
            $fp = fopen($fname, 'rb');
            fpassthru($fp);
        }
        exit();
    } // end getFile
    
}

?>