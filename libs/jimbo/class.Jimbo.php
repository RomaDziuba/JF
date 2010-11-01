<?php 

require_once 'jimbo/class.dbDisplayer.php';
require_once 'jimbo/class.tableDefinition.php';
require_once 'jimbo/class.dbAction.php';
require_once 'jimbo/class.dbDisplayer.php';
require_once 'jimbo/class.dbMenu.php';
require_once 'jimbo/class.dbLogic.php';
require_once 'jimbo/FormFields/common.php';
require_once 'jimbo/FormFields/custom.php';

require_once 'jimbo/class.JimboUser.php';
require_once 'jimbo/class.Plugin.php';

class Jimbo
{
    private $store = array();
    
    public $config = array();

    public $tpl = null;
    
    public $properties = array();
    
    static private $instance = null;

    private function __construct(&$db, $config = array())
    {
        $this->db = $db;
        $this->config = $config;
        
        $this->urlPrefix = defined('HTTP_ROOT') ? HTTP_ROOT : '/';
        
        if(!defined('TPL_ROOT')) {
            throw new SystemException(_('Undefined TPL_ROOT const'));
        }
        
        $this->tpl = dbDisplayer::getTemplateInstance(TPL_ROOT);
    } // end __construct
    
    static public function getInstance(&$db, $config = array()) 
    {
        if (self::$instance == null) {
            self::$instance = new self($db, $config);
        }
        
        return self::$instance;
    }
    

    public function __set($label, $object) 
    {
        if (!isset($this->store[$label])) {
            $this->store[$label] = $object;
        }
    }

    public function __unset($label) 
    {
        if (isset($this->store[$label])) {
            unset($this->store[$label]);
        }
    }

    public function __get($label) 
    {
        if (isset($this->store[$label])) {
            return $this->store[$label];
        }
        return false;
    }

    public function __isset($label) 
    {
        return isset($this->store[$label]);
    }
    
    public function getCurrentURL($prefix = false) 
    {
        if(!$prefix) {
            $prefix = $this->urlPrefix;
        }
        
        $url =  empty($_SERVER['REDIRECT_URL']) ? '/' : $_SERVER['REDIRECT_URL'];
        return preg_replace('#^'.$prefix.'#Umis', '/', $url);
    } // end getCurrentURL
    
    public function call($plugin, $method, $params = array(), $classPrefix = 'Plugin')
    {
        $className = $plugin.$classPrefix;
        
        $classFile = $this->config['paths']['plugins'].$className.'.php';
                          
        if (!file_exists($classFile)) {
            throw new SystemException(sprintf(_('File "%s" for plugin "%s" was not found.'), $classFile, $plugin));
        }
            
        require_once $classFile;
        if (!class_exists($className)) {
            throw new SystemException(sprintf(_('Class "%s" was not found in file "%s".'), $className, $classFile));
        }
            
        $obj = new $className($this->tpl);
        if (!is_callable(array($obj, $method))) {
            throw new SystemException(sprintf(_('Method "%s" was not found in module "%s".'), $method, $plugin));
        }
            
        return call_user_func_array(array($obj, $method), $params);
    } // end call
    
    public function redirect($url)
    {
        header('Location: '.$this->urlPrefix.$url);
        exit();
    } // end redirect
    
    public function display($content, $template = 'main.ihtml', $vars = false, $tplPath = false) 
    {
        echo $this->fetch($content, $template, $vars, $tplPath);
        exit();
    } // end display
    
    public function fetch($content, $template = 'main.ihtml', $vars = false, $tplPath = false)
    {
         if(!$tplPath && !defined('TPL_ROOT')) {
            throw new SystemException(_('Undefined TPL_ROOT const'));
        }
        
        $tpl = $tplPath ? dbDisplayer::getTemplateInstance($tplPath) : $this->tpl;
        
        $tpl->assign('content', $content);
        
        $info = array(
            'basehttp' => $this->urlPrefix,
            'charset' => CHARSET,
        );
        
        $info += $this->properties;
        
        $tpl->assign('menu', $this->getMenu());
        
        $tpl->assign('info', $info);
        return $tpl->fetch($template);
    } // end fetch
    
    public function includeJs($path)
    {
        $this->addProperties('js', $path);
    }
    
    public function includeCss($path)
    {
        $this->addProperties('css', $path);
    }
    
    public function addProperties($name, $value, $is_scalar = false)
    {
        if($is_scalar) {
            $this->properties[$name] = $value;
            return true;
        }
        
        if(!isset($cms->properties[$name])) {
            $this->properties[$name] = array();
        }
        
        $this->properties[$name][] = $value;
        
        return true;
    } // end addProperties
    
    public function getMenu()
    {
        if(!$this->user->isLogin()) {
            return false;
        }
        
        $id_group = $this->user->getGroup();
        
        $sql = "SELECT 
                    m.* 
                FROM 
                    dbdrive_menu_perms p
                    INNER JOIN dbdrive_menu m ON (p.id_menu = m.id)
                WHERE
                    p.id_role = ".$this->db->quote($id_group)."
                ORDER BY 
                    m.id_parent, m.order_n";
        $tmp = $this->db->getAll($sql);
        
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
                'href' => $this->getUrl($item['url']),
                'level' => 1,
                'items' => array()
                );
            } elseif(isset($menu[$item['id_parent']]['level']) && $menu[$item['id_parent']]['level'] == 1) {
            
                $menu[$item['id_parent']]['items'][$item['id']] = array(
                'caption' => $item['caption'],
                'href' => $this->getUrl($item['url']),
                'level' => 2,
                'id_parent' => $item['id_parent'],
                'items' => array()
                );
            } else {
    
                $parent = $item['id_parent'];
                $top = $parents[$parent];
                $menu[$top]['items'][$parent]['items'][] = array(
                'caption' => $item['caption'],
                'href' => $this->getUrl($item['url'])
                );
            }
        }
        $menu = new dbMenu($menu);
        
        return $menu->getHTML();
    } // end getMenu
    
    private function getUrl($url)
    {
        if(preg_match('#^(www|http)#Umis', $url)) {
            return $url;
        }
        
        $url = preg_replace('#^/#Umis', $this->urlPrefix, $url);
        
        return $url;
    }
    
    
}

//FIXME:


class SystemException extends Exception { }
class PermissionsException extends SystemException { }
class DatabaseException extends SystemException { }

?>