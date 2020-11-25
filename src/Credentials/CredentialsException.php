<?php

declare(strict_types=1);

namespace LukeTowers\ShopifyPHP\Credentials;

use LukeTowers\ShopifyPHP\ShopifyExceptionInterface;

class CredentialsException extends \InvalidArgumentException implements ShopifyExceptionInterface
{

}
