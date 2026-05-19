<?php

namespace App\Services\Setting;

use App\Helpers\GetDateFormat;
use App\Models\BusinessHour;
use App\Models\Setting;
use App\Services\ClientNumber\ClientNumberValidator;
use App\Services\InvoiceNumber\InvoiceNumberValidator;
use Carbon\Carbon;
use PragmaRX\Countries\Package\Services\Currency;

class UpdateOverallSettingsService
{
    public function __construct(
        private readonly ClientNumberValidator $clientNumberValidator,
        private readonly InvoiceNumberValidator $invoiceNumberValidator,
        private readonly Currency $currency,
    ) {
    }

    public function handle(array $data): UpdateOverallSettingsResult
    {
        $setting = Setting::first();
        if (! $setting) {
            $setting = Setting::query()->create([
                'company' => 'Default Company', 'currency' => 'USD', 'country' => 'US', 'language' => 'en',
                'vat' => 0, 'client_number' => 1, 'invoice_number' => 1, 'max_users' => 10,
            ]);
        }
        if (! $this->clientNumberValidator->validateClientNumber((int) $data['client_number'])) {
            return UpdateOverallSettingsResult::clientNumberInvalid();
        }
        if (! $this->invoiceNumberValidator->validateInvoiceNumber((int) $data['invoice_number'])) {
            return UpdateOverallSettingsResult::invoiceNumberInvalid();
        }
        if ($data['currency'] == $setting->currency && ! empty($data['vat'])) {
            $setting->vat = $data['vat'] * 100;
        } elseif ($data['currency'] != $setting->currency && $this->currency->hasCurrency($data['currency'])) {
            $setting->currency = $data['currency'];
            $setting->vat = empty($data['vat']) ? $this->currency->getCurrency($data['currency'])['vatPercentage'] : $data['vat'] * 100;
        } elseif (! empty($data['vat'])) {
            $setting->vat = $data['vat'] * 100;
        }

        $startTime = Carbon::parse('2020-01-01 ' . $data['start_time'] . ':00');
        $endTime = Carbon::parse('2020-01-01 ' . $data['end_time'] . ':00');
        if ($startTime->gt($endTime)) { $tmp = clone $endTime; $endTime = $startTime; $startTime = $tmp; }
        elseif ($startTime->eq($endTime)) { $endTime->addHour(); }

        foreach (BusinessHour::all() as $businessHour) {
            $businessHour->update(['open_time' => $startTime->format('H:i:s'), 'close_time' => $endTime->format('H:i:s')]);
        }

        $setting->client_number = $data['client_number'];
        $setting->invoice_number = $data['invoice_number'];
        if (isset($data['company'])) { $setting->company = $data['company']; }
        $setting->country = $data['country'];
        $setting->language = $data['language'];
        $setting->save();
        cache()->delete(GetDateFormat::CACHE_KEY);

        return UpdateOverallSettingsResult::success();
    }
}
