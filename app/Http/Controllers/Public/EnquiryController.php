<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\EnquiryResource;
use App\Models\Enquiry;
use App\Models\Location;
use App\Services\EnquiryNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Public contact-form create — destination-scoped when locationId is sent.
 */
class EnquiryController extends Controller
{
    public function __construct(
        private readonly EnquiryNotificationService $notifications,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'locationId' => ['required', 'integer', Rule::exists(Location::class, 'id')],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'consent' => ['required', 'boolean'],
        ]);

        $location = Location::query()->findOrFail($data['locationId']);

        $enquiry = Enquiry::create([
            'location_id' => $location->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'subject' => $data['subject'] ?: $location->name,
            'message' => $data['message'],
            'consent' => $data['consent'],
            'status' => 'new',
        ]);

        $this->notifications->notifyCreated($enquiry->load('location'));

        return response()->json([
            'enquiry' => new EnquiryResource($enquiry->refresh()->load('location')),
        ], 201);
    }
}
