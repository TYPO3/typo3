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

namespace TYPO3\CMS\Core\Schema\Field;

use TYPO3\CMS\Core\Utility\MathUtility;

final readonly class NumberFieldType extends AbstractFieldType
{
    public function getType(): string
    {
        return 'number';
    }

    public function isSearchable(): bool
    {
        return $this->getScale() === 0;
    }

    /**
     * @deprecated since TYPO3 v15.0, will be removed in TYPO3 v16.0.
     */
    public function getFormat(): string
    {
        trigger_error(
            'NumberFieldType->getFormat() is deprecated since TYPO3 v15.0 and will be removed in TYPO3 v16.0. Use getScale() instead.',
            E_USER_DEPRECATED
        );

        if ($this->getScale() === 0) {
            return 'integer';
        }
        return 'decimal';
    }

    /**
     * Get the number of decimal places of the number element.
     * 0 decimal places means, it is an integer.
     * More than 0 decimal places means, it is a float.
     * Maximum of 30 decimal places is possible.
     * Defaults to 0.
     */
    public function getScale(): int
    {
        return MathUtility::forceIntegerInRange(
            (int)($this->configuration['scale'] ?? 0),
            0,
            30
        );
    }

    public function getSoftReferenceKeys(): false
    {
        return false;
    }
}
