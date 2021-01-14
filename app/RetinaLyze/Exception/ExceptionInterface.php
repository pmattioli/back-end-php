<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Exception;

/**
 * Interface ExceptionInterface.
 *
 *
 * @package RetinaLyze\Exception
 * @author mom
 */
interface ExceptionInterface
{
    /**
     * Get the name of the module that has thrown the exception.
     *
     *
     * @return string Name of the module that has thrown the exception.
     */
    public function getModule();
    /**
     * Set the name of the module that has thrown the exception.
     *
     *
     * @param string $module Name of the module that has thrown the exception.
     */
    public function setModule($module);
}