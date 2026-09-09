<?php

class AuthMiddleware
{
    public static function handle(): void
    {
        if (!isset($_SESSION['user'])) {

            $_SESSION['error'] = 'Please login first.';

            header(
                'Location: /finovo-oms-and-wms/public/login'
            );

            exit;
        }
    }
}