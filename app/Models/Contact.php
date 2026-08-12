<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'first_name',
        'last_name',
        'gender',
        'email',
        'tel',
        'address',
        'building',
        'detail',
    ];

    public function getGenderLabelAttribute(): string
    {
        return match ($this->gender) {
            1 => '男性',
            2 => '女性',
            default => 'その他',
        };
    }

    public static function makeQuery($request)
    {
        $query = static::query();

        if ($request->filled('keyword')) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->keyword . '%')
                    ->orWhere('last_name', 'like', '%' . $request->keyword . '%')
                    ->orWhere('email', 'like', '%' . $request->keyword . '%');
            });
        }

        if ($request->filled('gender')) {
            if ($request->gender != 0) {
                $query->where('gender', '=', $request->gender);
            }
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', '=', $request->category_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', '=', $request->date);
        }

        return $query;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class)->withDefault();
    }

    public function contactTags(): HasMany
    {
        return $this->hasMany(ContactTag::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }
}
