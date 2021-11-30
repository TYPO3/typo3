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

namespace TYPO3\CMS\Core\Resource\Service;

use TYPO3\CMS\Core\Resource\FileInterface;

/**
 * Resources can contain configurations: For example to define image dimensions or
 * image masking. Resource configurations form part of the object identity and are
 * used to create an object signature that itself is used to cache objects but NOT
 * to reconstruct them. To prevent objects to be reconstructed they MUST NOT be
 * serialized. This is why an object signature is obtained by serializing its array
 * rather than serializing it directly. But attention...as well the objects array
 * MUST NOT contain resource objects which could be the case when a configuration
 * defines image masking. Here this service comes into play: it serializes any
 * object configuration, as well those containing resources.
 */
class ConfigurationService
{
    public function serialize(array $configuration): string
    {
        return serialize($this->makeSerializable($configuration));
    }

    /**
     * Recursively substitute file objects with their array representation.
     */
    protected function makeSerializable(array $configuration): array
    {
        return array_map(function ($value) {
            if (is_array($value)) {
                return $this->makeSerializable($value);
            }
            if ($value instanceof FileInterface) {
                return $value->toArray();
            }
            return $value;
        }, $configuration);
    }
}
