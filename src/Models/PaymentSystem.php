<?php
/**
 * Created by PhpStorm.
 * User: Azizbek Eshonaliyev
 * Date: 2/15/2019
 * Time: 8:05 PM
 */

namespace Goodoneuz\PayUz\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentSystem extends Model
{
    use SoftDeletes;

    /**
     *
     */
    const NOT_ACTIVE = 'not_active';

    /**
     *
     */
    const ACTIVE = 'active';

    /**
     * @var array
     */
    protected $dates    = [
        'deleted_at'
    ];

    /**
     * @var array
     */
    protected $fillable =[
        'name',
        'system',
        'merchant_id',
        'service_id',
        'secret_key',
        'merchant_user_id',
        'login',
        'password',
        'end_point_url',
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
}
