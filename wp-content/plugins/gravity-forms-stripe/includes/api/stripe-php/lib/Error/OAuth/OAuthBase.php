<?php

namespace PPP\Stripe\Error\OAuth;

class OAuthBase extends \PPP\Stripe\Error\Base
{
    public function __construct(
        $code,
        $description,
        $httpStatus = null,
        $httpBody = null,
        $jsonBody = null,
        $httpHeaders = null
    ) {
        parent::__construct($description, $httpStatus, $httpBody, $jsonBody, $httpHeaders);
        $this->code = $code;
    }

    public function getErrorCode()
    {
        return $this->code;
    }
}
