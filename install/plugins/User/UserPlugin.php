<?php 
class UserPlugin extends ObjectJimboPlugin
{
    private $_userObject;
    
    private $_signinFields = array(
        'login'    => PARAM_STRING,
        'password' => PARAM_STRING,
    );
    
    public function onInit()
    {
        $this->_userObject = $this->getObject('User');
    }
    
    public function login()
    {
        global $jimbo;
        
        if (!empty($_POST) && $this->auth()) {
            
            $url = $jimbo->popParam('back_to');
            if ($url) {
                $jimbo->redirect($url, false);
            }
            
            $jimbo->redirect();
        }
        
        $this->error_message = $jimbo->popParam('error');
        
        $jimbo->includeCss('css/dbalogin.css');
        
        $content = $jimbo->systemFetch('dba_login.ihtml');
        
        $jimbo->display($content);
    } // end login
    
    public function logout()
    {
        global $jimbo;
        
        unset($_SESSION[AUTH_DATA]);
        $jimbo->redirect();
    }
    
    
    private function auth()
    {
        global $jimbo;
        
        $fields = $jimbo->getParams($this->_signinFields, $_REQUEST);
        
        return $this->signin($fields['login'], $fields['password']);
    } // end auth
    
    public function signin($login, $passaword)
    {
        global $jimbo;
        
        if (empty($login) || empty($passaword)) {
            $jimbo->addParam('errors', array(_('Username or password can not be empty') ));
            return false;
        }
        
        $search = array(
            'login' => $login,
            'pass'  => md5($passaword)
        );
        
        $info = $this->_userObject->get($search);
        
        if (!$info) {
            $jimbo->addParam('errors', array(_('Wrong username or password') ));
            return false;
        }
        
        $params = array(
            'auth'       => 'yes', 
            'auth_id'    => $info['id'], 
            'auth_login' => $info['login'], 
            'auth_role'  => $info['id_type'],
            'auth_data'  => $info
        );
        
        $jimbo->user->doLogin($params);
        
        return $info['id'];
    } // end signin
    
    
    public function getSignupDataErrors($data)
    {
        global $jimbo;
        
        $errors = array();
        
        // Login check
        if ( empty($data['login']) ) {
            $errors[] = _('Wrong Login');
        } else {
            $info = $this->_userObject->get(array('login' => $data['login']));
            if ($info) {
                $errors[] = _('Login already exist');
            }
        }
        
        // Check E-mail
        if ( empty($data['email']) || !$jimbo->user->isEmail($data['email']) ) {

            $errors[] = _('Wrong E-mail');
            
        } else {
            $info = $this->_userObject->get(array('email' => $data['email']));
            if ($info) {
                $errors[] = _('E-mail already exist');
            }
        }
        
        //Check Password
        if ( empty($data['password']) ) {
             $errors[] = _('Wrong password');
        } 
        
        if ( empty($data['password2']) ) {
             $errors[] = _('Wrong confirm password');
        } 
        
        if ( $data['password'] != $data['password2'] ) {
             $errors[] = _('Password and Confirm Password do not match');
        } 
        
        return $errors;
    } // end getSignupDataErrors
    
    
    public function add($data)
    {
        global $jimbo;
        
        $errors = $this->getSignupDataErrors($data);
        
        if ($errors) {
            $jimbo->addParam('errors', $errors);
            return false;
        }
        
        $defaultUserType = 'user';
        if ( !empty($jimbo->settings['default_user_type']) ) {
            $defaultUserType = $jimbo->settings['default_user_type'];
        }
        
        $idUserType      = $jimbo->user_types[$defaultUserType];
        $is_activation   = !empty($jimbo->settings['is_activation']);
        $activationCode  = md5($data['login'].$data['email'].mktime());
        
        $values = array(
            'login'             => $data['login'],
            'email'             => $data['email'],
            'pass'              => md5($data['password']),
            'id_type'            => $idUserType,
            'status'            => $is_activation ? 'new' : 'active',
            'activation_code'   => $activationCode,
            'registration_date' => 'NOW()'
        );
        
        $jimbo->db->beginTransaction();
        try {
            $id_user = $this->_userObject->add($values);
            
            $mailData = array(
                'user' => array(
                    'id'    => $id_user,
                    'login' => $data['login'],
                    'email' => $data['email'],
                    'pass'  => $data['password'],
                    'activation_code' => $activationCode
                )
            );
            
            $mailType = $is_activation ? 'email_activation' : 'email_registration_success';
            
            $isSuccess = $jimbo->call('Mail', 'send', array($data['email'], $mailType, $mailData));
            
        } catch (Exception $exp) {
            $isSuccess = false;
        }
        
        if ( !$isSuccess ) {
            $jimbo->db->rollback();
            $jimbo->addParam('errors', array(_('An error occurred when registering a new user')));
            return false;
        }
        
        $jimbo->db->commit();
        
        return $id_user;
    } // end add
    
