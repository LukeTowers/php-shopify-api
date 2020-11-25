<?php

declare(strict_types=1);

namespace LukeTowers\ShopifyPHP\OAuth;

use LukeTowers\ShopifyPHP\ShopifyExceptionInterface;

class ScopeException extends \InvalidArgumentException implements ShopifyExceptionInterface
{

}
