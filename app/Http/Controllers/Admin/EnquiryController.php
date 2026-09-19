<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\EnquiryResource;
use App\Models\Enquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Spec "Enquiries" — perm `enquiries`. Index/show/update only (create is public).
 */
class EnquiryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $enquiries = Enquiry::query()
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['enquiries' => EnquiryResource::collection($enquiries)]);
    }

    public function show(Enquiry $enquiry): JsonResponse
    {
        return response()->json(['enquiry' => new EnquiryResource($enquiry)]);
    }

    public function update(Request $request, Enquiry $enquiry): JsonResponse
    {
        $data = $request->validate([
            'status' => ['sometimes', Rule::in(['new', 'in_progress', 'closed'])],
            'internalNotes' => ['nullable', 'string'],
        ]);

        $enquiry->update($this->mapCamel($data));

        return response()->json(['enquiry' => new EnquiryResource($enquiry->refresh())]);
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
