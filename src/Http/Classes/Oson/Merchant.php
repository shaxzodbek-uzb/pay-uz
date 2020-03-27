<?php
/**
 * Created by PhpStorm.
 * User: Shaxzodbek
 * Date: 25.05.2018
 * Time: 9:57
 */

namespace App\Http\Classes\Oson;


class Merchant
{
    public $config;
    public $acc;
    public function __construct($config,$acc)
    {
        $this->acc = $acc;
        $this->config = $config;
    }
    public function checkAuth(){
        if ($this->config['login'] != $this->acc['login'] || $this->config['password'] != $this->acc['password'] )
            throw new OsonException(OsonException::ERROR_AUTHORIZATION,[]);
    }
}