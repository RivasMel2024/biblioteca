<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'book_id',
        'book_copy_id',
        'returned_at',
        'return_date',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'returned_at' => 'datetime',
        'return_date' => 'datetime',
    ];

    public function isActive(): Attribute
    {
        return Attribute::make(
            get: fn () => is_null($this->returned_at),
        );
    }

    public function isOverdue(): Attribute
    {
        return Attribute::make(
            get: fn () => is_null($this->returned_at) && now() > $this->return_date,
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bookCopy()
    {
        return $this->belongsTo(BookCopy::class);
    }

    public function book()
    {
        return $this->hasOneThrough(Book::class, BookCopy::class, 'id', 'id', 'book_copy_id', 'book_id');
    }

    public function fine()
    {
        return $this->hasOne(Fine::class);
    }
}

