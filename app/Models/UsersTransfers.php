<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @method static where(string $string, $utid)
 */
class UsersTransfers extends Model
{
    use HasFactory;
    protected $connection = 'mysql_milliy';

    protected $table = 'users_transfers';
}
