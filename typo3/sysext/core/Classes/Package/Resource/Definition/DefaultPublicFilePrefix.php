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

namespace TYPO3\CMS\Core\Package\Resource\Definition;

use TYPO3\CMS\Core\Package\PackageInterface;

/**
 * @internal This is subject to change during v14 development. Do not use.
 */
final class DefaultPublicFilePrefix implements DynamicPublicPrefixInterface
{
    public function calculatePrefix(PackageInterface $package, PublicResourceDefinition $definition): string
    {
        return new DefaultPublicFolderPrefix()->calculatePrefix($package, $definition) . '/' . basename($definition->getRelativePath());
    }
}
