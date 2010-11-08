<?php 
class TblDefinitionPlugin extends Plugin
{
	public function definition(&$tpl)
	{
        $GLOBALS['db']->query('set names utf8');
        $tpl->assign('_dictionary', $GLOBALS['db']->getAssoc("SELECT ident, value_".LANG." FROM dictionary"));
        $GLOBALS['db']->query('set names cp1251');
        
        $info = array(
        	'role' => $GLOBALS['_sessionData']['auth_role']
        );
        
        $tpl->assign('info', $info);
	}
}
?>