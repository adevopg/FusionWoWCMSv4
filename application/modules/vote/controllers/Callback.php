<?php

use MX\MX_Controller;

class Callback extends MX_Controller
{
    public function index($plugin)
    {
        $this->plugins->$plugin->handleCallback();
        exit;
    }
}
