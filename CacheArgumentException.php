<?php

use Psr\SimpleCache\InvalidArgumentException as InvalidArgumentExceptionInterface;

class CacheArgumentException extends InvalidArgumentException implements Psr\SimpleCache\InvalidArgumentException
{
}