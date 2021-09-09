<?php

declare(strict_types=1);

namespace TYPO3\CMS\Core\Core;

use TYPO3\CMS\Core\Utility\StringUtility;

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

/**
 * @internal
 */
final class RequestId
{
    /**
     * @var string
     */
    private string $requestId;

    public function __construct()
    {
        $this->requestId = substr(md5(StringUtility::getUniqueId()), 0, 13);
    }

    public function __toString(): string
    {
        return $this->requestId;
    }
}
