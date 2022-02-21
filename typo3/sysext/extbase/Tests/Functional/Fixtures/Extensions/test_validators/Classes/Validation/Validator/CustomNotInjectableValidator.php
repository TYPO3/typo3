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

namespace ExtbaseTeam\TestValidators\Validation\Validator;

use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

/**
 * Fixture to verify ObjectManager injects dependency and options are set.
 * @deprecated since v11, will be removed in v12.
 */
class CustomNotInjectableValidator extends AbstractValidator
{
    protected $supportedOptions = [
        'foo' => [0, 'foo length', 'integer'],
    ];

    /**
     * @param mixed $value
     */
    protected function isValid($value): void
    {
    }
}
