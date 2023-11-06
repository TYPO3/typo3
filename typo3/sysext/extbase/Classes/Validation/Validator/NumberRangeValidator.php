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
 * Validator for general numbers
 */
final class NumberRangeValidator extends AbstractValidator
{
    protected string $notValidMessage = 'LLL:EXT:extbase/Resources/Private/Language/locallang.xlf:validator.numberrange.notvalid';
    protected string $notInRangeMessage = 'LLL:EXT:extbase/Resources/Private/Language/locallang.xlf:validator.numberrange.range';

    protected array $translationOptions = ['notValidMessage', 'notInRangeMessage'];

    /**
     * @var array
     */
    protected $supportedOptions = [
        'minimum' => [0, 'The minimum value to accept', 'integer'],
        'maximum' => [PHP_INT_MAX, 'The maximum value to accept', 'integer'],
        'notValidMessage' => [null, 'Translation key or message for non valid value', 'string'],
        'notInRangeMessage' => [null, 'Translation key or message for value not in range', 'string'],
    ];

    /**
     * The given value is valid if it is a number in the specified range.
     */
    public function isValid(mixed $value): void
    {
        if (!is_numeric($value)) {
            $this->addError($this->translateErrorMessage($this->notValidMessage), 1221563685);
            return;
        }

        $minimum = $this->options['minimum'];
        $maximum = $this->options['maximum'];

        if ($minimum > $maximum) {
            $x = $minimum;
            $minimum = $maximum;
            $maximum = $x;
        }
        if ($value < $minimum || $value > $maximum) {
            $this->addError($this->translateErrorMessage(
                $this->notInRangeMessage,
                '',
                [
                    $minimum,
                    $maximum,
                ]
            ), 1221561046, [$minimum, $maximum]);
        }
    }
}
