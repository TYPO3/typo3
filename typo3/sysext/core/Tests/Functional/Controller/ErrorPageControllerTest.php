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

namespace TYPO3\CMS\Core\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Controller\ErrorPageController;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\ClassLoadingInformation;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ErrorPageControllerTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $coreExtensionsToLoad = ['fluid'];

    /**
     * The exception handlers render through this controller, and applications running on the
     * failsafe container - the install tool does - have to be able to build it. Without that, an
     * uncaught exception is answered with a bare status code and an empty body, because
     * GeneralUtility::makeInstance() falls back to constructing the class directly and dies on its
     * constructor arguments inside the exception handler.
     */
    #[Test]
    public function errorPageIsRenderedOnTheFailsafeContainer(): void
    {
        $currentContainer = GeneralUtility::getContainer();
        $currentSingletons = GeneralUtility::getSingletonInstances();

        try {
            $failsafeContainer = Bootstrap::init(ClassLoadingInformation::getClassLoader(), true);
            GeneralUtility::purgeInstances();
            GeneralUtility::setContainer($failsafeContainer);

            self::assertTrue($failsafeContainer->has(ErrorPageController::class));

            $content = GeneralUtility::makeInstance(ErrorPageController::class)->errorAction(
                'A title',
                'A message',
                1476049366,
                500
            );
        } finally {
            GeneralUtility::purgeInstances();
            GeneralUtility::setContainer($currentContainer);
            GeneralUtility::resetSingletonInstances($currentSingletons);
        }

        self::assertStringContainsString('A title', $content);
        self::assertStringContainsString('A message', $content);
        self::assertStringContainsString('1476049366', $content);
        // The template nonces its inline style, so the view helper has to be resolvable as well.
        self::assertMatchesRegularExpression('/nonce="[^"]+"/', $content);
    }
}
