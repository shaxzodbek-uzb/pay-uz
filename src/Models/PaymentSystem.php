<?php
/**
 * Created by PhpStorm.
 * User: Azizbek Eshonaliyev
 * Date: 2/15/2019
 * Time: 8:05 PM
 */

namespace Goodoneuz\PayUz\Models;


use Illuminate\Database\Eloquent\Model;

class PaymentSystem extends Model
{

    const NOT_ACTIVE = 'not_active';
    const ACTIVE = 'active';

    const PAYME     = 'payme';
    const CLICK     = 'click';
    const UPAY      = 'upay';
    const UZCARD    = 'uzcard';
    const MBANK     = 'mbank';
    const OSON      = 'oson';
    const VISA      = 'visa';
    const AGR       = 'agr';
    const PAYNET    = 'paynet';
    const CASH      = 'cash';
    const TERMINAL  = 'terminal';
    const STRIPE    = 'stripe';
    /**
     * @var array
     */
    protected $fillable =[
        'name',
        'system',
        'status'
    ];
    /**
     * @param $query
     * @param null $status
     * @return mixed
     */
    public function scopeStatus($query, $status = null){
        return $query->where('status',($status) ? $status : self::ACTIVE);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function params(){
        return $this->hasMany(PaymentSystemParam::class,'system','system');
    }
}
