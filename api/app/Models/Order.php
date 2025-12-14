<?php

namespace App\Models;

use App\Observers\OrderObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(OrderObserver::class)]

/**
 * @property int $id
 * @property int $user_id
 * @property int $symbol_id
 * @property string $side
 * @property string $price
 * @property string $amount
 * @property int $status
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read User $user
 * @property-read Symbol $symbol
 */
class Order extends Model
{
    public const STATUS_OPEN = 1;
    public const STATUS_FILLED = 2;
    public const STATUS_CANCELLED = 3;

    public const SIDE_BUY = 'buy';
    public const SIDE_SELL = 'sell';

    protected $fillable = [
        'user_id',
        'symbol_id',
        'side',
        'price',
        'amount',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:8',
        'amount' => 'decimal:8',
        'status' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function symbol(): BelongsTo
    {
        return $this->belongsTo(Symbol::class);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isBuy(): bool
    {
        return $this->side === self::SIDE_BUY;
    }

    public function isSell(): bool
    {
        return $this->side === self::SIDE_SELL;
    }
}
