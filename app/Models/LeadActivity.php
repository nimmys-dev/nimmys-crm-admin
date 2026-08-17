<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'user_id',
        'activity_type',
        'description',
        'properties',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Lead, LeadActivity>
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * @return BelongsTo<User, LeadActivity>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Tabler icon class according to activity type.
     */
    public function icon(): string
    {
        return match ($this->activity_type) {
            'created' => 'ti ti-sparkles',
            'updated' => 'ti ti-edit',
            'assigned', 'reassigned' => 'ti ti-user-check',
            'status_changed' => 'ti ti-arrows-exchange',
            'closed' => 'ti ti-flag-off',
            'call_logged' => 'ti ti-phone-incoming',
            'call_updated' => 'ti ti-phone-cog',
            'call_deleted' => 'ti ti-phone-x',
            'quotation_created' => 'ti ti-file-invoice',
            'quotation_updated' => 'ti ti-file-pencil',
            'quotation_downloaded' => 'ti ti-download',
            'followup_created' => 'ti ti-calendar-plus',
            default => 'ti ti-activity',
        };
    }

    /**
     * Human readable type label.
     */
    public function typeLabel(): string
    {
        return match ($this->activity_type) {
            'created' => 'Lead Created',
            'updated' => 'Lead Updated',
            'assigned' => 'Lead Assigned',
            'reassigned' => 'Lead Reassigned',
            'status_changed' => 'Status Changed',
            'closed' => 'Lead Closed',
            'call_logged' => 'Call Logged',
            'call_updated' => 'Call Updated',
            'call_deleted' => 'Call Deleted',
            'quotation_created' => 'Quotation Created',
            'quotation_updated' => 'Quotation Updated',
            'quotation_downloaded' => 'Quotation Downloaded',
            'followup_created' => 'Follow-up Scheduled',
            default => ucfirst(str_replace('_', ' ', $this->activity_type)),
        };
    }

    /**
     * Category for filtering activities.
     */
    public function category(): string
    {
        return match ($this->activity_type) {
            'call_logged', 'call_updated', 'call_deleted' => 'call',
            'quotation_created', 'quotation_updated', 'quotation_downloaded' => 'quotation',
            'assigned', 'reassigned' => 'assignment',
            'status_changed', 'closed' => 'status',
            default => 'general',
        };
    }

    /**
     * Background / Accent color class for icon.
     */
    public function iconColorClass(): string
    {
        return match ($this->activity_type) {
            'created' => 'bg-emerald-500 text-white',
            'updated' => 'bg-blue-500 text-white',
            'assigned', 'reassigned' => 'bg-indigo-500 text-white',
            'status_changed' => 'bg-purple-500 text-white',
            'closed' => 'bg-rose-500 text-white',
            'call_logged' => 'bg-cyan-600 text-white',
            'call_updated' => 'bg-sky-500 text-white',
            'call_deleted' => 'bg-gray-500 text-white',
            'quotation_created' => 'bg-amber-500 text-white',
            'quotation_updated' => 'bg-orange-500 text-white',
            'quotation_downloaded' => 'bg-teal-600 text-white',
            'followup_created' => 'bg-violet-500 text-white',
            default => 'bg-slate-500 text-white',
        };
    }
}
