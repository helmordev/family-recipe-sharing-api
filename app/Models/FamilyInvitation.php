<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $family_id
 * @property-read string $code
 * @property-read string $email
 * @property-read CarbonInterface|null $expires_at
 * @property-read CarbonInterface|null $used_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * */
final class FamilyInvitation extends Model
{
    protected $table = 'family_invitations';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'family_id',
        'code',
        'email',
        'expires_at',
        'used_at',
    ];

    /**
     * @return BelongsTo<Family, $this>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null
            && $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function isValid(): bool
    {
        return ! $this->isUsed() && ! $this->isExpired();
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query
            ->whereNull('used_at')
            ->where(function (Builder $builder): void {
                $builder->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'family_id' => 'integer',
            'code' => 'string',
            'email' => 'string',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
