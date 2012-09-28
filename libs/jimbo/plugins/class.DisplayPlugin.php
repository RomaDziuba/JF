<?php
class DisplayPlugin extends ObjectJimboPlugin
{
    private $vars = array();

    public function __set($label, $object)
    {
        $this->vars[$label] = $object;
    }

    public function __unset($label)
    {
        if (isset($this->vars[$label])) {
            unset($this->vars[$label]);
        }
    }

    public function __get($label)
    {
        if (isset($this->vars[$label])) {
            return $this->vars[$label];
        }

        return false;
    }

    public function __isset($label)
    {
        return isset($this->vars[$label]);
    }

    public function assign($key, &$value)
    {
        $this->vars[$key] = &$value;
    }

    public function getPluginTemplatePath()
    {
        return $this->options['plugin_path']."templates".DIRECTORY_SEPARATOR;
    }

    public function fetch($file, $cached_name = null, $time_cached = null, $tpl = null)
    {
        global $jimbo;

        $pluginPath = $this->getPluginTemplatePath().$file;
        $path = $jimbo->tpl->template_dir.DIRECTORY_SEPARATOR.$file;

        if (file_exists($pluginPath)) {
            $path = $pluginPath;
        } else if (!file_exists($path)) {
            throw new SystemException(__('Template file %s not found', $path));
        }

        foreach($this->vars as $key => $val) {
            $$key = $val;
        }

        ob_start();

        include($path);

        $result = ob_get_contents();
        ob_end_clean();

        if (!is_null($tpl)) {
            $this->content = $result;
            $result = $this->fetch($tpl, $cached_name, $time_cached);
        }

        return $result;
    }

}

?>