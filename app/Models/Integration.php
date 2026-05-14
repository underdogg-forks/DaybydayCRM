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
     *             static method. It remains only for backward compatibility
     *             with legacy code that has not yet been migrated.
     */
    public static function initBillingIntegration()
    {
        // Delegate to the container-resolved registry to avoid re-introducing
        // the service-locator anti-pattern here.
        /** @var \App\Services\Billing\BillingIntegrationRegistry $registry */
        $registry = app(\App\Services\Billing\BillingIntegrationRegistry::class);

        if (! $registry->isConfigured()) {
            return null;
        }

        return $registry->driver();
    }
}
