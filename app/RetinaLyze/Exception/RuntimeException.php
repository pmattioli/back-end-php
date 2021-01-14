<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Exception;

use RuntimeException as StandardRuntimeException;
/**
 * Class RuntimeException.
 *
 *
 * @package RetinaLyze\Exception
 * @author  mom
 */
class RuntimeException extends StandardRuntimeException implements ExceptionInterface
{
    use ModuleExceptionTrait;
}