<?php
namespace TYPO3\CMS\Fluid\Core\ViewHelper;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Argument definition of each view helper argument
 *
 * This subclass of ArgumentDefinition from Fluid has
 * one additional capability: defining that an argument
 * should be expected as a parameter for the render()
 * method - which means the ViewHelperInvoker will be
 * processing it a bit differently. Other than this it
 * is a normal Fluid ArgumentDefinition.
 */
class ArgumentDefinition extends \TYPO3Fluid\Fluid\Core\ViewHelper\ArgumentDefinition
{
    /**
     * TRUE if it is a method parameter
     *
     * @var bool
     */
    protected $isMethodParameter = false;

    /**
     * Constructor for this argument definition.
     *
     * @param string $name Name of argument
     * @param string $type Type of argument
     * @param string $description Description of argument
     * @param bool $required TRUE if argument is required
     * @param mixed $defaultValue Default value
     * @param bool $isMethodParameter TRUE if this argument is a method parameter
     */
    public function __construct($name, $type, $description, $required, $defaultValue = null, $isMethodParameter = false)
    {
        $this->name = $name;
        $this->type = $type;
        $this->description = $description;
        $this->required = $required;
        $this->defaultValue = $defaultValue;
        $this->isMethodParameter = $isMethodParameter;
    }

    /**
     * TRUE if it is a method parameter
     *
     * @return bool TRUE if it's a method parameter
     */
    public function isMethodParameter()
    {
        return $this->isMethodParameter;
    }
}
