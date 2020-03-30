<?php

declare(strict_types=1);

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
