<?php
class Response
{
    const ACTION_REDIRECT = "redirect";

    const NORMAL      = 'normal';
    const JSON        = 'json';
    const JSON_JS     = 'json_js';
    const JSON_IFRAME = 'json_iframe';
    const JSON_P      = 'jsonp';

    protected $type;

    public $response;

    protected $notifications = array();


    public function __construct($type = self::NORMAL, $actionType = false)
    {
        $this->type = $type;
        $this->response = array(
			'type' => $actionType,
			'messages' => array(),
        );

    } // end __construct

    public function setType($type)
    {
        $this->type = $type;
    }

    public function setAction($type)
    {
        $this->response['type'] = $type;
    }

    public function addParam($name, $value)
    {
        $this->response[$name] = $value;
    }

    public function addMessage($message)
    {
        $this->response['messages'][] = $message;
    }

    public function addNotification($message)
    {
        $this->notifications[] = $message;
    } // end addNotification

    public function getMessageCount()
    {
        return count($this->response['messages']);
    }


    public function getType()
    {
        return $this->type;
    }

    public function setAfter($type, $command)
    {
        global $jimbo;

        $jimbo->addParam('after_type', $type);
        $jimbo->addParam('after_command', $command);
    }

    public static function callAfter()
    {
        global $jimbo;

        $type = $jimbo->popParam('after_type');
        $command = $jimbo->popParam('after_command');

        if ($type == 'url') {
            $jimbo->redirect($command, false);
        }
    }

    public function send($plugin = null)
    {
        global $jimbo;

        if ($this->type != self::NORMAL) {
            if ($this->notifications) {
                $this->response['notifications'] = $this->notifications;
            }
        }

        if ($this->type == self::JSON) {
            $jimbo->json($this->response);
        } else if ($this->type == self::NORMAL) {
            $this->_processing($plugin);
        } else if ($this->type == self::JSON_IFRAME) {
            echo "<script>parent.Jimbo.responseIframe('".json_encode($this->response)."');</script>";
        } else if ($this->type == self::JSON_JS) {
            echo "<script>Jimbo.responseIframe('".json_encode($this->response)."');</script>";
        } else if ($this->type == self::JSON_P) {
            $callbakFunctionName = isset($_REQUEST['callback']) ? $_REQUEST['callback'] : 'Jimbo.responseIframe';
            header('Content-Type: text/javascript');
            echo $callbakFunctionName . '('.json_encode($this->response).')';
        }

        exit();
    } // end send

    private function _processing($plugin)
    {
        global $jimbo;

        if ($this->response['type'] == 'redirect') {

            if ($this->notifications) {
                $jimbo->addParam("notifications", $this->notifications);
            }

            $jimbo->redirect($this->response['url'], false);
            return true;
        }

        if (isset($this->response['content'])) {
            if ($plugin) {
                $plugin->display($this->response['content']);
            } else {
                echo $this->response['content'];
            }
        }

    }


}
?>