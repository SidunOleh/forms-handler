<?php

namespace FormsHandler;

defined('ABSPATH') or die;

use Rakit\Validation\Validator;

class Handler
{
    private string $action;

    private array $rules;

    private string $to;

    private string $subject;

    private array $headers;

    private array $conf;

    public function __construct( 
        string $action, 
        array $rules, 
        string $to = '', 
        string $subject = '',
        array $headers = [], 
        array $conf = []
    ) 
    {
        $this->action = $action;
        $this->rules = $rules;
        $this->to = $to ?: get_option('admin_email');
        $this->subject = $subject ?: get_bloginfo('name');
        $this->headers = $headers;
        $this->conf = $conf;
    }

    public function register(): void
    {
        add_action("wp_ajax_{$this->action}",  [$this, 'handle']);
        add_action("wp_ajax_nopriv_{$this->action}", [$this, 'handle']);
        add_filter('wp_mail_content_type', fn () => 'text/html');
    }

    public function handle(): never
    {
        $validator = new Validator;
        $validation = $validator->make($_POST + $_FILES, $this->rules);
        $validation->validate();

        if ($validation->fails()) {
            $this->response(false);
        }

        if (
            get_forms_settings('enable_recaptcha') and
            ! $this->checkRecaptcha()
        ) {
            $this->response(false);
        }

        $validated = $validation->getValidData();

        $this->uploadFiles($validated);

        do_action('forms_handlers_before_send', $this->action, $validated, $this->conf);

        $sent = $this->send($validated);

        do_action('forms_handlers_after_send', $this->action, $sent, $validated, $this->conf);

        if ($this->conf['persist'] ?? false) {
            FormsData::save($this->action, $sent, $validated);
        }

        $this->response($sent);
    }

    private function checkRecaptcha(): bool
    {
        $recaptcha = new Recaptcha(
            get_forms_settings('recaptcha_site_key'),
            get_forms_settings('recaptcha_secret_key')
        );
    
        $code = $_POST['recaptcha_response'] ?? null;
        if (
            ! $code or 
            ! $recaptcha->verify($code)
        ) {
            return false;
        }

        return true;
    }

    private function uploadFiles(array &$data)
    {
        foreach ($data as &$item) {
            if (isset($item['tmp_name'])) {
                $item = $this->upload($item);
            }

            if (is_array($item)) {
                $this->uploadFiles($item);
            }
        }
    }

    private function upload(array $file): string|false
    {
        if ($file['error']) {
            return false;
        }

        $uploaded = wp_handle_upload($file, ['test_form' => false,]);

        if (isset($uploaded['error'])) {
            return false;
        }

        return $uploaded['file'];
    }

    private function send(array $data): bool
    {
        $msgTemplate = apply_filters(
            'forms_handlers_email_template', 
            FORMS_HANDLER_ROOT . '/src/views/emails/default.php',
            $this->action
        );

        $msg = new Message($data, $msgTemplate);

        if (isset($data['email'])) {
            $this->headers[] = "Reply-To: {$data['email']} <{$data['email']}>";
        }

        return $msg->send($this->to, $this->subject, $this->headers);
    }

    private function response(bool $status): never
    {
        wp_send_json(['status' => $status,]);
        wp_die();
    }
}