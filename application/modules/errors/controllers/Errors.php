<?php

use MX\MX_Controller;

class Errors extends MX_Controller
{
    public function index()
    {
        $this->template->show404();
    }
}
