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
        // Constant-time compares to avoid timing / type-juggling auth bypass.
        if (!hash_equals((string)$this->config['login'], (string)($this->acc['login'] ?? '')) ||
            !hash_equals((string)$this->config['password'], (string)($this->acc['password'] ?? '')) )
            throw new OsonException(OsonException::ERROR_AUTHORIZATION,[]);
    }
}