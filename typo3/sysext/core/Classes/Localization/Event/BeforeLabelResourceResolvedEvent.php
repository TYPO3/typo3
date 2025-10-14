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

namespace TYPO3\CMS\Core\Localization\Event;

/**
 * Event that is fired after a file name has been mapped to a translation domain for all files
 * of a package.
 *
 * E.g. "vendor/myext.messages" is mapped to "EXT:myext/Resources/Private/Language/locallang.xlf"
 *
 * If you want this file to be a different location, e.g. for historical reasons, you can do this here.
 */
final class BeforeLabelResourceResolvedEvent
{
    public function __construct(
        public readonly string $packageKey,
        public array $domains
    ) {}
}
