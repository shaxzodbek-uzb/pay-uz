<?php

namespace Goodoneuz\PayUz\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

    const NOT_ACTIVE = 0;
    const ACTIVE = 1;

    protected $dates    = [
        'deleted_at'
    ];
}