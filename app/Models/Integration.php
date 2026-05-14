<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Integration extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'client_id', 'client_secret', 'api_key', 'org_id', 'api_type', 'user_id'];

    /**
     * @deprecated Use BillingIntegrationRegistry instead of calling this
     *             static method directly. This method remains only for backward
     *             compatibility with legacy code that still calls
     *             Integration::getApi($type).
     *
     * @param  string|null  $type
     * @return mixed|null
     */
    public static function getApi($type)
    {
        $type = is_string($type) ? strtolower(trim($type)) : null;

        if ($type === 'billing') {
            return self::initBillingIntegration();
        }

        return null;
    }

    /**
     * @deprecated Use BillingIntegrationRegistry instead of calling this
     *             static method. It remains only for backward compatibility
     *             with legacy code that has not yet been migrated.
     */
    public static function initBillingIntegration()
    {
        // Delegate to the container-resolved registry to avoid re-introducing
        // the service-locator anti-pattern here.
        /** @var \App\Services\Billing\BillingIntegrationRegistry $registry */
        $registry = app(\App\Services\Billing\BillingIntegrationRegistry::class);

        // Preserve the historical shim behavior by returning a fresh adapter
        // instance on every call instead of the registry's cached driver.
        $driver = $registry->driver();

        return clone $driver;
    }
}
