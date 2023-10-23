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

    protected array $options = [];
    protected \SplObjectStorage $validators;

    /**
     * @todo: Properties {@see self::$validators} and {@see self::$validatedInstancesContainer} are not properly
     *        initialized in this class because introducing a constructor is considered possibly breaking.
     *        However, it is unlikely that anyone extends this validator. Hence, it should be marked @internal in
     *        v13 and get a constructor. Both framework validators that extend this class, {@see ConjunctionValidator}
     *        and {@see DisjunctionValidator} do have a constructor. Their constructors need to be removed again then.
     */
    protected \SplObjectStorage $validatedInstancesContainer;

    public function setOptions(array $options): void
    {
        $this->initializeDefaultOptions($options);
    }

    /**
     * Adds a new validator to the composition.
     */
    public function addValidator(ValidatorInterface $validator): void
    {
        $this->validators->attach($validator);
    }

    /**
     * Removes the specified validator.
     *
     * @throws NoSuchValidatorException
     */
    public function removeValidator(ValidatorInterface $validator): void
    {
        if (!$this->validators->contains($validator)) {
            throw new NoSuchValidatorException('Cannot remove validator because its not in the conjunction.', 1207020177);
        }
        $this->validators->detach($validator);
    }

    /**
     * Returns the number of validators contained in this composition.
     */
    public function count(): int
    {
        return count($this->validators);
    }

    /**
     * Returns the child validators of this Composite Validator
     */
    public function getValidators(): \SplObjectStorage
    {
        return $this->validators;
    }

    /**
     * Returns the options for this validator
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Allows to set a container to keep track of validated instances.
     */
    public function setValidatedInstancesContainer(\SplObjectStorage $validatedInstancesContainer): void
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
            static function (array $supportedOptionData, string $supportedOptionName, array $options): void {
                if (isset($supportedOptionData[3]) && !array_key_exists($supportedOptionName, $options)) {
                    throw new InvalidValidationOptionsException('Required validation option not set: ' . $supportedOptionName, 1339163922);
                }
            },
            $options
        );
        // merge with default values
        $this->options = array_merge(
            array_map(
                static fn(array $value): mixed => $value[0],
                $this->supportedOptions
            ),
            $options
        );
        $this->validators = new \SplObjectStorage();
    }
}
