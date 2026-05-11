<?php

namespace App\Http\Controllers;

use App\Enums\PermissionName;
use App\Events\LeadAction;
use App\Http\Requests\Lead\StoreLeadRequest;
use App\Http\Requests\Lead\UpdateLeadAssignRequest;
use App\Http\Requests\Lead\UpdateLeadDeadlineRequest;
use App\Http\Requests\Lead\UpdateLeadStatusRequest;
use App\Http\Requests\Lead\UpdateLeadFollowUpRequest;
use App\Models\Client;
use App\Models\Lead;
use App\Models\Setting;
use App\Models\Status;
use App\Models\User;
use App\Services\Invoice\InvoiceCalculator;
use App\Services\Lead\LeadService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LeadsController extends Controller
{
    public const CREATED = 'created';

    public const UPDATED_STATUS = 'updated_status';

    public const UPDATED_DEADLINE = 'updated_deadline';

    public const UPDATED_ASSIGN = 'updated_assign';

    public function __construct(private LeadService $leadService)
    {
        $this->middleware('permission:' . PermissionName::LEAD_VIEW->value, ['only' => ['index', 'show', 'leadsJson', 'allLeads']]);
        $this->middleware('lead.create', ['only' => ['create']]);
        $this->middleware('lead.assigned', ['only' => ['updateAssign']]);
        $this->middleware('lead.update.status', ['only' => ['updateStatus']]);
        $this->middleware('permission:' . PermissionName::LEAD_DELETE->value, ['only' => ['destroy', 'destroyJson']]);
    }

    public function index()
    {
        return view('leads.index')
            ->withStatuses(Status::typeOfLead()->get());
    }

    /**
     * Data for Data tables.
     *
     * @return mixed
     */
    public function leadsJson()
    {
        $leads = Lead::with(['user', 'creator', 'client.primaryContact', 'status'])->get();

        $leads->map(function ($item) {
            return [$item['visible_deadline_date'] = $item['deadline']->format(carbonDate()), $item['visible_deadline_time'] = $item['deadline']->format(carbonTime())];
        });

        return $leads->toJson();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create($client_external_id = null)
    {
        $client = Client::whereExternalId($client_external_id);

        return view('leads.create')
            ->withUsers(User::with(['department'])->get()->pluck('nameAndDepartmentEagerLoading', 'id'))
            ->withClients(Client::query()->pluck('company_name', 'external_id'))
            ->withClient($client ?: null)
            ->withStatuses(Status::typeOfLead()->pluck('title', 'id'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreLeadRequest|Request $request
     *
     * @return Response
     */
    public function store(StoreLeadRequest $request)
    {
        $lead = $this->leadService->create($request->validated(), auth()->id());

        event(new LeadAction($lead, self::CREATED));
        session()->flash('flash_message', __('Lead successfully added'));

        return redirect()->route('leads.show', $lead->external_id);
    }

    public function destroy(Lead $lead, Request $request)
    {
        $deleteOffers = $request->delete_offers ? true : false;
        if ($lead->offers && $deleteOffers) {
            $lead->offers()->delete();
        } elseif ($lead->offers) {
            foreach ($lead->offers as $offer) {
                $offer->update([
                    'source_id'   => null,
                    'source_type' => null,
                ]);
            }
        }

        $lead->delete();

        session()->flash('flash_message', __('Lead deleted'));

        if ($request->expectsJson()) {
            return response()->json(['message' => __('Lead deleted')], 200);
        }

        return redirect()->back();
    }

    public function destroyJson(Lead $lead, Request $request)
    {
        $deleteOffers = $request->delete_offers ? true : false;
        if ($lead->offers && $deleteOffers) {
            $lead->offers()->delete();
        } elseif ($lead->offers) {
            foreach ($lead->offers as $offer) {
                $offer->update([
                    'source_id'   => null,
                    'source_type' => null,
                ]);
            }
        }

        $lead->delete();

        return response('OK');
    }

    public function updateAssign($external_id, UpdateLeadAssignRequest $request)
    {
        if ( ! auth()->user()->can(PermissionName::LEAD_ASSIGN->value)) {
            session()->flash('flash_message_warning', __('You do not have permission to assign leads'));

            return redirect()->back();
        }
        $lead = $this->findByExternalId($external_id);
        $this->leadService->assign($lead, $request->validated('user_assigned_id'));

        event(new LeadAction($lead, self::UPDATED_ASSIGN));
        session()->flash('flash_message', __('New user is assigned'));

        return redirect()->back();
    }

    /**
     * Update the follow up date (Deadline).
     *
     * @return mixed
     */
    public function updateFollowup(UpdateLeadFollowUpRequest $request, $external_id)
    {
        $lead = $this->findByExternalId($external_id);

        $validated = $request->validated();
        $this->leadService->updateFollowup($lead, $validated['deadline'], $validated['contact_time']);
        event(new LeadAction($lead, self::UPDATED_DEADLINE));
        session()->flash('flash_message', __('New follow up date is set'));

        return redirect()->back();
    }

    /**
     * Update the deadline for a lead.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function updateDeadline(UpdateLeadDeadlineRequest $request, $external_id)
    {
        $lead = $this->findByExternalId($external_id);

        $this->leadService->updateDeadline(
            $lead,
            $request->validated('deadline_date'),
            $request->validated('deadline_time')
        );

        event(new LeadAction($lead, self::UPDATED_DEADLINE));

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Deadline successfully updated'),
            ]);
        }

        session()->flash('flash_message', __('Deadline successfully updated'));

        return redirect()->back();
    }

    /**
     * Display the specified resource.
     *
     * @param int $external_id
     *
     * @return Response
     */
    public function show($external_id)
    {
        $lead = $this->findByExternalId($external_id);

        $offers = $lead->offers->map(function ($offer) {
            return new InvoiceCalculator($offer);
        });

        return view('leads.show')
            ->withLead($lead)
            ->withOffers($offers)
            ->withUsers(User::with(['department'])->get()->pluck('nameAndDepartmentEagerLoading', 'id'))
            ->withCompanyname(Setting::first()->company)
            ->withStatuses(Status::typeOfLead()->pluck('title', 'id'));
    }

    /**
     * Complete lead.
     *
     * @return mixed
     */
    public function updateStatus($external_id, UpdateLeadStatusRequest $request)
    {
        if ( ! auth()->user()->can(PermissionName::LEAD_UPDATE_STATUS->value)) {
            session()->flash('flash_message_warning', __('You do not have permission to change lead status'));

            return redirect()->route('leads.show', $external_id);
        }
        $lead = $this->findByExternalId($external_id);

        if (! $this->leadService->updateStatus($lead, $request->validated())) {
            session()->flash('flash_message_warning', __('Invalid status for lead'));

            return redirect()->back();
        }
        event(new LeadAction($lead, self::UPDATED_STATUS));
        session()->flash('flash_message', __('Lead status updated'));

        return redirect()->back();
    }

    public function convertToOrder(Lead $lead)
    {
        $invoice = $lead->convertToOrder();

        return $invoice->external_id;
    }

    /**
     * @return mixed
     */
    public function findByExternalId($external_id)
    {
        return Lead::whereExternalId($external_id)->firstOrFail();
    }
}
