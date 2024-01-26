<?php

namespace Goodoneuz\PayUz\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentSystemParam extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'payment_system_id',
        'label',
        'name',
        'value',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function payment_system(){
        return $this->hasOne(PaymentSystem::class);
    }
}
