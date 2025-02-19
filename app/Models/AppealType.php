<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static where(string $string, mixed $text)
 * @method static findOrFail($id)
 * @method static create(array $array)
 */
class AppealType extends Model
{
    use HasFactory;

    protected $guarded = [];
}
