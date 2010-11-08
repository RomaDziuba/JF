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
                    AND id_role = ".$jimbo->user->getRole();
        
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
        
        $template = $this->getTemplateName();
        
        if(!$template) {
            echo $content;
            exit();
        }
        
        $jimbo->display($content, $template);
    } // end main
    
    /**
     * Returns the name of the main template
     */
    private function getTemplateName()
    {
        $template = 'main.ihtml';
        
        if(isset($_GET['popup'])) {
            $template = JIMBO_POPUP_MODE == 'popup' ? 'light.ihtml' : false;
        }
        
        return $template;
    } // end getTemplateName
    
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
    
    public function getMenu()
    {
        global $jimbo;
        if(!$jimbo->user->isLogin()) {
            return false;
        }
        
        $id_group = $jimbo->user->getRole();
        
        $sql = "SELECT 
                    m.* 
                FROM 
                    dbdrive_menu_perms p
                    INNER JOIN dbdrive_menu m ON (p.id_menu = m.id)
                WHERE
                    p.id_role = ".$jimbo->db->quote($id_group)."
                ORDER BY 
                    m.id_parent, m.order_n";
        $tmp = $jimbo->db->getAll($sql);
        
        if(PEAR::isError($tmp)) {
            throw new DatabaseException($tmp->getMessage());    
        }

        $menu = array();
        $parents = array();

        foreach ($tmp as $item) {
            
            $parents[$item['id']] = $item['id_parent'];
            if (empty($item['id_parent'])) {
            
                $menu[$item['id']] = array(
                'caption' => $item['caption'],
                'href' => $jimbo->getUrl($item['url']),
                'level' => 1,
                'items' => array()
                );
            } elseif(isset($menu[$item['id_parent']]['level']) && $menu[$item['id_parent']]['level'] == 1) {
            
                $menu[$item['id_parent']]['items'][$item['id']] = array(
                'caption' => $item['caption'],
                'href' => $jimbo->getUrl($item['url']),
                'level' => 2,
                'id_parent' => $item['id_parent'],
                'items' => array()
                );
            } else {
    
                $parent = $item['id_parent'];
                $top = $parents[$parent];
                $menu[$top]['items'][$parent]['items'][] = array(
                'caption' => $item['caption'],
                'href' => $jimbo->getUrl($item['url'])
                );
            }
        }
        $menu = new dbMenu($menu);
        
        return $menu->getHTML();
    } // end getMenu
    
}

?>