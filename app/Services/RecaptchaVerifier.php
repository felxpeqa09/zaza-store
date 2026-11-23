<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class RecaptchaVerifier
{
    public function verify(string $token): bool
    {
        $secret = (string) config('zaza.recaptcha.secret');
        $hostname = (string) config('zaza.recaptcha.hostname');
        if ($secret === '' || $hostname === '' || $token === '') {
            return false;
        }

        try {
            $response = Http::asForm()->connectTimeout(2)->timeout(4)
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => $secret,
                    'response' => $token,
                ]);
        } catch (ConnectionException) {
            return false;
        }

        return $response->successful()
            && $response->json('success') === true
            && strcasecmp($hostname, (string) $response->json('hostname')) === 0;
    }
}
