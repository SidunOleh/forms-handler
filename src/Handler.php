<?php

namespace FormsHandler;

use Exception;

defined('ABSPATH') or die;

class Handler
{
    private static array $rules = [];

    private string $action;

    private array $fields;

    private string $to;

    private string $subject;

    private array $headers;

    private array $conf;

    public function __construct( 
        string $action, 
        array $fields, 
        string $to = '', 
        string $subject = '',
        array $headers = [], 
        array $conf = []
    ) {
        $this->action = $action;
        $this->fields = $fields;
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
        $input = $_POST;

        $validated = $this->validate($input);
        if ($validated === false) {
            $this->response(false);
        }

        do_action('forms_handlers_before_send', $this->action, $validated, $this->conf);

        $sent = $this->send($validated);

        do_action('forms_handlers_after_send', $this->action, $sent, $validated, $this->conf);

        $this->response($sent);
    }

    private function validate(array $data): array|false
    {
        $validated = [];
        foreach ($this->fields as $field => $rules) {
            if (
                ! in_array('required', $rules) and 
                ! isset($data[$field])
            ) {
                continue;
            }

            foreach ($rules as $rule) {
                if (! isset(self::$rules[$rule])) {
                    throw new Exception("Rule {$rule} not found.");
                }

                if (self::$rules[$rule]($field, $data)) {
                    $validated[$field] = $data[$field];
                } else {
                    return false;
                }
            }
        }

        return $validated;
    }

    private function send(array $data): bool
    {
        $msg = $this->msg($data);

        if (isset($data['email'])) {
            $this->headers[] = "Reply-To: {$data['email']} <{$data['email']}>";
        }

        return wp_mail($this->to, $this->subject, $msg, $this->headers);
    }

    private function msg(array $data)
    {
        $template = apply_filters(
            'forms_handlers_email_template', 
            FORMS_HANDLER_ROOT . '/src/views/emails/default.php',
            $this->action
        );

        $msg = $this->readTemplate($template, $data);

        return $msg;
    }

    private function readTemplate(string $template, array $data = []): string
    {
        ob_start();

        require $template;
        
        $msg = ob_get_clean();

        return $msg;
    }

    private function response(bool $status): never
    {
        wp_send_json(['status' => $status,]);
        wp_die();
    }

    public static function addRule(string $name, callable $fn): void
    {
        self::$rules[$name] = $fn;
    }
}