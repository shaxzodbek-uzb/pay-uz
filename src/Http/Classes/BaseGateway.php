<?php

namespace Goodoneuz\PayUz\Http\Classes;

abstract class BaseGateway
{
	const CUSTOM_FORM = '';
	public $hasDescription = false;
	public function setDescription($hasDescription)
	{
		$this->hasDescription = $hasDescription;
		return $this;
	}
}