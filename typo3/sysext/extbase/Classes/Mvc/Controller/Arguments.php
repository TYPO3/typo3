<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Extbase\Mvc\Controller;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException;

/**
 * A composite of controller arguments
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class Arguments extends \ArrayObject
{
    protected array $argumentNames = [];
    protected array $argumentShortNames = [];

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
     * @throws \InvalidArgumentException if the argument is not a valid Controller Argument object
     */
    public function offsetSet(mixed $offset, mixed $value): void
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
     * @throws \InvalidArgumentException if the argument is not a valid Controller Argument object
     */
    public function append(mixed $value): void
    {
        if (!$value instanceof Argument) {
            throw new \InvalidArgumentException('Controller arguments must be valid TYPO3\\CMS\\Extbase\\Mvc\\Controller\\Argument objects.', 1187953787);
        }
        $this->offsetSet(null, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        $translatedOffset = $this->translateToLongArgumentName($offset);
        parent::offsetUnset($translatedOffset);
        unset($this->argumentNames[$translatedOffset]);
        if ($offset != $translatedOffset) {
            unset($this->argumentShortNames[$offset]);
        }
    }

    public function offsetExists(mixed $offset): bool
    {
        $translatedOffset = $this->translateToLongArgumentName($offset);
        return parent::offsetExists($translatedOffset);
    }

    /**
     * Returns the value at the specified index
     *
     * @throws NoSuchArgumentException if the argument does not exist
     */
    public function offsetGet(mixed $offset): Argument
    {
        $translatedOffset = $this->translateToLongArgumentName($offset);
        if ($translatedOffset === '') {
            throw new NoSuchArgumentException('The argument "' . $offset . '" does not exist.', 1216909923);
        }
        return parent::offsetGet($translatedOffset);
    }

    /**
     * Creates, adds and returns a new controller argument to this composite object.
     * If an argument with the same name exists already, it will be replaced by the
     * new argument object.
     */
    public function addNewArgument(string $name, string $dataType = 'Text', bool $isRequired = false, mixed $defaultValue = null): Argument
    {
        $argument = GeneralUtility::makeInstance(Argument::class, $name, $dataType);
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
     */
    public function addArgument(Argument $argument): void
    {
        $this->offsetSet(null, $argument);
    }

    /**
     * Returns an argument specified by name
     *
     * @throws NoSuchArgumentException
     */
    public function getArgument(string $argumentName): Argument
    {
        if (!$this->offsetExists($argumentName)) {
            throw new NoSuchArgumentException('An argument "' . $argumentName . '" does not exist.', 1195815178);
        }
        return $this->offsetGet($argumentName);
    }

    /**
     * Checks if an argument with the specified name exists
     *
     * @see offsetExists()
     */
    public function hasArgument(string $argumentName): bool
    {
        return $this->offsetExists($argumentName);
    }

    /**
     * Returns the names of all arguments contained in this object
     */
    public function getArgumentNames(): array
    {
        return array_keys($this->argumentNames);
    }

    /**
     * Returns the short names of all arguments contained in this object that have one.
     */
    public function getArgumentShortNames(): array
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
     * @throws \LogicException
     */
    public function __call(string $methodName, array $arguments): void
    {
        if (!str_starts_with($methodName, 'set')) {
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
     */
    protected function translateToLongArgumentName(string $argumentName): string
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
    public function removeAll(): void
    {
        foreach ($this->argumentNames as $argumentName => $booleanValue) {
            parent::offsetUnset($argumentName);
        }
        $this->argumentNames = [];
    }

    public function validate(): Result
    {
        $results = new Result();
        /** @var Argument $argument */
        foreach ($this as $argument) {
            $argumentValidationResults = $argument->validate();
            $results->forProperty($argument->getName())->merge($argumentValidationResults);
        }
        return $results;
    }
}
