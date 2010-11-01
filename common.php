<?php
error_reporting(E_ALL & ~E_DEPRECATED);
 
ini_set('include_path', ini_get('include_path').':'.FS_ROOT.':.:'.FS_ROOT.'libs:');

require_once "templates/class.template.php";

//////////////////////////////
//  Jimbo
//////////////////////////////

require_once 'jimbo/class.Jimbo.php';

if(!defined('CHARSET')) {
    throw new Exception(_('Undefined CHARSET const'));
}

//////////////////////////////
//  Database connection
//////////////////////////////

require_once "MDB2.php";

if(!defined('DSN')) {
    throw new Exception(_('Undefined DSN const'));
}

$db = MDB2::factory(DSN);
$db->loadModule('Extended');
$db->setOption('portability', MDB2_PORTABILITY_ALL ^ (MDB2_PORTABILITY_FIX_CASE | MDB2_PORTABILITY_EMPTY_TO_NULL));
$db->setFetchMode(MDB2_FETCHMODE_ASSOC);
$res = $db->query('SET NAMES utf8');

if(PEAR::isError($res)) {
    throw new Exception(_('Database connection error'));
}

session_start();

$GLOBALS['_sessionData'] = &$_SESSION[AUTH_DATA][AUTH_TOKEN];

$jimbo = Jimbo::getInstance($db, $GLOBALS['config']);

$jimbo->user = new JimboUser($GLOBALS['_sessionData']);

?>