<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'dob',
        'gender',
        'address',
        'insurance_provider',
        'insurance_member_id',
        'emergency_contact',
    ];

    protected $casts = [
        'dob' => 'date',
        'emergency_contact' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function intakeForms()
    {
        return $this->hasMany(IntakeForm::class);
    }

    public function getEmergencyContactNameAttribute(): ?string
    {
        return $this->emergency_contact['name'] ?? null;
    }

    public function getEmergencyContactPhoneAttribute(): ?string
    {
        return $this->emergency_contact['phone'] ?? null;
    }

    public function getAgeAttribute(): ?int
    {
        if (!$this->dob) {
            return null;
        }

        return $this->dob->diffInYears(now());
    }
}