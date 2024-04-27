<?php

namespace FormsHandler;

function get_forms_settings(string $name) {
    $settings = get_option('forms_handler_settings', []);
    
    return $settings[$name] ?? null;
}
