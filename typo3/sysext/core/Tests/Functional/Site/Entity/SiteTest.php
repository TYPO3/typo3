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

namespace TYPO3\CMS\Core\Tests\Functional\Site\Entity;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Error\PageErrorHandler\FluidPageErrorHandler;
use TYPO3\CMS\Core\Error\PageErrorHandler\PageContentErrorHandler;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SiteTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    public function getErrorHandlerReturnsConfiguredErrorHandler(): void
    {
        $subject = new Site('aint-misbehaving', 13, [
            'languages' => [],
            'errorHandling' => [
                [
                    'errorCode' => 123,
                    'errorHandler' => 'Fluid',
                ],
                [
                    'errorCode' => 124,
                    'errorContentSource' => 123,
                    'errorHandler' => 'Page',
                ],
                [
                    'errorCode' => 125,
                    'errorHandler' => 'PHP',
                    'errorContentSource' => 123,
                    'errorPhpClassFQCN' => PageContentErrorHandler::class,
                ],
            ],
        ]);

        GeneralUtility::addInstance(FluidPageErrorHandler::class, $this->createMock(FluidPageErrorHandler::class));

        self::assertInstanceOf(FluidPageErrorHandler::class, $subject->getErrorHandler(123));
        self::assertInstanceOf(PageContentErrorHandler::class, $subject->getErrorHandler(124));
        self::assertInstanceOf(PageContentErrorHandler::class, $subject->getErrorHandler(125));
    }

    #[Test]
    public function getErrorHandlerUsesFallbackWhenNoErrorHandlerForStatusCodeIsConfigured(): void
    {
        $subject = new Site('aint-misbehaving', 13, [
            'languages' => [],
            'errorHandling' => [
                [
                    'errorCode' => 403,
                    'errorHandler' => 'Does it really matter?',
                ],
                [
                    'errorCode' => 0,
                    'errorContentSource' => 123,
                    'errorHandler' => 'Page', // PageContentErrorHandler fallback
                ],
            ],
        ]);
        self::assertInstanceOf(PageContentErrorHandler::class, $subject->getErrorHandler(404));
    }
}
