<?php

namespace FormsHandler;

defined('ABSPATH') or die;

use Rakit\Validation\Validator;

class Handler
{
    private string $action;

    private array $rules;

    private string|array $to;

    private string $subject;

    private array $headers;

    private array $conf;

    public function __construct( 
        string $action, 
        array $rules, 
        string|array $to = '', 
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
        if ($validated = $this->validate()) {
            $this->response(false);
        }

        if (
            get_forms_settings('enable_recaptcha') and
            ! $this->verifyRecaptcha()
        ) {
            $this->response(false);
        }

        $this->uploadFiles($validated);

        do_action('forms_handlers_before_send', $this->action, $validated);

        $sent = $this->send($validated);

        do_action('forms_handlers_after_send', $this->action, $validated, $sent);

        if ($this->conf['persist'] ?? false) {
            FormsData::save($this->action, $validated, $sent);
        }

        $this->response($sent);
    }

    private function validate(): array|false
    {
        $validator = new Validator;
        $validation = $validator->make($_POST + $_FILES, $this->rules);
        $validation->validate();

        if ($validation->fails()) {
            return false;
        } else {
            return $validation->getValidData();
        }
    }

    private function verifyRecaptcha(): bool
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
        $emailTemplate = apply_filters(
            'forms_handlers_email_template', 
            FORMS_HANDLER_ROOT . '/src/views/emails/default.php',
            $this->action
        );

        $msg = new Message($data, $emailTemplate);

        if (isset($data['email'])) {
           array_unshift($this->headers, "Reply-To: {$data['email']} <{$data['email']}>");
        }

        return $msg->send($this->to, $this->subject, $this->headers);
    }

    private function response(bool $status): never
    {
        wp_send_json(['status' => $status,]);
        wp_die();
    }
}