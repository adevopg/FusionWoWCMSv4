<?php

use MX\MX_Controller;

class Password_recovery extends MX_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('password_recovery_model');

        $this->load->helper('email_helper');

        $this->load->library('security');
        $this->load->library('form_validation');
        $this->load->library('captcha');
        $this->load->library('recaptcha');

        $this->user->guestArea();

        requirePermission("view");

        if (!$this->config->item('has_smtp'))
        {
            redirect('errors');
        }
    }

    public function index()
    {
        clientLang("email_sent", "recovery");

        $this->template->setTitle(lang("password_recovery", "recovery"));

        $data = [
            "use_captcha" => $this->config->item('use_captcha'),
            "captcha_type" => $this->config->item('captcha_type'),
            "recaptcha_html" => $this->recaptcha->getScriptTag() . $this->recaptcha->getWidget()
        ];

        $content = $this->template->loadPage("password_recovery.tpl", $data);
        $box = $this->template->box(lang("password_recovery", "recovery"), $content);
        $this->template->view($box, "modules/auth/css/recovery.css", "modules/auth/js/recovery.js");
    }

    public function create_request()
    {
        $use_captcha = $this->config->item('use_captcha');
        $captcha_type = $this->config->item('captcha_type');

        $this->form_validation->set_rules('email', 'email', 'trim|required|valid_email');

        if ($use_captcha && $captcha_type == 'inbuilt')
        {
            $this->form_validation->set_rules('captcha', 'captcha', 'trim|required|exact_length[7]|alpha_numeric');
        }

        $this->form_validation->set_error_delimiters('', '');
        
        $data = [
            "messages" => false,
            "success" => []
        ];

        if ($this->form_validation->run())
        {
            //Check captcha
            if ($use_captcha)
            {
                $data['showCaptcha'] = true;

                if ($captcha_type == 'inbuilt') {
                    if ($this->input->post('captcha') != $this->captcha->getValue() || empty($this->input->post('captcha'))) {
                        $data['messages']["error"] = lang("captcha_invalid", "auth");
                        die(json_encode($data));
                    }
                } else if ($captcha_type == 'recaptcha') {
                    $recaptcha = $this->input->post('recaptcha');
                    $result = $this->recaptcha->verifyResponse($recaptcha)['success'];
                    if (!$result) {
                        $data['messages']["error"] = lang("captcha_invalid", "auth") . $result;
                        die(json_encode($data));
                    }
                } else if ($captcha_type == 'recaptcha3') {
                    $recaptcha = $this->input->post('recaptcha');
                    $score = $this->recaptcha->verifyScore($recaptcha);
                    if($score < 0.5) {
                        $data['messages']["error"] = lang("captcha_invalid", "auth");
                        die(json_encode($data));
                    }
                }
            }
            
            $email = $this->input->post("email");
            
            if ($this->external_account_model->emailExists($email))
            {
                $username = $this->password_recovery_model->get_username($email);
                $token = $this->generate_token($username, $email);

                $link = base_url() . 'password_recovery/reset_password?token=' . $token;
                sendMail($email, $this->config->item('server_name') . ': ' . lang("reset_password", "recovery"), $username, lang("email", "recovery") . ' <a href="' . $link . '">' . $link . '</a>', 1);

                $this->password_recovery_model->insert_token($token, $username, $email, $this->input->ip_address());
                $this->dblogger->createLog("user", "recovery", "Password recovery requested", [], Dblogger::STATUS_SUCCEED, $this->user->getId($username));
            }
            
            $data['messages']["success"] = lang("email_sent", "recovery");
        }
        else
        {
            $data['messages']["error"] = validation_errors();
        }
        die(json_encode($data));
    }

    public function reset_password()
    {
        clientLang("password_changed", "recovery");

        $this->form_validation->set_rules('token', 'token', 'trim|required');
        $this->form_validation->set_rules('new_password', 'new_password', 'trim|required|min_length[6]');

        $this->form_validation->set_error_delimiters('', '');

        if ($this->input->method() === 'post')
        {
            if ($this->form_validation->run())
            {
                $new_password = $this->input->post('new_password');
                $token = $this->input->post('token');
                $token_data = $this->password_recovery_model->get_token($token);

                if (!$token_data)
                {
                    $data['messages']["error"] = lang('invalid', 'recovery');
                    die(json_encode($data));
                }

                $this->external_account_model->setPassword($token_data['username'], $token_data['email'], $new_password);
                
                $this->dblogger->createLog("user", "recovery", "Password changed via reset", [], Dblogger::STATUS_SUCCEED, $this->user->getId($token_data['username']));

                $this->password_recovery_model->delete_token($token);

                $data['messages']["success"] = lang("password_reset_success", "recovery");
                die(json_encode($data));
            }
            else
            {
                $data['messages']["error"] = validation_errors();
                die(json_encode($data));
            }
        }

        $this->template->setTitle(lang("password_reset", "recovery"));

        $data = [];
        $data['token'] = $this->input->get('token');

        $content = $this->template->loadPage("password_reset.tpl", $data);
        $box = $this->template->box(lang("password_reset", "recovery"), $content);
        $this->template->view($box, "modules/auth/css/recovery.css", "modules/auth/js/recovery.js");
    }

    private function generate_token($username, $email)
    {
        $timestamp = time();
        $random_string = bin2hex(random_bytes(32));
        $token = hash('sha512', $username . $email . $timestamp . $random_string);
        return $token;
    }
}
