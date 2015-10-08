<?php
namespace TYPO3\CMS\Fluid\Core\ViewHelper;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Argument definition of each view helper argument
 */
class ArgumentDefinition
{
    /**
     * Name of argument
     *
     * @var string
     */
    protected $name;

    /**
     * Type of argument
     *
     * @var string
     */
    protected $type;

    /**
     * Description of argument
     *
     * @var string
     */
    protected $description;

    /**
     * Is argument required?
     *
     * @var bool
     */
    protected $required = false;

    /**
     * Default value for argument
     *
     * @var mixed
     */
    protected $defaultValue = null;

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
     * Get the name of the argument
     *
     * @return string Name of argument
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the type of the argument
     *
     * @return string Type of argument
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get the description of the argument
     *
     * @return string Description of argument
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Get the optionality of the argument
     *
     * @return bool TRUE if argument is optional
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * Get the default value, if set
     *
     * @return mixed Default value
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
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
