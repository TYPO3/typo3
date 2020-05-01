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

namespace TYPO3\CMS\Extbase\Mvc\View;

/**
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 *
 * It's safe to use this interface in TYPO3 10 LTS as it will not be changed or removed in that
 * version but this interface is likely to be removed and/or changed in version 11.
 */
interface ViewResolverInterface
{
    public function resolve(string $controllerObjectName, string $actionName, string $format): ViewInterface;
}
