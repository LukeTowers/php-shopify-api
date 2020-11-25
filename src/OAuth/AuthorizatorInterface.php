<?php

declare(strict_types=1);

namespace LukeTowers\ShopifyPHP\OAuth;

use LukeTowers\ShopifyPHP\Credentials\ShopDomain;

interface AuthorizatorInterface
{
    /**
     * @param AuthorizationRequest $request
     * @param string $nonce Use of the nonce is highly recommended and may be required in future
     * @param ShopDomain|null $shopDomain Optionally ensure that the request shop domain matches expected shop domain
     * @return AuthorizationResponse
     * @throws AuthorizationException
     */
    public function authorizeApplication(
        AuthorizationRequest $request,
        string $nonce = '',
        ShopDomain $shopDomain = null
    ): AuthorizationResponse;
}
