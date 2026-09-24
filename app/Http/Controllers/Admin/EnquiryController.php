<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesAdminPanel;
use App\Http\Resources\EnquiryResource;
use App\Models\Enquiry;
use App\Services\AccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Spec "Enquiries" — perm `enquiries`. Index/show/update only (create is public).
 * Location managers only see enquiries for their destinations; global admins see all.
 */
class EnquiryController extends Controller
{
    use AuthorizesAdminPanel;

    public function __construct(private readonly AccessService $access) {}

    public function index(Request $request): JsonResponse
    {
        $enquiries = Enquiry::query()
            ->with('location')
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->get()
            ->filter(fn (Enquiry $enquiry) => $this->canSee($request, $enquiry))
            ->values();

        return response()->json(['enquiries' => EnquiryResource::collection($enquiries)]);
    }

    public function show(Request $request, Enquiry $enquiry): JsonResponse
    {
        $this->authorizeSee($request, $enquiry);

        return response()->json([
            'enquiry' => new EnquiryResource($enquiry->load('location')),
        ]);
    }

    public function update(Request $request, Enquiry $enquiry): JsonResponse
    {
        $this->authorizeSee($request, $enquiry);

        $data = $request->validate([
            'status' => ['sometimes', Rule::in(['new', 'in_progress', 'closed'])],
            'internalNotes' => ['nullable', 'string'],
        ]);

        $enquiry->update($this->mapCamel($data));

        return response()->json([
            'enquiry' => new EnquiryResource($enquiry->refresh()->load('location')),
        ]);
    }

    private function authorizeSee(Request $request, Enquiry $enquiry): void
    {
        if (! $this->canSee($request, $enquiry)) {
            throw new HttpException(404, 'Not found.');
        }
    }

    private function canSee(Request $request, Enquiry $enquiry): bool
    {
        if ($this->isDeveloper($request)) {
            return true;
        }

        $admin = $this->actingAdmin($request);
        if (! $admin) {
            return false;
        }

        if ($this->access->isGlobal($admin)) {
            return true;
        }

        if ($enquiry->location_id === null) {
            return false;
        }

        return $this->access->canAccessLocation($admin, $enquiry->location_id);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mapCamel(array $data): array
    {
        if (array_key_exists('internalNotes', $data)) {
            $data['internal_notes'] = $data['internalNotes'];
            unset($data['internalNotes']);
        }

        return $data;
    }
}
