<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Patient extends Model
{
    protected $fillable = ['no_rm', 'name', 'nik', 'address'];

    protected $hidden = ['nik_hash'];

    protected function casts(): array
    {
        return ['nik' => 'encrypted'];
    }

    protected static function booted(): void
    {
        static::creating(function (Patient $patient) {
            $patient->nik_hash = static::hashNik($patient->nik);
            $patient->no_rm ??= static::nextNoRm();
        });
    }

    public static function hashNik(string $nik): string
    {
        return hash('sha256', $nik);
    }

    /** RM0001, RM0002, ... Caller must wrap in DB::transaction() to be race-safe. */
    public static function nextNoRm(): string
    {
        $last = static::lockForUpdate()->orderByDesc('id')->value('no_rm');
        $next = $last ? (int) substr($last, 2) + 1 : 1;

        return sprintf('RM%04d', $next);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }
}
