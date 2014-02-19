<?php
namespace common\controller;

use common\logic\HttpParameter;
use core\Controller;

class AppController extends Controller {

    public function preFilter() {
        parent::preFilter();
        $this->_param = HttpParameter::getInstance();
    }
}