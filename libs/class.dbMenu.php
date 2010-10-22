<?php 
class dbMenu {
	
	protected $tpl;
	protected $items;
	protected $name;
	
	function __construct($items, $name = 'rootMenu') {
		$this->items = $items;
		$this->tpl = dbDisplayer::getTemplateInstance();
		$this->name = $name;
	}
	
	public function getHTML() {
		global $db, $_sessionData;
		$tpl = $this->tpl;
		
		$info = array(
		'logo' => LOGO_IMG
		);		
		$supportContacts = $db->getOne('SELECT value FROM config WHERE ident = "SUPPORT_CONTACTS"');		
		
		$currentItem = $_SERVER['REQUEST_URI'];
		$currentItem2 = substr($currentItem, -1, 1) != '/' ? $currentItem.'/' : substr($currentItem, 0, -1);
		$tpl->assign("currentItem", $currentItem);
		$tpl->assign("currentItem2", $currentItem2);
		
		$tpl->assign("name", $this->name);
		$tpl->assign("info", $info);		
		$tpl->assign("items", array_values($this->items));
		$tpl->assign("supportContacts", trim($supportContacts));
		$tpl->assign("userId", $_sessionData['auth_id']);
		return $tpl->fetch("menu.ihtml");
	}
	
}

?>