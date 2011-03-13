<?php 

require_once dirname(__FILE__).'/class.dbDisplayer.php';
require_once dirname(__FILE__).'/class.tableDefinition.php';
require_once dirname(__FILE__).'/class.dbAction.php';
require_once dirname(__FILE__).'/class.dbDisplayer.php';
require_once dirname(__FILE__).'/class.dbLogic.php';
require_once dirname(__FILE__).'/FormFields/common.php';
require_once dirname(__FILE__).'/FormFields/custom.php';

require_once dirname(__FILE__).'/class.JimboUser.php';
require_once dirname(__FILE__).'/class.Plugin.php';

define('PARAM_ARRAY', 100);
define('PARAM_STRING', 101);
define('PARAM_STRING_NULL', 104);
define('PARAM_FILE', 102); 

class Controller
{
    private $_options;
    
    private $_store = array();
    
    public $properties = array();
    
    static private $_instance = null;

    private function __construct($options = array())
    {
        $this->_options = $options;
        
        $this->_setDefaultOptions();
        
    } // end __construct
    
    private function _setDefaultOptions()
    {
        if (!isset($this->_options['base_path'])) {
            $this->_options['base_path'] = realpath(dirname(__FILE__).'/../../../').'/';
        }
        
        if (isset($this->_options['http_base'])) {
            $this->urlPrefix = $this->_options['http_base'];  
        } else if (defined('HTTP_ROOT')) {
            $this->urlPrefix = HTTP_ROOT;
        } else {
            $this->urlPrefix = '/';
        }
        
        $this->_options['http_base'] =  $this->urlPrefix;
        
        if (!isset($this->_options['session_data'])) {
            if (!session_id()) {
                session_start();
            }
            
            $this->_options['session_data'] = &$_SESSION['jimbo'];
        }
        
        if (!isset($this->_options['engine_url'])) {
            $this->_options['engine_url'] = 'jimbo';
        }
        
        if (!isset($this->_options['engine_path'])) {
            $this->_options['engine_path'] = realpath(dirname(__FILE__).'/../../').'/';
        }
        
        if (!isset($this->_options['engine_style'])) {
            $this->_options['engine_style'] = 'adminus';
        }
        
        if (!isset($this->_options['engine_http_base'])) {
            $this->_options['engine_http_base'] = $this->urlPrefix.'/jimbo/';
        }
        
        if (!isset($this->_options['defs_path'])) {
            $this->_options['defs_path'] = $this->_options['base_path'].'tblDefs/';
        }
        
        // FIXME:
        // popup | jquery
        if (!isset($this->_options['popup_mode'])) {
            $this->_options['popup_mode'] = 'jquery';
        }
        
        define('JIMBO_POPUP_MODE', $this->_options['popup_mode']);
        
    } // end _setDefaultOptions
        
    
    static public function getInstance($options = array()) 
    {
        if (self::$_instance == null) {
            self::$_instance = new self($options);
        }
        
        return self::$_instance;
    } // end getInstance
    

    public function __set($label, $object) 
    {
        if (!isset($this->_store[$label])) {
            $this->_store[$label] = $object;
        }
    }

    public function __unset($label) 
    {
        if (isset($this->_store[$label])) {
            unset($this->_store[$label]);
        }
    }

    public function __get($label) 
    {
        if (isset($this->_store[$label])) {
            return $this->_store[$label];
        }
        return false;
    }

    public function __isset($label) 
    {
        return isset($this->_store[$label]);
    }
    
    public function getCurrentURL($prefix = false) 
    {
        if(!$prefix) {
            $prefix = $this->urlPrefix;
        }
        
        $url =  empty($_SERVER['REDIRECT_URL']) ? '/' : $_SERVER['REDIRECT_URL'];
        
        return preg_replace('#^'.$prefix.'#Umis', '/', $url);
    } // end getCurrentURL
    
    public static function getPluginInstance($plugin, $params = array(), $options = array())
    {
        $classPrefix = !isset($options['classPrefix']) ?  'Plugin' : $classPrefix;
        
        $className = $plugin.$classPrefix;
        
        if ( isset($options['path']) ) {
            $path = $options['path'];
        } elseif ( defined('JIMBO_PLUGINS_PATH') ) {
            $path = JIMBO_PLUGINS_PATH;
        } else {
            $path = realpath(dirname(__FILE__).'/../../../').'/plugins/';
        }
        
        $classFile = $path.$className.'.php';
                          
        if (!file_exists($classFile)) {
            throw new SystemException(sprintf(_('File "%s" for plugin "%s" was not found.'), $classFile, $plugin));
        }
            
        require_once $classFile;
        if (!class_exists($className)) {
            throw new SystemException(sprintf(_('Class "%s" was not found in file "%s".'), $className, $classFile));
        }

        $tplPath = isset($options['tpl_path']) ? $options['tpl_path'] : false;
        
        $tpl = !isset($options['tpl']) ? dbDisplayer::getTemplateInstance($tplPath) : $options['tpl'];
        
        $obj = new $className($tpl);
        
        return $obj;
    } // end getPluginInstance
    