    /**
     * Registration page
     */
    public function registration()
    {
        global $jimbo;
        
        $jimbo->setTitle(_('Create an Account'));
        
        if ( !empty($_POST) ) {
            
            $response = array(
                'type' => 'alert',
                'title' => _('Notification'),
                'messages' => array()
            );
            
            $idUser = $this->add($_REQUEST);
            
            if ( $idUser ) {
                if ( !empty($jimbo->settings['is_activation']) ) {
                    $message = _('Thanks for registering with '.$jimbo->settings['site_caption'].'. 
                    				Please activate your account from e-mail');
                } else {
                    $message = _('Thanks for registering with '.$jimbo->settings['site_caption'].'.');
                }
                
                $response['url'] = HTTP_ROOT;
                $response['messages'][] = $message;
            } else {
                $response['messages'] = $jimbo->popParam('errors');
            }
            
            $jimbo->json($response, 'iframe');
        }
        
        $this->postData = $_REQUEST;
        
        $content = $this->fetch('users/registration.ihtml');

        $jimbo->display($content);
    } // end registration
    
    /**
     * Activate new user by user id and activation code
     */
    public function activation()
    {
        global $jimbo;
        
        $uid = !empty($_GET['uid']) ? (int) $_GET['uid'] : null;
        $code = !empty($_GET['a_code']) ? $_GET['a_code'] : null;
        if ((!empty($code) && !empty($uid))) {
            $serach = array('activation_code' => $code, 'id' => $uid, 'status' => 'new');
            $info = $this->_userObject->get($serach);
            if ($info) {
                $this->_userObject->change(array('status' => 'active'), $serach);
                $user = $this->_userObject->get(array('id' => $uid));
                $jimbo->addParam('message', _('Activation success'));

                $emailData = array('user' => $user);
                $jimbo->call('Mail', 'send', array($user['email'], 'email_activation_success', $emailData));
                
            } else {
                $jimbo->addParam('error', _('Activation fail'));
            }
        }

        $jimbo->redirect();
    } // end activation
 
    /**
     * Forgot password page
     */
    public function forgotPassword()
    {
        global $jimbo;
        
        if (!empty($_POST) && $this->sendForgotEmail()) {
            $jimbo->redirect('user/forgot/');
        }
        
        $this->errors = $jimbo->popParam('errors');
        $this->postData = $_POST;
        
        $content = $this->fetch('users/forgot.ihtml');

        $jimbo->display($content);
    } // end forgotPassword
    
    /**
     * Send email with change password request
     */
    private function sendForgotEmail() 
    {
        global $jimbo;
        
        $info = $this->_userObject->get(array('email' => $_POST['email']));
        if (empty($info)) {
            $jimbo->addParam('errors', _('E-mail not found'));
            return false;
        }
        if (!$info['activation_code']) {
             $activationCode = md5($info['login'].$info['email'].mktime());
             $search = array('id' => $info['id']);
             $this->_userObject->change(array('activation_code' => $activationCode), $search);
             $info['activation_code'] = $activationCode;
        }
        
        $emailData = array('user' => $info);
        $jimbo->call('Mail', 'send', array($info['email'], 'email_password_forgot', $emailData));
        $jimbo->addParam('message', _('Check instructions on your E-mail'));
        $jimbo->redirect();
    } // end sendForgotEmail
    
    /**
     * Generate and change user password
     */
    public function changePassword()
    {
        global $jimbo;
        $uid = !empty($_GET['uid']) ? (int) $_GET['uid'] : null;
        $code = !empty($_GET['a_code']) ? $_GET['a_code'] : null;
        if ((!empty($code) && !empty($uid))) {
            $serach = array('activation_code' => $code, 'id' => $uid);
            $info = $this->_userObject->get($serach);
            if ($info) {
                
                $newPassword = substr(md5(rand().rand()), 0, 6);

                $this->_userObject->change(array('pass' => md5($newPassword)), $serach);
                $info['pass'] = $newPassword;
                $emailData = array('user' => $info);
                $jimbo->call('Mail', 'send', array($info['email'], 'email_password_new', $emailData));
                $jimbo->addParam('message', _('Check new password on your E-mail'));
                
            } else {
                $jimbo->addParam('error', _('Changing password fail'));
            }
        }
        $jimbo->redirect();
    } // end changePassword

}
?>