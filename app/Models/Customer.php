<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $guarded = ['id'];

    public function getCodeAttribute(): string
    {
        return $this->customer_code ?? '';
    }

    public function getDisplayNameAttribute(): string
    {
        if (filled($this->company)) {
            return (string) $this->company;
        }

        if (filled($this->contact_name)) {
            return "{$this->contact_name} (Client)";
        }

        return (string) ($this->customer_code ?: 'Unnamed Customer');
    }

    public function getNameAttribute(): string
    {
        return $this->display_name;
    }

    public function setCompanyAttribute($value): void
    {
        $this->attributes['company'] = (is_string($value) && trim($value) === '') ? null : $value;
    }

    public function setEmailAttribute($value): void
    {
        $this->attributes['email'] = (is_string($value) && trim($value) === '') ? null : $value;
    }

    public function setGstNumberAttribute($value): void
    {
        $this->attributes['gst_number'] = (is_string($value) && trim($value) === '') ? null : (is_string($value) ? strtoupper(trim($value)) : $value);
    }

    public function setPanNumberAttribute($value): void
    {
        $this->attributes['pan_number'] = (is_string($value) && trim($value) === '') ? null : (is_string($value) ? strtoupper(trim($value)) : $value);
    }

    /**
     * Local query scope for role-based customer data isolation.
     *
     * - Admin: all customers.
     * - Manager: customers with sales_person_id in downline OR having projects with coordinator_id/sales_person_id in downline.
     * - Coordinator: customers having projects assigned to coordinator, or customers onboarded by/attributed to coordinator.
     * - Sales: customers strictly attributed to this salesperson (sales_person_id == user->id).
     */
    public function scopeForUser(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();

        if (! $user) {
            return $query;
        }

        if ($user->role === 'Admin') {
            return $query;
        }

        if ($user->role === 'Manager') {
            $downlineIds = $user->getDownlineUserIds();

            return $query->where(function ($subQuery) use ($downlineIds) {
                $subQuery->whereIn('sales_person_id', $downlineIds)
                    ->orWhereHas('projects', function ($projectQuery) use ($downlineIds) {
                        $projectQuery->whereIn('coordinator_id', $downlineIds)
                            ->orWhereIn('sales_person_id', $downlineIds);
                    });
            });
        }

        if ($user->role === 'Sales') {
            return $query->where('sales_person_id', $user->id);
        }

        // Coordinator: customers with projects assigned to coordinator or created/attributed to them
        return $query->where(function ($subQuery) use ($user) {
            $subQuery->where('sales_person_id', $user->id)
                ->orWhere('created_by', $user->id)
                ->orWhereHas('projects', fn ($projectQuery) => $projectQuery->where('coordinator_id', $user->id));
        });
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function salesPerson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_person_id')->where('role', 'Sales');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function followups(): HasMany
    {
        return $this->hasMany(Followup::class)->orderByDesc('happened_on');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
