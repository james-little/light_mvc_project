<?php

/**
 * email
 * =======================================================
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
class Email {

    protected $template;
    protected $template_variables;
    protected $encode = 'utf-8';

    /**
     * set encode
     * @param string $encode
     * @return \logic\adReward\utility\Email
     */
    public function setEncode($encode) {
        $this->encode = $encode;
        return $this;
    }
    /**
     * add cc
     * @param string $cc
     * @return \logic\adReward\utility\Email
     */
    public function addCc($cc) {
        $this->cc[] = $cc;
        return $this;
    }
    /**
     * set template file
     * @param string $template_file
     * @return \logic\adReward\utility\Email
     */
    public function setTemplate($template_file) {

        if (!is_file(realpath($template_file))) {
            return ;
        }
        $this->template = $template_file;
        return $this;
    }
    /**
     * assign variable to template
     * @param string $key
     * @param array $value
     * @return \logic\adReward\utility\Email
     */
    public function assign($key, $value) {
        $this->template_variables[$key] = $value;
        return $this;
    }
    /**
     * send mail
     * @param string $address
     * @param string $subject
     * @param string $message
     */
    public function send($address, $subject, $message = null) {

        if(!$address) {
            return ;
        }
        if ($this->template) {
            $this->sendByTemplate($address, $subject);
            return ;
        }
        $headers = $this->makeHeader();
        @mail($address, $subject, $message, $headers);
        $this->reset();
    }
    /**
     * apply template variables to template
     * @param string $address
     * @param string $subject
     */
    private function sendByTemplate($address, $subject) {

        $template_contents = @file_get_contents($this->template);
        $template_contents = $this->extractValues($template_contents);
        $headers = $this->makeHeader();
        @mail($mail_address, $subject, $template_contents);
        $this->reset();
    }
    /**
     * make header string
     */
    private function makeHeader() {

        $headers = array();
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = "Content-type: text/plain; charset={$this->encode}";
        return implode("\r\n", $headers);
    }
    /**
     * apply template variables to template
     */
    private function extractValues($template_contents) {

        if (empty($this->template_variables)) {
            return $template_contents;
        }
        foreach($this->template_variables as $key => $value) {
            $template_contents = str_replace('<% '.$key.' %>', $value, $template_contents);
        }
        return $template_contents;
    }
    /**
     * clear template file name and template variables
     */
    private function reset() {
        $this->template_variables = array();
        $this->template = null;
    }
}