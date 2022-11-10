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

namespace TYPO3\CMS\Core\Routing\Aspect;

/**
 * Provides fallback values for unresolved values during processing mappers.
 */
trait UnresolvedValueTrait
{
    /**
     * @var array{fallbackValue?: ?scalar}
     */
    protected $settings;

    public function hasFallbackValue(): bool
    {
        return array_key_exists('fallbackValue', $this->settings);
    }

    public function getFallbackValue(): ?string
    {
        if (!$this->hasFallbackValue()) {
            throw new \LogicException('Property fallbackValue must be defined', 1668084601);
        }
        /** @var mixed $fallbackValue */
        $fallbackValue = $this->settings['fallbackValue'];
        if (is_string($fallbackValue) || is_null($fallbackValue)) {
            return $fallbackValue;
        }
        return (string)$fallbackValue;
    }
}
