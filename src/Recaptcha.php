<?php

namespace FormsHandler;

defined('ABSPATH') or die;

class Recaptcha
{
    private string $siteKey;

    private string $secretKey;

    private string $verifyUri;

    public function __construct(
        string $siteKey,
        string $secretKey
    )
    {
        $this->siteKey = $siteKey;
        $this->secretKey = $secretKey;
        $this->verifyUri = 'https://www.google.com/recaptcha/api/siteverify';
    }

    public function verify(string $code): bool
    {
        $response = wp_remote_post($this->verifyUri, [
            'body' => [
                'secret' => $this->secretKey,
                'response' => $code,
            ],
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $data = json_decode($response['body']);

        return $data->success;
    }
}