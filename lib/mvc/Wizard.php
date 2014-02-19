<?php
/*
 * when you want to create a wizard in your app.
 * first get a instance of the Wizard class or you can extend this to create your
 * own wizard class
 *
 * the wizard class contains a stepArray contains how many steps you want to create in your
 * wizard and the data structure i use is like the following:
 *         step_list => array(
 *             '1' => 'scriptPath1'
 *             '2' => 'scriptPath2'
 *             '3' => 'scriptPath3'
 *             '4' => 'scriptPath4'
 *         )
 * the core function of this class is wizard(), wizard() use to get and validate
 * the data and return the result
 * or even you can customize the stepArray, but what you have to do is overloading the
 * wizard()
 *
 */
class Wizard {

    private $step_list = array();
    private $now_step = 1;

    /**
     * set step list
     * @param array $step_list
     */
    public function setStepList(array $step_list) {
        $this->step_list = $step_list;
    }
    /**
     * get step list
     */
    public function getStepList() {
        return $this->step_list;
    }
    /**
     * add step to wizard
     * @param string $script
     */
    public function addStep($script) {
        if (!count($this->step_list)) {
            $this->step_list['1'] = $script;
        } else {
            $this->step_list[] = $script;
        }
    }
    /**
     * set step
     * @param int $index
     * @param string $script
     */
    public function setStep($index, $script) {
        $this->step_list[$index] = $script;
    }
    /**
     * unset step
     * @param int $index
     */
    public function unsetStep($index) {
        if (isset($this->step_list[$index]))
            unset($this->step_list[$index]);
    }
    /**
     * set now step
     * @param int $now_step
     */
    public function setNowStep($now_step = null) {
        $this->now_step = $now_step == null ? 1 : $now_step;
    }
    /**
     * get now step
     * @return int
     */
    public function getNowStep() {
        return $this->now_step;
    }

    /**
     * core function
     * action:
     *     -1: back
     *  1: next:
     *  0: stop here
     *
     * stepArray => array(
     *     '1' => 'scriptPath1',
     *     '2' => 'scriptPath2'
     *     '3' => 'scriptPath3'
     *     '4' => 'scriptPath4'
     * )
     */
    public function wizard($act) {

        if (!count($this->step_list)) {
            return '';
        }
        switch ($act) {
            case 'f':
                return $this->execForward();
            case 'b':
                return $this->execBackward();
        }
        return $this->step_list[$this->now_step];
    }
    /**
     * execute forward
     */
    private function execForward() {

        if ($this->now_step == count($this->step_list)) {
            return $this->step_list[$this->now_step];
        }
        $init_method_name = 'init' . $this->now_step;
        if (method_exists($this, $init_method_name)) {
            $this->$init_method_name();
        }
        $data_map = null;
        $get_data_method = 'getData' . $this->now_step;
        if (method_exists($this, $get_data_method)) {
            $data_map = $this->$get_data_method();
        }
        $result = true;
        $check_data_method = 'checkData' . $this->now_step;
        if (method_exists($this, $check_data_method)) {
            $result = $this->$check_data_method($data_map);
        }
        if ($result) {
            $this->_moveNext();
        }
        return $this->step_list[$this->now_step];
    }
    /**
     * execute backward
     */
    private function execBackward() {
        if ($this->now_step == 1) return $this->step_list[1];
        $this->_moveBack();
        return $this->step_list[$this->now_step];
    }
    /**
     * move forward
     */
    protected function _moveNext(){
        $this->now_step ++;
    }
    /**
     * move backward
     */
    protected function _moveBack(){
        $this->now_step --;
    }
}

