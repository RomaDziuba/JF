<?php
class Logger
{
    public static $path = null;
    
    public static function info($message)
    {
        self::addMessage($message, "info");
    }
    
    public static function debug($message)
    { 
        self::addMessage($message, "debug");
    }
    
    public static function addMessage($msg, $type = 'debug', $file = false)
    {
        if (is_null(self::$path)) {
            return false;
        }
        
        $file = $file ? $file : "logger"; 
        
        $msg = date('Y-m-d H:i:s')."\t[".$type."]\t".$msg."\n";
        error_log($msg, 3, self::$path.'/'.$file.".log");
    }
    
    public static function access()
    {
        self::addMessage(join("\t", func_get_args()), __FUNCTION__);
    }
    
    public static function error($msg, $code = 0)
    {
        $debug = debug_backtrace();
        
        $msg .= "\t{".$code."}";
        
        if (!empty($debug[1])) {
            if (!empty($debug[1]['file'])) {
                $msg .= "\n".$debug[1]['file'].":".$debug[1]['line']." ".$debug[1]['class']."::".$debug[1]['function']."()";
            } else {
                $msg .= "\t".$debug[1]['class']."::".$debug[1]['function']."()";
            }
        }

        self::addMessage($msg, __FUNCTION__, "errors");
    }
}

?>