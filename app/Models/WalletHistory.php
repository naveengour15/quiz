<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletHistory extends Model
{
    use HasFactory;
   
    protected $table = 'wallet_history';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'quiz_id',
        'referral_user_id',
        'survey_id',
        'old_wallet_balance',
        'amount',
        'new_wallet_balance',
        'type',
        'transaction_type',
        'reference_id',
        'remarks',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'old_wallet_balance' => 'decimal:2',
        'new_wallet_balance' => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
