<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Symfony\Component\HttpFoundation\StreamedResponse;
use XMLWriter;

class SitemapController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        return response()->stream(function (): void {
            $xml = new XMLWriter;
            $xml->openUri('php://output');
            $xml->startDocument('1.0', 'UTF-8');
            $xml->startElementNs(null, 'urlset', 'http://www.sitemaps.org/schemas/sitemap/0.9');

            foreach (['home', 'about', 'privacy', 'returns', 'cookies'] as $page) {
                $this->entry($xml, route($page, absolute: false));
            }
            foreach (Category::query()->whereIn('slug', ['ropa', 'zapatos', 'accesorios'])->pluck('slug') as $slug) {
                $this->entry($xml, route('catalog', $slug, false));
            }
            foreach (Product::query()->activo()->select(['id', 'slug', 'updated_at'])->lazyById(300) as $product) {
                $this->entry($xml, route('products.show', $product->slug, false), $product->updated_at?->toDateString());
            }

            $xml->endElement();
            $xml->endDocument();
            $xml->flush();
        }, headers: ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    private function entry(XMLWriter $xml, string $path, ?string $modified = null): void
    {
        $xml->startElement('url');
        $xml->writeElement('loc', rtrim(config('app.url'), '/').$path);
        if ($modified) {
            $xml->writeElement('lastmod', $modified);
        }
        $xml->endElement();
    }
}
