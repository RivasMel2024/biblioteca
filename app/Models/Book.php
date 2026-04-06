<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'ISBN',
        'category_id',
    ];

    protected $appends = [
        'total_copies',
        'available_copies',
        'is_available',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function copies()
    {
        return $this->hasMany(BookCopy::class);
    }

    public function loans()
    {
        return $this->hasManyThrough(Loan::class, BookCopy::class);
    }

    /**
     * Total de copias (conteo desde BookCopy)
     */
    public function totalCopies(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->copies()->count(),
        );
    }

    /**
     * Copias disponibles (estado = DISPONIBLE)
     */
    public function availableCopies(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->copies()
                ->where('status', BookCopy::STATUS_AVAILABLE)
                ->count(),
        );
    }

    /**
     * ¿Hay copias disponibles?
     */
    public function isAvailable(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->available_copies > 0,
        );
    }
}
