<?php

class pluginSociallogin extends Plugin
{

    public $config = null;

    public function __construct()
    {
        // get the plugin config
        include('_plugin_config.inc.php');

        // load config into the object
        $this->config = $pluginConfig;
    }

    public function getPluginDetails()
    {
        return $this->config;
    }

}