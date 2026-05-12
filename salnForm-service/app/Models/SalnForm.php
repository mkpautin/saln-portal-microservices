<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalnForm extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'compliance_type',
        'compliance_date',
        'compliance_year',
        'declarant',
        'spouse',
        'filing_type',
        'additional_spouses',
        'children',
        'real_properties',
        'personal_properties',
        'business_interests',
        'relatives_in_government_service',
        'liabilities',
        'total_assets',
        'total_liabilities',
        'net_worth',
    ];

    protected function casts(): array
    {
        return [
            'compliance_date' => 'date',
            'compliance_year' => 'integer',
            'declarant' => 'array',
            'spouse' => 'array',
            'additional_spouses' => 'array',
            'children' => 'array',
            'real_properties' => 'array',
            'personal_properties' => 'array',
            'business_interests' => 'array',
            'relatives_in_government_service' => 'array',
            'liabilities' => 'array',
            'total_assets' => 'decimal:2',
            'total_liabilities' => 'decimal:2',
            'net_worth' => 'decimal:2',
        ];
    }
}
