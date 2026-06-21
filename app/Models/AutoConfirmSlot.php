<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutoConfirmSlot extends Model
{
    use HasFactory;

    const DAYS = [
        '0' => 'Domenica',
        '1' => 'Lunedì',
        '2' => 'Martedì',
        '3' => 'Mercoledì',
        '4' => 'Giovedì',
        '5' => 'Venerdì',
        '6' => 'Sabato',
    ];

    protected $fillable = [
        'day_of_week',
        'time_from',
        'time_to',
    ];
}
