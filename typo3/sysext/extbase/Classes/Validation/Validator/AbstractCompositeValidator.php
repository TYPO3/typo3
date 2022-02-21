<?php

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

namespace TYPO3\CMS\Extbase\Validation\Validator;

use TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException;
use TYPO3\CMS\Extbase\Validation\Exception\NoSuchValidatorException;

/**
 * An abstract composite validator consisting of other validators
 */
abstract class AbstractCompositeValidator implements ObjectValidatorInterface, \Countable
{
    /**
     * This contains the supported options, their default values and descriptions.
     *
     * @var array
     */
    protected $supportedOptions = [];

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var \SplObjectStorage
     */
    protected $validators;

    /**
     * @var \SplObjectStorage
     */
    protected $validatedInstancesContainer;

    /**
     * Constructs the composite validator and sets validation options
     *
     * @param array $options Options for the validator
     * @throws \TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException
     * @todo: __construct() will vanish in v12, this abstract will implement setOptions() to set and initialize default options.
     */
    public function __construct(array $options = [])
    {
        $this->initializeDefaultOptions($options);
    }

    /**
     * Adds a new validator to the conjunction.
     *
     * @param \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface $validator The validator that should be added
     */
    public function addValidator(ValidatorInterface $validator)
    {
        if ($validator instanceof ObjectValidatorInterface) {
            // @todo: provide bugfix as soon as it is fixed in TYPO3.Flow (https://forge.typo3.org/issues/48093)
            $validator->setValidatedInstancesContainer = $this->validatedInstancesContainer;
        }
        $this->validators->attach($validator);
    }

    /**
     * Removes the specified validator.
     *
     * @param \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface $validator The validator to remove
     * @throws \TYPO3\CMS\Extbase\Validation\Exception\NoSuchValidatorException
     */
    public function removeValidator(ValidatorInterface $validator)
    {
        if (!$this->validators->contains($validator)) {
            throw new NoSuchValidatorException('Cannot remove validator because its not in the conjunction.', 1207020177);
        }
        $this->validators->detach($validator);
    }

    /**
     * Returns the number of validators contained in this conjunction.
     *
     * @return int The number of validators
     * @todo Set to return type int as breaking change in v12.
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->validators);
    }

    /**
     * Returns the child validators of this Composite Validator
     *
     * @return \SplObjectStorage
     */
    public function getValidators()
    {
        return $this->validators;
    }

    /**
     * Returns the options for this validator
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Allows to set a container to keep track of validated instances.
     *
     * @param \SplObjectStorage $validatedInstancesContainer A container to keep track of validated instances
     */
    public function setValidatedInstancesContainer(\SplObjectStorage $validatedInstancesContainer)
    {
        $this->validatedInstancesContainer = $validatedInstancesContainer;
    }

    /**
     * Initialize default options.
     * @throws InvalidValidationOptionsException
     */
    protected function initializeDefaultOptions(array $options): void
    {
        // check for options given but not supported
        if (($unsupportedOptions = array_diff_key($options, $this->supportedOptions)) !== []) {
            throw new InvalidValidationOptionsException('Unsupported validation option(s) found: ' . implode(', ', array_keys($unsupportedOptions)), 1339079804);
        }
        // check for required options being set
        array_walk(
            $this->supportedOptions,
            static function ($supportedOptionData, $supportedOptionName, $options) {
                if (isset($supportedOptionData[3]) && !array_key_exists($supportedOptionName, $options)) {
                    throw new InvalidValidationOptionsException('Required validation option not set: ' . $supportedOptionName, 1339163922);
                }
            },
            $options
        );
        // merge with default values
        $this->options = array_merge(
            array_map(
                static function ($value) {
                    return $value[0];
                },
                $this->supportedOptions
            ),
            $options
        );
        $this->validators = new \SplObjectStorage();
    }
}
