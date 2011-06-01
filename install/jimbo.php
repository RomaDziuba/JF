<?php 
define('WHITOUT_COMMON', 1);
require_once dirname(__FILE__).'/config.php';

set_include_path(dirname(__FILE__).PATH_SEPARATOR.dirname(__FILE__).'/libs'.PATH_SEPARATOR.dirname(__FILE__).'/libs/PEAR'.PATH_SEPARATOR.dirname(__FILE__).'/libs/juds'.PATH_SEPARATOR.dirname(__FILE__).'/libs/uniweb'.PATH_SEPARATOR.get_include_path());

require_once FS_SITE_ROOT.'/dba/libs/jimbo/class.Controller.php';

session_start();

$GLOBALS['_sessionData'] = &$_SESSION['dba'];

$options = array(
    'http_base' => '/',
    'session_data' => &$GLOBALS['_sessionData']
);

$jimbo = Controller::getInstance($options);

$jimbo->config = $GLOBALS['CONFIG'];

$jimbo->user = new JimboUser($GLOBALS['_sessionData']);


$tpl = dbDisplayer::getTemplateInstance(FS_SITE_ROOT.'/templates/');

$tpl->assign('_user', $jimbo->user->getData());

// Database 
require_once 'MDB2.php';

$db = MDB2::factory($CONFIG['db_dsn'], array('quote_identifier' => true, 'persistent' => false));

if (PEAR::isError($db)) {
    exit(_('Database connection error.'));   
}

$db->setFetchMode(MDB2_FETCHMODE_ASSOC);
$db->setOption('portability', MDB2_PORTABILITY_NONE);
$db->loadModule('Datatype', null, 'Common');
$db->loadModule('Extended');
$db->query('SET NAMES utf8');

$jimbo->db = $db;
$jimbo->tpl = $tpl;

$sitePath = realpath(FS_SITE_ROOT.'/init.php');
if ($sitePath) {
    define('SITE_ROOT', dirname($sitePath).'/');
    require_once $sitePath;    
}

$currentUrl = $jimbo->getCurrentURL();
if ( !defined('IS_USER') && !$jimbo->user->isLogin() && $currentUrl != '/login/' ) {
    $jimbo->redirect('login/');    
}

$GLOBALS['pluginRules'] = array(
    '~^/$~'        => array('Main', 'main'),
    '~^/login/$~'  => array('User', 'login'),
    '~^/logout/$~' => array('User', 'logout'),
);

try {
    $options = array(
        'tpl' => &$jimbo->tpl,
        'path' => FS_SITE_ROOT.'/jplugins/'
    );
    
    $jimbo->bind($GLOBALS['pluginRules'], $options);

} catch(PermissionsException $premExp) {
    die('ОШИБКА: У Вас нет прав...');
} catch(DatabaseException $dbExp) {
    echo $dbExp->getMessage()."<hr />";
    die('ОШИБКА: В работе базе данных...');
}








?>