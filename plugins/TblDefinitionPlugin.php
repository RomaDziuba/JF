<?php 
class TblDefinitionPlugin extends Plugin
{
	public function definition(&$tpl)
	{        
        $info = array(
        	'role' => $GLOBALS['_sessionData']['auth_role'],
        	'position' => $GLOBALS['_sessionData']['position_id']
        );
        
        $tpl->assign('info', $info);
	}
}
?>

