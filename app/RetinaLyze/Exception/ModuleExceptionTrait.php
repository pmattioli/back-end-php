<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Exception;

/**
 * This trait adds module-specific information to the exceptions if available.
 *
 * @author mom
 */
trait ModuleExceptionTrait
{
    /**
     * The name of the module that has thrown the exception.
     *
     *
     * @var string
     */
    protected $_bnModule;
    /**
     * Set the name of the module that has thrown the exception.
     *
     *
     * @param string $module Name of the module that has thrown the exception.
     */
    public function setModule($module)
    {
        $this->_bnModule = (string)$module;
    }
    /**
     * Get the name of the module that has thrown the exception.
     *
     *
     * @return string Name of the module that has thrown the exception.
     */
    public function getModule()
    {
        return (string)$this->_bnModule;
    }
}