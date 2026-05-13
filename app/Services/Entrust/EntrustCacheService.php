<?php

namespace App\Services\Entrust;

use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/**
 * Service to manage Entrust cache operations
 */
class EntrustCacheService
{
    /**
     * Clear all Entrust-related caches
     *
     * @return bool Whether any cache was cleared
     */
    public static function clear(): bool
    {
        $cacheStore = Cache::getStore();
        $isTaggable = $cacheStore instanceof TaggableStore;

        try {
            // Clear permission-role cache
            if ($isTaggable) {
                Cache::tags(Config::get('entrust.permission_role_table'))->flush();
            }

            // Clear role-user cache
            if ($isTaggable) {
                Cache::tags(Config::get('entrust.role_user_table'))->flush();
            }

            // Flush general cache as fallback
            Cache::flush();

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to clear Entrust cache: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear only permission-role cache
     */
    public static function clearPermissions(): void
    {
        $cacheStore = Cache::getStore();
        if ($cacheStore instanceof TaggableStore) {
            Cache::tags(Config::get('entrust.permission_role_table'))->flush();
        }
    }

    /**
     * Clear only role-user cache
     */
    public static function clearRoles(): void
    {
        $cacheStore = Cache::getStore();
        if ($cacheStore instanceof TaggableStore) {
            Cache::tags(Config::get('entrust.role_user_table'))->flush();
        }
    }
}

