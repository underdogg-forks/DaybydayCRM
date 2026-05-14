<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Integration extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'client_id', 'client_secret', 'api_key', 'org_id', 'api_type', 'user_id'];

    /**
     * @return mixed
     *
     * @throws Exception
     */
    public static function getApi($type)
    {
        $integration = self::query()->where([
            'api_type' => $type,
        ])->first();
        if ($integration) {
            $className = ucfirst($integration->name);

            call_user_func_array(['App\\' . $className, 'initialize'], [$integration]);
            $apiInstance = call_user_func_array(['App\\' . $className, 'getInstance'], []);

            return $apiInstance;
        }

        return false;
    }

    public static function initBillingIntegration()
    {
        $integration = self::whereApiType('billing')->first();
        if ( ! $integration) {
            return null;
        }

        return $integration->api_class;
    }

    public function getApiClassAttribute()
    {
        $candidates = array_filter([
            $this->name,
            'App\\' . ltrim($this->name, '\\'),
        ]);

        foreach ($candidates as $candidate) {
            if (class_exists($candidate)) {
                return new $candidate();
            }
        }

        return null;
    }
}
