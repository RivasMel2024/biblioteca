<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookCopy extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id',
        'barcode',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Status: DISPONIBLE, PRESTADA, DAÑADA, PERDIDA
    public const STATUS_AVAILABLE = 'DISPONIBLE';
    public const STATUS_LOANED = 'PRESTADA';
    public const STATUS_DAMAGED = 'DAÑADA';
    public const STATUS_LOST = 'PERDIDA';

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }
}
