<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

/**
 * @method static whereNull(string $string)
 * @method static where(string $string, $id)
 * @method static find($id)
 * @method static findOrFail($id)
 * @method static create(array $all)
 */
class Consultation extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function children(): HasMany
    {
        return $this->hasMany(Consultation::class, 'parent_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Consultation::class, 'parent_id');
    }

    public static function getTopParents()
    {
        $cacheKey = 'consultation_top_parents';

        return Cache::remember($cacheKey, 31536000, function () {
            return self::whereNull('parent_id')->get();
        });
    }

    public static function getChildrenOrFalse($id)
    {
        $cacheKey = "consultation_children_{$id}";

        return Cache::remember($cacheKey, 31536000, function () use ($id) {
            $children = self::where('parent_id', $id)->get();
            return $children->isNotEmpty() ? $children : false;
        });
    }

    public static function findConsultation($attr, $value)
    {
        $cacheKey = "consultation_find_{$attr}_{$value}";

        return Cache::remember($cacheKey, 31536000, function () use ($attr, $value) {
            return self::where($attr, $value)->where('enable', 1)->first();
        });
    }
}
