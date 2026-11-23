<?php

namespace App\Storage;

use Aws\CommandInterface;
use Aws\Middleware;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Filesystem\FilesystemManager;

final class SupabaseStorage
{
    public static function make(FilesystemManager $filesystems, array $config): AwsS3V3Adapter
    {
        $disk = $filesystems->createS3Driver($config);
        $disk->getClient()->getHandlerList()->appendInit(Middleware::mapCommand(
            static function (CommandInterface $command): CommandInterface {
                // Public reads are controlled by the Supabase bucket, which has no per-object ACL.
                unset($command['ACL']);

                return $command;
            },
        ), 'supabase-no-object-acl');

        return $disk;
    }
}
