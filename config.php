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

define('JIMBO_VERSION', '3.1');

define('FS_ROOT', dirname(__FILE__)."/");

define('TPL_ROOT', FS_ROOT.'/templates/');
define('BASE_HTTP_PATH', "/");
define('BIN_PHP', '/usr/local/bin/php');
define('BIN_SH', '/bin/sh');
define('IMAGEMAGIC_BIN', '/usr/local/bin/convert');

define("FS_TEMPLATES", FS_ROOT."templates/");
define("FS_LIBS", FS_ROOT."libs/");
define('HTTP_ROOT', "/jimbo/");
define('LANG', 'ru');
define('ENGINE_URL', 'jimbo');

define('AUTH_DATA', 'dbadmin');
define('AUTH_TOKEN', 'zero');

$GLOBALS['config']['paths'] = array(
    'plugins' => FS_ROOT.'plugins/' 
);

include_once FS_ROOT."common.php";




/*

$ddcPerms = new simplePermissions();
$_sessionData['DB_alowed_groups'] = join(", ", (array)$ddcPerms->getOpStatus('DDC_DINFO_READ'));

if (defined('DDC_VENDOR_ID')) {
	$_sessionData['DDC_VENDOR_ID'] = DDC_VENDOR_ID;
} else {
	$_sessionData['DDC_VENDOR_ID'] = -1;
}

*/


?>