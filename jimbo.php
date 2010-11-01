<?php 
require_once dirname(__FILE__).'/config.php';

$currentUrl = $jimbo->getCurrentURL();

if(!$jimbo->user->isLogin() && $currentUrl != '/login/') {
    $jimbo->redirect('login/');    
}

$systemRules = array(

    '~^/jimbo/$~'                           => array('Jimbo', 'main'),
    '~^/getfile/([^/]+)/([^/]+)/([^/]+)/$~' => array('Jimbo', 'getFile'),
    '~^/jimbo/([^/]+)/$~'                   => array('Jimbo', 'main'),

    '~^/login/$~'  => array('User', 'login'),
    '~^/logout/$~' => array('User', 'logout'),
);

$rules = $GLOBALS['pluginRules'] + $systemRules;

try {
    foreach($rules as $regExp => $call) {
        if (preg_match($regExp, $currentUrl, $regs)) {
            array_shift($regs);
            $jimbo->call($call[0], $call[1], $regs);
            break;
        }
    }
} catch(PermissionsException $premExp) {
    die('ОШИБКА: У Вас нет прав...');
} catch(DatabaseException $dbExp) {
    die('ОШИБКА: В работе базе данных...');
}

?>