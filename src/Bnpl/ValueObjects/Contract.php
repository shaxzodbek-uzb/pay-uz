<?php

namespace Goodoneuz\PayUz\Bnpl\ValueObjects;

/**
 * A created installment contract (Uzum Nasiya `orders` response).
 *
 * NOTE the two identifiers: {@see contractId()} is used by confirm()/status(),
 * while {@see orderId()} (Nasiya's `order`) is the one cancel() needs. Open
 * {@see webviewPath()} for the buyer to sign/activate. Money is in tiyin.
 */
class Contract
{
    /** @var int id for confirm()/status() */
    protected $contractId;

    /** @var int id for cancel() (Nasiya `order`) */
    protected $orderId;

    /** @var int total payable, tiyin */
    protected $totalTiyin;

    /** @var int monthly payment, tiyin */
    protected $monthlyTiyin;

    /** @var string|null hosted signing/activation URL */
    protected $webviewPath;

    /** @var string|null unsigned act PDF URL */
    protected $actPdfUrl;

    /** @var array */
    protected $raw;

    /**
     * @param array $data already-tiyin money values
     */
    public function __construct(array $data = [])
    {
        $this->contractId   = isset($data['contract_id']) ? (int) $data['contract_id'] : 0;
        $this->orderId      = isset($data['order_id']) ? (int) $data['order_id'] : 0;
        $this->totalTiyin   = isset($data['total']) ? (int) $data['total'] : 0;
        $this->monthlyTiyin = isset($data['monthly']) ? (int) $data['monthly'] : 0;
        $this->webviewPath  = isset($data['webview_path']) && $data['webview_path'] !== '' ? (string) $data['webview_path'] : null;
        $this->actPdfUrl    = isset($data['act_pdf_url']) && $data['act_pdf_url'] !== '' ? (string) $data['act_pdf_url'] : null;
        $this->raw          = isset($data['raw']) ? (array) $data['raw'] : $data;
    }

    public function contractId()
    {
        return $this->contractId;
    }

    public function orderId()
    {
        return $this->orderId;
    }

    public function totalTiyin()
    {
        return $this->totalTiyin;
    }

    public function monthlyTiyin()
    {
        return $this->monthlyTiyin;
    }

    public function webviewPath()
    {
        return $this->webviewPath;
    }

    public function actPdfUrl()
    {
        return $this->actPdfUrl;
    }

    public function raw()
    {
        return $this->raw;
    }
}
