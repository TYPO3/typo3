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

/**
 * Validator for boolean values
 */
final class BooleanValidator extends AbstractValidator
{
    protected string $notTrueMessage = 'LLL:EXT:extbase/Resources/Private/Language/locallang.xlf:validator.boolean.nottrue';
    protected string $notFalseMessage = 'LLL:EXT:extbase/Resources/Private/Language/locallang.xlf:validator.boolean.notfalse';

    protected array $translationOptions = ['notTrueMessage', 'notFalseMessage'];

    /**
     * @var array
     */
    protected $supportedOptions = [
        // The default is set to NULL here, because we need to be backward compatible here, because this
        // BooleanValidator is called automatically on boolean action arguments. If we would set it to TRUE,
        // every FALSE value for an action argument would break.
        // @todo with next patches: deprecate this BooleanValidator and introduce a BooleanValueValidator, like
        // in Flow, which won't be called on boolean action arguments.
        'is' => [null, 'Boolean value', 'boolean|string|integer'],
        'notTrueMessage' => [null, 'Translation key or message for not true value', 'string'],
        'notFalseMessage' => [null, 'Translation key or message for not false value', 'string'],
    ];

    /**
     * Check if $value matches the expectation given to the validator.
     * If it does not match, the function adds an error to the result.
     *
     * Also testing for '1' (true), '0' and '' (false) because casting varies between
     * tests and actual usage. This makes the validator loose but still keeping functionality.
     */
    public function isValid(mixed $value): void
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
                $this->addError($this->translateErrorMessage($this->notTrueMessage), 1361959230);
            } else {
                if ($expectation) {
                    $this->addError($this->translateErrorMessage($this->notTrueMessage), 1361959228);
                } else {
                    $this->addError($this->translateErrorMessage($this->notFalseMessage), 1361959229);
                }
            }
        }
    }
}
