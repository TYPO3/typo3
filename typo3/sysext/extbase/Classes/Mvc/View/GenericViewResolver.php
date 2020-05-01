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

use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class GenericViewResolver implements ViewResolverInterface
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var string
     */
    private $defaultViewClass;

    public function __construct(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param string $defaultViewClass
     * @internal
     */
    public function setDefaultViewClass(string $defaultViewClass): void
    {
        $this->defaultViewClass = $defaultViewClass;
    }

    public function resolve(string $controllerObjectName, string $actionName, string $format): ViewInterface
    {
        return $this->objectManager->get($this->defaultViewClass);
    }
}
