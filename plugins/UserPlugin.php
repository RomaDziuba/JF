<?php 
class UserPlugin extends Plugin
{
    public function login()
    {
        global $jimbo;
        
        if(!empty($_POST) && $this->auth()) {
            $jimbo->redirect();
        }
        
        $jimbo->includeCss('css/dbalogin.css');
        
        $content = $this->fetch('dba/'.ENGINE_STYLE.'/dba_login.ihtml');
        
        $jimbo->display($content, 'main.ihtml', false, TPL_ROOT.'dba/');
    }
    
    private function auth()
    {
        global $jimbo;
        
        ////////////////////////////
        // Auth
        ////////////////////////////
        
        // Sample:
        $params = array(
            'auth' => 'yes', 
            'auth_id' => 1, 
            'auth_login' => 'test', 
            'auth_role' => 8
        );
        
        $jimbo->user->doLogin($params);
        
        return true;
    }
    
}
?>