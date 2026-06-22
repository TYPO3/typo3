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

namespace TYPO3\CMS\Extensionmanager\Property\TypeConverter;

use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter;
use TYPO3\CMS\Extensionmanager\Domain\Model\PackageIdentifier;

/**
 * Maps an array with keys 'packageKey', 'version' and 'remote'
 * to a {@see PackageIdentifier} value object, enabling it to be used directly
 * as a typed Extbase action parameter.
 *
 * @internal This class is a specific converter implementation and is not part of the Public TYPO3 API.
 */
final class PackageIdentifierTypeConverter extends AbstractTypeConverter
{
    public function convertFrom(
        $source,
        string $targetType,
        array $convertedChildProperties = [],
        ?PropertyMappingConfigurationInterface $configuration = null
    ): PackageIdentifier|Error {
        if (empty($source['packageKey'])) {
            return new Error('The "packageKey" argument must not be empty.', 1750687200);
        }
        if (empty($source['version'])) {
            return new Error('The "version" argument must not be empty.', 1750687201);
        }
        if (empty($source['remote'])) {
            return new Error('The "remote" argument must not be empty.', 1750687202);
        }
        return new PackageIdentifier(
            $source['packageKey'],
            $source['version'],
            $source['remote'],
        );
    }
}
