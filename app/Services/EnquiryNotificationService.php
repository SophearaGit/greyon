<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Developer;
use App\Models\Enquiry;
use App\Models\StaffNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Fan-out contact enquiries to:
 * - every active Developer
 * - every active global Admin (sees all destinations)
 * - every active Admin who canAccessLocation(enquiry.location_id)
 *   (destination managers for that pick)
 */
class EnquiryNotificationService
{
    public function __construct(
        private readonly AccessService $access,
    ) {}

    public function notifyCreated(Enquiry $enquiry): void
    {
        $enquiry->loadMissing('location');
        $location = $enquiry->location;
        $locationName = $location?->name ?? $enquiry->subject;

        foreach ($this->recipientsForLocation($enquiry->location_id) as $recipient) {
            StaffNotification::create([
                'notifiable_type' => $recipient::class,
                'notifiable_id' => $recipient->getKey(),
                'type' => 'enquiry.created',
                'location_id' => $enquiry->location_id,
                'title' => 'New enquiry',
                'body' => sprintf(
                    '%s · %s · %s',
                    $locationName,
                    $enquiry->name,
                    $enquiry->email,
                ),
                'data' => [
                    'enquiryId' => $enquiry->id,
                    'locationId' => $enquiry->location_id,
                    'locationName' => $locationName,
                    'guestName' => $enquiry->name,
                    'guestEmail' => $enquiry->email,
                    'subject' => $enquiry->subject,
                ],
            ]);
        }
    }

    /**
     * @return Collection<int, Model>
     */
    private function recipientsForLocation(mixed $locationId): Collection
    {
        $developers = Developer::query()
            ->where('status', 'active')
            ->get();

        $admins = Admin::query()
            ->where('status', 'active')
            ->with(['packages.roles'])
            ->get()
            ->filter(function (Admin $admin) use ($locationId) {
                if ($this->access->isGlobal($admin)) {
                    return true;
                }

                if ($locationId === null) {
                    return false;
                }

                return $this->access->canAccessLocation($admin, $locationId);
            })
            ->values();

        return $developers->concat($admins)->values();
    }
}