    public static function call($plugin, $method, $params = array(), $options = array())
    {
        if ( !isset($options['path']) ) {
            $options['path'] = realpath(dirname(__FILE__).'/../../../').'/plugins/';
        }
        
        if ( !isset($options['tpl_path']) ) {
            //$options['tpl_path'] = $options['path'].'templates/';
        }
        
        $obj = self::getPluginInstance($plugin, $params, $options);
        
        if (!is_callable(array($obj, $method))) {
            throw new SystemException(sprintf(_('Method "%s" was not found in module "%s".'), $method, $plugin));
        }
            
        return call_user_func_array(array($obj, $method), $params);
    } // end call
    
    public function bind($urlRules = array(), $callOptions = array())
    {
        $currentUrl = $this->getCurrentURL();

        $systemRules = array(
            '~^/jimbo/$~'                           => array('Jimbo', 'main'),
            '~^/getfile/([^/]+)/([^/]+)/([^/]+)/$~' => array('Jimbo', 'getFile'),
            '~^/jimbo/([^/]+)/$~'                   => array('Jimbo', 'main'),
        );

        $rules = $urlRules + $systemRules;
        
        foreach($rules as $regExp => $call) {
            if (preg_match($regExp, $currentUrl, $regs)) {
                array_shift($regs);
                return $this->call($call[0], $call[1], $regs, $callOptions);
            }
        }
        
        return false;
    } // end bind
    
    public function getView($db, $table)
    {
        define('DBADMIN_CURRENT_TABLE', $table);
        
        $this->_options['session_data']['DB_CURRENT_TABLE'] = $table;
        $this->_options['session_data']['DBA_SCRIPT'] = $this->urlPrefix.$this->_options['engine_url'].'/';
        
        $tblAction = new dbAction($db, $table, $this->_options);
        
        
        $tpl = dbDisplayer::getTemplateInstance();
        
        // TODO:
        $tpl->template_dir = $this->_options['engine_path'].'templates/dba/'.$this->_options['engine_style'].'/';
        
        $displayer = new dbDisplayer($tblAction, $tpl);
        
        $dbLogic = new dbLogic();
        
        $doAction = $dbLogic->detectPerformAction($tblAction);
        $status = $tblAction->performAction($doAction);
        
        $viewAction = $dbLogic->detectViewAction($tblAction, $status);
        
        $this->includeJs($this->_options['engine_http_base'].'js/jimbo.js');
        
        $content = $displayer->performDisplay($viewAction);
        
        return $content;
    } // end getView
    
    
    public function redirect($url, $usePrefix = true)
    {
        $url = $usePrefix ? $this->urlPrefix.$url : $url;
        header('Location: '.$url);
        exit();
    } // end redirect
    
    public function json($data = array(), $type = false)
    {
        $json = json_encode($data);
        
        switch($type) {
            case 'iframe':
                header('Content-Type: text/html; charset='.CHARSET);
                echo "<script>parent.setIframeResponse('".mysql_escape_string($json)."');</script>";
                break;
            default:
                header('Content-type: application/json'); 
                echo $json;       
        }
        
        exit();        
    } // end json
    
    public function display($content, $template = 'main.ihtml', $vars = false, $tplPath = false) 
    {
        echo $this->fetch($content, $template, $vars, $tplPath);
        exit();
    } // end display
    
    public function fetch($content, $template = 'main.ihtml', $vars = false, $tplPath = false)
    {
        $tpl = dbDisplayer::getTemplateInstance($tplPath);
        
        
        $tpl->assign('content', $content);
        
        $info = array(
            'basehttp' => $this->urlPrefix,
            'charset' => CHARSET,
            //'style_header' => ENGINE_STYLE.'/header.ihtml'
        );
        
        $info += $this->properties;
        
//        $tpl->assign('menu', $this->call('Jimbo', 'getMenu'));
        
        $tpl->assign('info', $info);
        if($vars) {
            $tpl->assign($vars);
        }
        
        return $tpl->fetch($template);
    } // end fetch
    
