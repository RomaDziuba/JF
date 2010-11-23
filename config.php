<?php

$GLOBALS['config'] = array();

//////////////////////////////
//  User setting
//////////////////////////////

define('DSN', 'mysql://www:wwwrun@localhost/jimbo_wizard');
define('CHARSET', 'utf-8');

define('DEFAULT_TABLE', 'dbdrive_tables');

//////////////////////////////
//  System setting
//////////////////////////////

define('SITE_CHARSET', CHARSET);

define('IS_JIMBO', 1);
define('JIMBO_VERSION', '3.1');

// popup | jquery
define('JIMBO_POPUP_MODE', 'jquery');

if(!defined('LANG')) {
    define('LANG', 'ru');
}

define('FS_ROOT', dirname(__FILE__)."/");

define('TPL_ROOT', FS_ROOT.'/templates/');
define('BASE_HTTP_PATH', "/");
define('BIN_PHP', '/usr/local/bin/php');
define('BIN_SH', '/bin/sh');
define('IMAGEMAGIC_BIN', '/usr/local/bin/convert');

define("FS_TEMPLATES", FS_ROOT."templates/");
define("FS_LIBS", FS_ROOT."libs/");
define('HTTP_ROOT', "/jimbo/");
define('ENGINE_URL', 'jimbo');

// adminus | indigo
define('ENGINE_STYLE', 'indigo');

define('STYLES_HTTP_ROOT', HTTP_ROOT.'styles/');

define('AUTH_DATA', 'dbadmin');
define('AUTH_TOKEN', 'zero');

$GLOBALS['config']['paths'] = array(
    'plugins' => FS_ROOT.'plugins/' 
);

$GLOBALS['pluginRules'] = array();

if( file_exists(FS_ROOT.'config.site.php') ) {
    require_once FS_ROOT.'config.site.php';
}

include_once FS_ROOT."common.php";

?>
