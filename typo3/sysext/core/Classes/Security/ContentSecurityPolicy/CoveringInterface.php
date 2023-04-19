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

namespace TYPO3\CMS\Core\Security\ContentSecurityPolicy;

/**
 * Interface to determine whether a value is covered by some other value in the scope of CSP,
 * e.g. URI `*.example.com` would "cover" URI `https://specific.example.com/path/file.js`
 * @internal
 */
interface CoveringInterface
{
    public function covers(CoveringInterface $other): bool;
}
