<?php

namespace Goodoneuz\PayUz\Http\Classes\Payme;

class Request
{
    /** @var array decoded request payload */
    public $payload;

    /** @var int id of the request */
    public $id;

    /** @var string method name, such as <em>CreateTransaction</em> */
    public $method;

    /** @var array request parameters, such as <em>amount</em>, <em>account</em> */
    public $params;

    /** @var int amount value in coins */
    public $amount;

    public $response;
    /**
     * Request constructor.
     * Parses request payload and populates properties with values.
     */
    public function __construct($response)
    {
        $this->response = $response;
        $request_body  = file_get_contents('php://input');

        if(env('APP_ENV') == 'testing')
            $request_body = request()->all()['request'];
    
        $this->payload = json_decode($request_body, true);
        if (!$this->payload) {
            $this->response->error(Response::ERROR_INVALID_JSON_RPC_OBJECT,'Invalid JSON-RPC object.');
        }

        // populate request object with data
        $this->id     = isset($this->payload['id']) ? 1 * $this->payload['id'] : null;
        $this->method = isset($this->payload['method']) ? trim($this->payload['method']) : null;
        $this->params = isset($this->payload['params']) ? $this->payload['params'] : [];
        $this->amount = isset($this->payload['params']['amount']) ? 1 * $this->payload['params']['amount'] : null;

        // add request id into params too
        $this->params['request_id'] = $this->id;
    }

    public function account($param)
    {
        return isset($this->params['account'], $this->params['account'][$param]) ? $this->params['account'][$param] : null;
    }

}
