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

namespace TYPO3\CMS\Core\Html\Srcset;

/**
 * Represents a source candidate for the HTML srcset attribute
 */
abstract class SrcsetCandidate
{
    public const WIDTH_UNIT = 'w';
    public const DENSITY_UNIT = 'x';

    protected ?string $uri = null;

    /**
     * @param string   $descriptor      srcset width (like "300w") or density (like "2x") descriptor.
     * @param int|null $referenceWidth  Needs to be provided if $descriptor is a density descriptor
     *                                  (like "2x") to be able to resolve the relative image dimensions.
     *                                  The absolute $referenceWidth will be used as "1x" width.
     */
    public static function createFromDescriptor(string $descriptor, ?int $referenceWidth = null): SrcsetCandidate
    {
        $mode = substr($descriptor, -1);
        $value = substr($descriptor, 0, -1);
        if (is_numeric($value)) {
            if ($mode === static::DENSITY_UNIT) {
                // '1.5x'
                return new DensitySrcsetCandidate((float)$value, $referenceWidth);
            }
            if ($mode === static::WIDTH_UNIT) {
                // '200w'
                return new WidthSrcsetCandidate((int)$value);
            }
        }
        throw new \InvalidArgumentException(
            'Invalid srcset descriptor provided, must be a numeric value that ends with "w" or "x": ' . $descriptor,
            1774527269,
        );
    }

    abstract public function getDescriptor(): string;

    abstract public function getCalculatedWidth(): int;

    public function setUri(string $uri): static
    {
        $this->uri = $uri;
        return $this;
    }

    public function getUri(): ?string
    {
        return $this->uri;
    }

    /**
     * Ensures that the provided URI can be used safely in a srcset attribute
     */
    public function getSanitizedUri(): ?string
    {
        if ($this->uri === null) {
            return null;
        }

        return strtr($this->uri, [
            ' ' => '%20',
            ',' => '%2C',
        ]);
    }

    public function generateSrcset(): string
    {
        return $this->getSanitizedUri() . ' ' . $this->getDescriptor();
    }

    public function __toString()
    {
        return $this->generateSrcset();
    }
}
