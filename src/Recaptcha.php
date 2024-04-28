<?php

namespace FormsHandler;

defined('ABSPATH') or die;

class Recaptcha
{
    private string $siteKey;

    private string $secretKey;

    public function __construct(
        string $siteKey,
        string $secretKey
    )
    {
        $this->siteKey = $siteKey;
        $this->secretKey = $secretKey;
    }

    public function verify(string $code): bool
    {
        $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
            'body' => [
                'secret' => $this->secretKey,
                'response' => $code,
            ],
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $data = json_decode($response['body'], true);

        return $data['success'];
    }
}