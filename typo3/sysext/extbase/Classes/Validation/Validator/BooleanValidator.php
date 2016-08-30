<?php
namespace TYPO3\CMS\Extbase\Validation\Validator;

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
 * Validator for boolean values
 */
class BooleanValidator extends AbstractValidator
{
    /**
     * @var array
     */
    protected $supportedOptions = [
        // The default is set to NULL here, because we need to be backward compatible here, because this
        // BooleanValidator is called automatically on boolean action arguments. If we would set it to TRUE,
        // every FALSE value for an action argument would break.
        // @todo with next patches: deprecate this BooleanValidator and introduce a BooleanValueValidator, like
        // in Flow, which won't be called on boolean action arguments.
        'is' => [null, 'Boolean value', 'boolean|string|integer']
    ];

    /**
     * Check if $value matches the expectation given to the validator.
     * If it does not match, the function adds an error to the result.
     *
     * Also testing for '1' (true), '0' and '' (false) because casting varies between
     * tests and actual usage. This makes the validator loose but still keeping functionality.
     *
     * @param mixed $value The value that should be validated
     * @return void
     */
    public function isValid($value)
    {
        // see comment above, check if expectation is NULL, then nothing to do!
        if ($this->options['is'] === null) {
            return;
        }
        switch (strtolower((string)$this->options['is'])) {
            case 'true':
            case '1':
                $expectation = true;
                break;
            case 'false':
            case '':
            case '0':
                $expectation = false;
                break;
            default:
                $this->addError('The given expectation is not valid.', 1361959227);
                return;
        }

        if ($value !== $expectation) {
            if (!is_bool($value)) {
                $this->addError($this->translateErrorMessage(
                    'validator.boolean.nottrue',
                    'extbase'
                ), 1361959230);
            } else {
                if ($expectation) {
                    $this->addError($this->translateErrorMessage(
                        'validator.boolean.nottrue',
                        'extbase'
                    ), 1361959228);
                } else {
                    $this->addError($this->translateErrorMessage(
                        'validator.boolean.notfalse',
                        'extbase'
                    ), 1361959229);
                }
            }
        }
    }
}
