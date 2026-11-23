<?php

namespace Tests\Feature;

use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Storage;
use Psr\Http\Message\RequestInterface;
use Tests\TestCase;

class SupabaseStorageTest extends TestCase
{
    public function test_catalog_uses_signed_s3_storage_without_object_acl_or_browser_credentials(): void
    {
        $sent = null;
        config([
            'filesystems.disks.supabase.region' => 'us-east-1',
            'filesystems.disks.supabase.key' => 'clave-simulada',
            'filesystems.disks.supabase.secret' => 'secreto-simulado',
            'filesystems.disks.supabase.bucket' => 'catalogo',
            'filesystems.disks.supabase.endpoint' => 'https://storage.example.test/storage/v1/s3',
            'filesystems.disks.supabase.url' => 'https://public.example.test/storage/v1/object/public/catalogo',
            'filesystems.disks.supabase.http_handler' => function (RequestInterface $request) use (&$sent) {
                $sent = $request;

                return Create::promiseFor(new Response(200, ['ETag' => '"mock"']));
            },
        ]);

        $disk = Storage::disk('supabase');
        $this->assertTrue($disk->put('catalog/products/example.jpg', 'contenido de prueba'));
        $this->assertSame('PUT', $sent->getMethod());
        $this->assertSame('/storage/v1/s3/catalogo/catalog/products/example.jpg', $sent->getUri()->getPath());
        $this->assertTrue($sent->hasHeader('Authorization'));
        $this->assertFalse($sent->hasHeader('x-amz-acl'));
        $this->assertFalse($sent->hasHeader('x-amz-sdk-checksum-algorithm'));
        $this->assertSame('https://public.example.test/storage/v1/object/public/catalogo/catalog/products/example.jpg',
            $disk->url('catalog/products/example.jpg'));
    }
}
