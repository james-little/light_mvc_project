<?php
namespace test\controller;

use core\Controller;

class IndexController extends Controller {


    public function index() {
//         $tree_model = new \test\model\TestTreeModel();
//         $tree_model->initTree(0);

//         $model = new \test\model\UserAccountModel();
//         $result = $model->queryAll('SELECT * FROM user_account');
//         $this->_view_param = $result;
        $this->setViewParam("test", "test");
    }

    public function renderError() {

    }
}
