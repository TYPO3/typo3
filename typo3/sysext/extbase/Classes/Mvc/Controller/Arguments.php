<?php
namespace TYPO3\CMS\Extbase\Mvc\Controller;

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
 * A composite of controller arguments
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class Arguments extends \ArrayObject
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var array Names of the arguments contained by this object
     */
    protected $argumentNames = [];

    /**
     * @var array
     */
    protected $argumentShortNames = [];

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Constructor. If this one is removed, reflection breaks.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Adds or replaces the argument specified by $value. The argument's name is taken from the
     * argument object itself, therefore the $offset does not have any meaning in this context.
     *
     * @param mixed $offset Offset - not used here
     * @param mixed $value The argument
     * @throws \InvalidArgumentException if the argument is not a valid Controller Argument object
     */
    public function offsetSet($offset, $value)
    {
        if (!$value instanceof Argument) {
            throw new \InvalidArgumentException('Controller arguments must be valid TYPO3\\CMS\\Extbase\\Mvc\\Controller\\Argument objects.', 1187953786);
        }
        $argumentName = $value->getName();
        parent::offsetSet($argumentName, $value);
        $this->argumentNames[$argumentName] = true;
    }

    /**
     * Sets an argument, aliased to offsetSet()
     *
     * @param mixed $value The value
     * @throws \InvalidArgumentException if the argument is not a valid Controller Argument object
     */
    public function append($value)
    {
        if (!$value instanceof Argument) {
            throw new \InvalidArgumentException('Controller arguments must be valid TYPO3\\CMS\\Extbase\\Mvc\\Controller\\Argument objects.', 1187953787);
        }
        $this->offsetSet(null, $value);
    }

    /**
     * Unsets an argument
     *
     * @param mixed $offset Offset
     */
    public function offsetUnset($offset)
    {
        $translatedOffset = $this->translateToLongArgumentName($offset);
        parent::offsetUnset($translatedOffset);
        unset($this->argumentNames[$translatedOffset]);
        if ($offset != $translatedOffset) {
            unset($this->argumentShortNames[$offset]);
        }
    }

    /**
     * Returns whether the requested index exists
     *
     * @param mixed $offset Offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        $translatedOffset = $this->translateToLongArgumentName($offset);
        return parent::offsetExists($translatedOffset);
    }

    /**
     * Returns the value at the specified index
     *
     * @param mixed $offset Offset
     * @return Argument The requested argument object
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException if the argument does not exist
     */
    public function offsetGet($offset)
    {
        $translatedOffset = $this->translateToLongArgumentName($offset);
        if ($translatedOffset === '') {
            throw new \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException('The argument "' . $offset . '" does not exist.', 1216909923);
        }
        return parent::offsetGet($translatedOffset);
    }

    /**
     * Creates, adds and returns a new controller argument to this composite object.
     * If an argument with the same name exists already, it will be replaced by the
     * new argument object.
     *
     * @param string $name Name of the argument
     * @param string $dataType Name of one of the built-in data types
     * @param bool $isRequired TRUE if this argument should be marked as required
     * @param mixed $defaultValue Default value of the argument. Only makes sense if $isRequired==FALSE
     * @return Argument The new argument
     */
    public function addNewArgument($name, $dataType = 'Text', $isRequired = false, $defaultValue = null)
    {
        /** @var Argument $argument */
        $argument = $this->objectManager->get(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class, $name, $dataType);
        $argument->setRequired($isRequired);
        $argument->setDefaultValue($defaultValue);
        $this->addArgument($argument);
        return $argument;
    }

    /**
     * Adds the specified controller argument to this composite object.
     * If an argument with the same name exists already, it will be replaced by the
     * new argument object.
     *
     * Note that the argument will be cloned, not referenced.
     *
     * @param Argument $argument The argument to add
     */
    public function addArgument(Argument $argument)
    {
        $this->offsetSet(null, $argument);
    }

    /**
     * Returns an argument specified by name
     *
     * @param string $argumentName Name of the argument to retrieve
     * @return Argument
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     */
    public function getArgument($argumentName)
    {
        if (!$this->offsetExists($argumentName)) {
            throw new \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException('An argument "' . $argumentName . '" does not exist.', 1195815178);
        }
        return $this->offsetGet($argumentName);
    }

    /**
     * Checks if an argument with the specified name exists
     *
     * @param string $argumentName Name of the argument to check for
     * @return bool TRUE if such an argument exists, otherwise FALSE
     * @see offsetExists()
     */
    public function hasArgument($argumentName)
    {
        return $this->offsetExists($argumentName);
    }

    /**
     * Returns the names of all arguments contained in this object
     *
     * @return array Argument names
     */
    public function getArgumentNames()
    {
        return array_keys($this->argumentNames);
    }

    /**
     * Returns the short names of all arguments contained in this object that have one.
     *
     * @return array Argument short names
     */
    public function getArgumentShortNames()
    {
        $argumentShortNames = [];
        /** @var Argument $argument */
        foreach ($this as $argument) {
            $argumentShortNames[$argument->getShortName()] = true;
        }
        return array_keys($argumentShortNames);
    }

    /**
     * Magic setter method for the argument values. Each argument
     * value can be set by just calling the setArgumentName() method.
     *
     * @param string $methodName Name of the method
     * @param array $arguments Method arguments
     * @throws \LogicException
     */
    public function __call($methodName, array $arguments)
    {
        if (strpos($methodName, 'set') !== 0) {
            throw new \LogicException('Unknown method "' . $methodName . '".', 1210858451);
        }
        $firstLowerCaseArgumentName = $this->translateToLongArgumentName(strtolower($methodName[3]) . substr($methodName, 4));
        $firstUpperCaseArgumentName = $this->translateToLongArgumentName(ucfirst(substr($methodName, 3)));
        if (in_array($firstLowerCaseArgumentName, $this->getArgumentNames())) {
            $argument = parent::offsetGet($firstLowerCaseArgumentName);
            $argument->setValue($arguments[0]);
        } elseif (in_array($firstUpperCaseArgumentName, $this->getArgumentNames())) {
            $argument = parent::offsetGet($firstUpperCaseArgumentName);
            $argument->setValue($arguments[0]);
        }
    }

    /**
     * Translates a short argument name to its corresponding long name. If the
     * specified argument name is a real argument name already, it will be returned again.
     *
     * If an argument with the specified name or short name does not exist, an empty
     * string is returned.
     *
     * @param string $argumentName argument name
     * @return string long argument name or empty string
     */
    protected function translateToLongArgumentName($argumentName)
    {
        if (in_array($argumentName, $this->getArgumentNames())) {
            return $argumentName;
        }
        /** @var Argument $argument */
        foreach ($this as $argument) {
            if ($argumentName === $argument->getShortName()) {
                return $argument->getName();
            }
        }
        return '';
    }

    /**
     * Remove all arguments and resets this object
     */
    public function removeAll()
    {
        foreach ($this->argumentNames as $argumentName => $booleanValue) {
            parent::offsetUnset($argumentName);
        }
        $this->argumentNames = [];
    }

    /**
     * Get all property mapping / validation errors
     *
     * @return \TYPO3\CMS\Extbase\Error\Result
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    public function getValidationResults()
    {
        trigger_error(
            'Method ' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v10.0.',
            E_USER_DEPRECATED
        );

        return $this->validate();
    }

    /**
     * @return \TYPO3\CMS\Extbase\Error\Result
     */
    public function validate(): \TYPO3\CMS\Extbase\Error\Result
    {
        $results = new \TYPO3\CMS\Extbase\Error\Result();
        /** @var Argument $argument */
        foreach ($this as $argument) {
            $argumentValidationResults = $argument->validate();
            if ($argumentValidationResults === null) {
                continue;
            }
            $results->forProperty($argument->getName())->merge($argumentValidationResults);
        }
        return $results;
    }
}
