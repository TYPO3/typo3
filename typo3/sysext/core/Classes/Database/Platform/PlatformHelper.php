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

namespace TYPO3\CMS\Core\Database\Platform;

use Doctrine\DBAL\Platforms\AbstractPlatform as DoctrineAbstractPlatform;

/**
 * @internal not part of public core API.
 */
final class PlatformHelper
{
    /**
     * Doctrine DBAL 4 removed the `getIdentifierQuoteCharacter()` method from the platform classes and suggest to
     * use `Platform::quoteIdentifier()` instead. As this invoke the need to provide a fake identifier and extract
     * the character, this helper method is used throughout the core.
     *
     * @see https://github.com/doctrine/dbal/blob/4.0.x/UPGRADE.md#bc-break-removed-abstractplatform-methods-exposing-quote-characters
     * @see https://github.com/doctrine/dbal/blob/3.7.x/UPGRADE.md#deprecated-abstractplatform-methods-exposing-quote-characters
     *
     * @param DoctrineAbstractPlatform $platform
     * @return string
     *
     * @internal Used in capsuled code and should not be needed to called by extension code. Not part of public API.
     */
    public function getIdentifierQuoteCharacter(DoctrineAbstractPlatform $platform): string
    {
        // Note: Albeit not used yet, $platform is handed over from usages to allow easier adjustments if required.
        return $platform->quoteSingleIdentifier('fake')[0];
    }
}
