<?php

namespace FormsHandler;

defined('ABSPATH') or die;

class Message
{
    private array $data;

    private string $template;

    public function __construct(array $data, string $template)
    {
        $this->data = $data;
        $this->template = $template;
    }

    public function send(
        string $to, 
        string $subject, 
        array $headers
    ): bool
    {
        if (isset($this->data['email'])) {
            $headers[] = "Reply-To: {$this->data['email']} <{$this->data['email']}>";
        }

        return wp_mail($to, $subject, $this->readTemplate(), $headers);
    }

    private function readTemplate(): string
    {
        ob_start();

        require $this->template;
        
        $msg = ob_get_clean();

        return $msg;
    }
}