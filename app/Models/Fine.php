<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fine extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'days_overdue',
        'daily_amount',
        'total_amount',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'paid_at' => 'datetime',
        'daily_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    // Status: PENDIENTE, PAGADA, CONDONADA
    public const STATUS_PENDING = 'PENDIENTE';
    public const STATUS_PAID = 'PAGADA';
    public const STATUS_FORGIVEN = 'CONDONADA';

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function isPaid(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === self::STATUS_PAID,
        );
    }
}
