<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'voucher_id',
        'unique_code',
        'provider',
        'provider_transaction_id',
        'gross_amount',
        'transaction_status',
        'payment_type',
        'payload',
        'proof_path',
        'proof_hash',
        'proof_uploaded_at',
        'rejection_reason',
        'customer_notes',
        'verified_by',
        'verified_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'gross_amount' => 'integer',
            'unique_code' => 'integer',
            'payload' => 'array',
            'proof_uploaded_at' => 'datetime',
            'verified_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function verificationLogs()
    {
        return $this->hasMany(PaymentVerificationLog::class)->latest();
    }
}
