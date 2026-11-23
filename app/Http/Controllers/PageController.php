<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\View\View;

class PageController extends Controller
{
    public function about(): View
    {
        $address = config('zaza.contact.address');
        $query = rawurlencode($address.', Colombia');
        $externalMap = 'https://www.google.com/maps/search/?api=1&query='.$query;
        $embed = config('zaza.map_embed_url') ?: 'https://maps.google.com/maps?q='.$query.'&output=embed';
        $map = filter_var($embed, FILTER_VALIDATE_URL) && parse_url($embed, PHP_URL_SCHEME) === 'https'
            && in_array(parse_url($embed, PHP_URL_HOST), ['www.google.com', 'maps.google.com', 'www.openstreetmap.org'], true)
            ? $embed : null;

        return view('pages.about', compact('address', 'map', 'externalMap'));
    }

    public function privacy(): View
    {
        return view('pages.privacy', ['contact' => config('zaza.contact')]);
    }

    public function returns(): View
    {
        return view('pages.returns', ['contact' => config('zaza.contact')]);
    }

    public function robots(): Response
    {
        $body = "User-agent: *\nDisallow: /admin\nDisallow: /cuenta\nDisallow: /checkout\nDisallow: /pedidos\nDisallow: /carrito\nDisallow: /favoritos\nDisallow: /webhooks\n";
        $body .= 'Sitemap: '.rtrim(config('app.url'), '/').route('sitemap', absolute: false)."\n";

        return response($body, headers: ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
