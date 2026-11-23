<?php

namespace App\Http\Controllers;

use App\Support\Keepalive;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DatabaseHealthController extends Controller
{
    public function __invoke(Request $request, Keepalive $keepalive): JsonResponse
    {
        $secret = (string) config('zaza.health_token');
        abort_if($secret === '' || ! hash_equals($secret, (string) $request->header('X-Health-Token')), 404);

        $keepalive->ping('http');

        return response()->json(['status' => 'ok']);
    }
}
