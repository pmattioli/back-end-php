<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Exception;

use UnexpectedValueException as StandardUnexpectedValueException;
/**
 * Class UnexpectedValueException.
 *
 *
 * @package RetinaLyze\Exception
 * @author  mom
 */
class UnexpectedValueException extends StandardUnexpectedValueException implements ExceptionInterface
{
    use ModuleExceptionTrait;
}
