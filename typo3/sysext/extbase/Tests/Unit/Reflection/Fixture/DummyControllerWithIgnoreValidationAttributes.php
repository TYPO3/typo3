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

namespace TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Extbase\Attribute\IgnoreValidation;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerInterface;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;

/**
 * Dummy controller with @TYPO3\CMS\Extbase\Attribute\IgnoreValidation attributes
 */
class DummyControllerWithIgnoreValidationAttributes implements ControllerInterface
{
    #[IgnoreValidation(['argumentName' => 'foo'])]
    #[IgnoreValidation(['argumentName' => 'bar'])]
    public function someAction($foo, $bar): void {}

    public function processRequest(RequestInterface $request): ResponseInterface
    {
        return new Response();
    }
}
