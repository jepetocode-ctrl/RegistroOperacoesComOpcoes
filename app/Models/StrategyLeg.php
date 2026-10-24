<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class StrategyLeg extends Model
{
    protected $table = 'strategy_legs';

    protected $fillable = [
        'strategy_id',
        'position',
        'option_contract_id',
        'instrument_type',
        'option_type',
        'strike_price',
        'expiration_date',
        'side',
        'quantity',
        'premium',
        'contract_multiplier',
        'costs',
    ];

    protected $casts = [
        'strike_price' => 'float',
        'premium' => 'float',
        'costs' => 'float',
        'quantity' => 'int',
        'contract_multiplier' => 'int',
        'position' => 'int',
    ];
}