    /**
     * Add template property title
     * 
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->addProperties('title', $title, true);
    } // end setTitle
    
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
    
    
    public function getUrl($url)
    {
        if(preg_match('#^(www|http)#Umis', $url)) {
            return $url;
        }
        
        $url = preg_replace('#^/#Umis', $this->urlPrefix, $url);
        
        return $url;
    }
    
    public function addParam($key, $value)
    {
        $_SESSION[$key] = $value;
    }
    
    public function getParam($key)
    {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : false;
    }
    
public function popParam($key)
    {
        $value = $this->getParam($key);
        $this->removeParam($key);
        
        return $value;
    }
    
    public function removeParam($key)
    {
        unset($_SESSION[$key]);
    }
    
    
    // TODO: rename it
    public function getParams($descriptor, $data = array())
    {
        $r = array();
        foreach ($descriptor as $name => $type) {
            switch($type) {
                case PARAM_ARRAY:
                    $r[$name] = isset($data[$name]) && is_array($data[$name]) ? $data[$name] : array();
                    break;
                    
                case PARAM_FILE:
                    $r[$name] = isset($_FILES[$name]) ? $_FILES[$name]['error'] : UPLOAD_ERR_NO_FILE;
                    break;
                    
                case PARAM_STRING_NULL:
                    $r[$name] = !empty($data[$name]) && is_scalar($data[$name]) ? $data[$name] : null;
                    break;
                    
                default:
                    $r[$name] = isset($data[$name]) && is_scalar($data[$name]) ? $data[$name] : null;
            }
            
        }
        
        return $r;
    } // end getParams
    
    public static function setImageResize($outfile, $infile, $neww, $newh = null, $quality = 100) 
    {
        $image_info = getimagesize($infile);
        
        if (!$image_info) {
            return false;
        } 
        
        $type = $image_info[2];
        
        switch($type) {
            case IMAGETYPE_GIF:
                $im = imagecreatefromgif($infile);
                break;
            case IMAGETYPE_JPEG:
                $im = imagecreatefromjpeg($infile);
                break;
            case IMAGETYPE_PNG:
                $im = imagecreatefrompng($infile);
                break;
        }
        
        if (!$im) {
            return false;
        }
        
        $w_src = imagesx($im); 
        $h_src = imagesy($im);
        
        if (!$w_src || !$h_src) {
            return false;
        }
        
        if (is_null($newh)) {
            // вычисление пропорций 
            $ratio = $w_src / $neww; 
            $ratio = $ratio == 0 ? 1 : $ratio;
            
            $w_dest = round($w_src/$ratio); 
            $h_dest = round($h_src/$ratio);
        } else if (is_null($neww)) {
            $ratio = $h_src / $newh; 
            $ratio = $ratio == 0 ? 1 : $ratio;
            
            $w_dest = round($w_src/$ratio); 
            $h_dest = round($h_src/$ratio);
        } else {
            $w_dest = $neww;
            $h_dest = $newh;
        }
        
        $imResult = imagecreatetruecolor($w_dest, $h_dest);

        if (!$imResult) {
            return false;
        }
        
        if (!imagecopyresampled($imResult, $im, 0, 0, 0, 0, $w_dest, $h_dest, $w_src, $h_src)) {
            return false;
        }       
        
        if (!imagejpeg($imResult, $outfile, $quality)) {
            return false;
        }
        
        imagedestroy($im);
        imagedestroy($imResult);
        
        chmod($outfile, 0777);
        chmod($outfile, 0777);
        
        return true;
    }// end setImageResize
    
    
    /**
     * Returns a html list of pages
     * 
     * @param integer $totalCnt
     * @param integer $perPage
     * @param string $path
     * @return HTML
     */
    public function getPager($totalCnt, $perPage, $path)
    {
        require_once 'Pager/Pager.php';
        $params = array(
            'totalItems' => $totalCnt,
            'mode'       => 'Sliding',
            'perPage'    => $perPage,
            'delta'      => 3,
            'linkClass' => 'page',
            'spacesBeforeSeparator' => 1,
            'spacesAfterSeparator' => 1,
            'append' => true, 
            'path' => $path,
            'fileName' => '',
            'fixFileName' => false
        ); 
            
        $pager = Pager::factory($params);
        $links = $pager->getLinks();
        
        return $links['all'];
    } // end getPager
    
    /**
     * Returns current sort order based on session and current user preference
     * 
     * @param string $default
     * @param string $postfix
     * @return string
     */
    public function getSort($default, $postfix = '')
    {
        $key = 'j_orderby'.$postfix;
        $orderby = $this->getParam($key);
        
        if ( !$orderby ) {
            $orderby = $default;
        }
        
        $orderby = isset($_REQUEST['orderby']) ? $_REQUEST['orderby'] : $orderby;
        
        $this->addParam($key, $orderby);
        $this->addParam('jimbo_current_sort', $orderby);
        
        if (substr($orderby, -5) == '_desc') {
            $orderby = substr($orderby, 0, -5).' DESC';
        }
        
        return array($orderby);
    } // end getSort
    
    public function strtotime($date, $format)
    {
        $ftime = strptime($date, $format);
        
        $time = mktime(
            $ftime['tm_hour'], 
            $ftime['tm_min'], 
            $ftime['tm_sec'], 
            $ftime['tm_mon'] + 1, 
            $ftime['tm_mday'], 
            $ftime['tm_year'] + 1900
        );
        return $time; 
    }
    
}

//FIXME:


class SystemException extends Exception { }
class PermissionsException extends SystemException { }
class DatabaseException extends SystemException { }

?>
