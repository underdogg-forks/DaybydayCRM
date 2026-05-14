<?php

namespace App\Services\Storage;

use App\Models\Integration;

class GetStorageProvider
{
    private static $storageProviders = [
        'local'       => Local::class,
        'dropbox'     => Dropbox::class,
        'googledrive' => GoogleDrive::class,
    ];

    public static function getStorage()
    {
        $integration = Integration::query()->where('api_type', 'file')->first();

        return self::fromIntegration($integration);
    }

    public static function fromIntegration(?Integration $integration): object
    {
        if (app()->environment('testing', 'local')) {
            return new Local();
        }

        return new (self::providerClassFromIntegration($integration))();
    }

    public static function providerClassFromIntegration(?Integration $integration): string
    {
        if ($integration) {
            $providerName = mb_strtolower($integration->name);

            return self::$storageProviders[$providerName] ?? Local::class;
        }

        return Local::class;
    }
}
