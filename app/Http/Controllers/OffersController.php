<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\OfferStatus;
use App\Http\Requests\Offer\CreateOfferRequest;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Lead;
use App\Models\Offer;
use App\Models\Product;
use App\Services\InvoiceNumber\InvoiceNumberService;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;

class OffersController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:offer-create', ['only' => ['create']]);
        $this->middleware('permission:offer-edit', ['only' => ['update', 'won', 'lost']]);
    }

    public function getOfferInvoiceLinesJson(Offer $offer)
    {
        return $offer->invoiceLines()->with(['product' => function ($q) {
            $q->select('id', 'external_id', 'name');
        }])->get(['title', 'comment', 'price', 'quantity', 'type', 'product_id']);
    }

    public function update(Request $request, Offer $offer)
    {
        $offer->invoiceLines()->forceDelete();
        foreach ($request->all() as $line) {
            if ( ! $line['title'] || ! $line['type'] || ! $line['price'] || ! $line['quantity']) {
                return response('missing fields', 422);
            }

            $invoiceLine = InvoiceLine::make([
                'title'      => $line['title'],
                'type'       => $line['type'],
                'quantity'   => $line['quantity'] ?: 1,
                'comment'    => $line['comment'] ?? null,
                'price'      => $line['price'] * 100,
                'product_id' => isset($line['product']) && $line['product'] ? Product::whereExternalId($line['product'])->first()->id : null,
            ]);
            $offer->invoiceLines()->save($invoiceLine);
        }
    }

    public function create(CreateOfferRequest $request, string $external_id)
    {
        $lead = Lead::query()->where('external_id', $external_id)->first();
        if ($lead) {
            if (! $lead->client_id) {
                return response()->json(['message' => 'This lead must be associated with a client before creating an offer'], 422);
            }

            return $this->createOfferForSource($request, $lead->id, $lead->client_id, Lead::class);
        }

        $client = Client::query()->where('external_id', $external_id)->first();
        if (! $client) {
            return response()->json(['message' => 'Offer source was not found'], 404);
        }

        return $this->createOfferForSource($request, $client->id, $client->id, Client::class);
    }

    private function createOfferForSource(CreateOfferRequest $request, int $sourceId, int $clientId, string $sourceType)
    {
        $offer = Offer::query()->create([
            'status'      => OfferStatus::inProgress()->getStatus(),
            'client_id'   => $clientId,
            'external_id' => Uuid::uuid4()->toString(),
            'source_id'   => $sourceId,
            'source_type' => $sourceType,
        ]);

        foreach ($request->validated() as $line) {
            $invoiceLine = InvoiceLine::make([
                'title'      => $line['title'],
                'type'       => $line['type'],
                'quantity'   => $line['quantity'] ?: 1,
                'comment'    => $line['comment'] ?? null,
                'price'      => $line['price'] * 100,
                'product_id' => isset($line['product']) && $line['product'] ? Product::whereExternalId($line['product'])->first()->id : null,
            ]);
            $offer->invoiceLines()->save($invoiceLine);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'OK'], 200);
        }

        return response('OK');
    }

    public function won(Request $request)
    {
        $offer = Offer::whereExternalId($request->get('offer_external_id'))->with('invoiceLines')->firstOrFail();
        $offer->setAsWon();

        $invoice                 = Invoice::query()->create($offer->toArray());
        $invoice->offer_id       = $offer->id;
        $invoice->invoice_number = app(InvoiceNumberService::class)->setNextInvoiceNumber();
        $invoice->status         = InvoiceStatus::draft()->getStatus();
        $invoice->save();

        $lines    = $offer->invoiceLines;
        $newLines = collect();
        foreach ($lines as $invoiceLine) {
            $invoiceLine->offer_id = null;
            $newLines->push(InvoiceLine::make($invoiceLine->toArray()));
        }

        $invoice->invoiceLines()->saveMany($newLines);

        return redirect()->back();
    }

    public function lost(Request $request)
    {
        $offer = Offer::whereExternalId($request->get('offer_external_id'))->firstOrFail();
        $offer->setAsLost();

        return redirect()->back();
    }
}
