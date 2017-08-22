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
 * Validator for general numbers
 *
 * @api
 */
class NumberRangeValidator extends AbstractValidator
{
    /**
     * @var array
     */
    protected $supportedOptions = [
        'minimum' => [0, 'The minimum value to accept', 'integer'],
        'maximum' => [PHP_INT_MAX, 'The maximum value to accept', 'integer']
    ];

    /**
     * The given value is valid if it is a number in the specified range.
     *
     * @param mixed $value The value that should be validated
     * @api
     */
    public function isValid($value)
    {
        if (!is_numeric($value)) {
            $this->addError(
                $this->translateErrorMessage(
                    'validator.numberrange.notvalid',
                    'extbase'
                ),
                1221563685
            );
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
                'validator.numberrange.range',
                'extbase',
                [
                    $minimum,
                    $maximum
                ]
            ), 1221561046, [$minimum, $maximum]);
        }
    }
}
