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
 * A validator which accepts any input.
 *
 * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
 */
class RawValidator extends AbstractValidator
{
    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        trigger_error(
            __CLASS__ . ' is deprecated and will be removed in TYPO3 v10.0.',
            E_USER_DEPRECATED
        );

        parent::__construct($options);
    }

    /**
     * This validator is always valid.
     *
     * @param mixed $value The value that should be validated (not used here)
     */
    public function isValid($value)
    {
    }
}
