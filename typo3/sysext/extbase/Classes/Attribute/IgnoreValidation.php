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

namespace TYPO3\CMS\Extbase\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class IgnoreValidation
{
    /**
     * @var non-empty-string|null
     */
    public readonly ?string $argumentName;

    /**
     * @param non-empty-string|array{value?: non-empty-string, argumentName?: non-empty-string}|null $argumentName
     */
    public function __construct(
        // @todo Convert to ?string and use CPP with TYPO3 v15.0
        string|array|null $argumentName = null,
    ) {
        // @todo Remove with TYPO3 v15.0
        if (is_array($argumentName)) {
            trigger_error(
                'Passing an array of configuration values to Extbase attributes will be removed in TYPO3 v15.0. ' .
                'Use explicit constructor parameters instead.',
                E_USER_DEPRECATED,
            );

            $values = $argumentName;

            $this->argumentName = $values['value'] ?? $values['argumentName'] ?? null;
        } else {
            $this->argumentName = $argumentName;
        }
    }
}
