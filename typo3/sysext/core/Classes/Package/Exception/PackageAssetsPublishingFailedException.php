<?php

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

namespace TYPO3\CMS\Core\Package\Exception;

use TYPO3\CMS\Core\Package\Exception;

class PackageAssetsPublishingFailedException extends Exception
{
    public function __construct(
        public readonly string $publishingStrategy,
        public readonly ?string $packageName = null,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(sprintf('Asset publishing by "%s" failed', $publishingStrategy), $code, $previous);
    }
}
