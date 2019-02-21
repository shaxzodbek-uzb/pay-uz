<?php

namespace Goodoneuz\PayUz\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 
 */

class Invoice extends Model
{
    use SoftDeletes;

    protected $dates    = [
        'deleted_at'
    ];

    const STATE_CREATED = 'created';
}
