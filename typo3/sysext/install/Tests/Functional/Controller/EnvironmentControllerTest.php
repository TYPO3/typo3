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

namespace TYPO3\CMS\Install\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\ClassLoadingInformation;
use TYPO3\CMS\Core\Imaging\GraphicsCanvas;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Controller\EnvironmentController;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class EnvironmentControllerTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['install'];

    protected array $configurationToUseInTestInstance = [
        'BE' => [
            // Non-empty to satisfy the install tool integrity checks, value irrelevant.
            'installToolPassword' => '$argon2i$v=19$m=65536,t=16,p=1$placeholder',
        ],
    ];

    public static function textTestActionsDataProvider(): array
    {
        return [
            'true type' => ['imageProcessingTrueTypeAction'],
            'GD text' => ['imageProcessingGdlibRenderTextAction'],
            'GD nice text' => ['imageProcessingGdlibNiceTextAction'],
            'GD nice text with shadow' => ['imageProcessingGdlibNiceTextShadowAction'],
        ];
    }

    /**
     * A GD build without FreeType cannot render the text of these tests, they
     * are skipped with a note instead of showing an image without text.
     */
    #[Test]
    #[DataProvider('textTestActionsDataProvider')]
    public function textTestIsSkippedWithoutFreeTypeSupport(string $action): void
    {
        $response = $this->dispatchEnvironmentAction($action, false);

        $body = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($body['success']);
        self::assertArrayNotHasKey('outputFile', $body);
        self::assertSame('Skipped test', $body['status'][0]['title'] ?? null);
        self::assertStringContainsString('no FreeType support', $body['status'][0]['message'] ?? '');
    }

    #[Test]
    #[DataProvider('textTestActionsDataProvider')]
    public function textTestIsNotSkippedWithFreeTypeSupport(string $action): void
    {
        if (!GraphicsCanvas::canRenderText()) {
            self::markTestSkipped('GD of this PHP cannot render text.');
        }

        $response = $this->dispatchEnvironmentAction($action, null);

        $body = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($body['success']);
        self::assertNotContains('Skipped test', array_column($body['status'] ?? [], 'title'));
    }

    private function dispatchEnvironmentAction(string $action, ?bool $hasFreeTypeSupport): ResponseInterface
    {
        $testContainer = GeneralUtility::getContainer();
        $testSingletonInstances = GeneralUtility::getSingletonInstances();
        $outputBufferingLevel = ob_get_level();
        try {
            $failsafeContainer = Bootstrap::init(ClassLoadingInformation::getClassLoader(), true);
            if ($hasFreeTypeSupport !== null) {
                new \ReflectionProperty(GraphicsCanvas::class, 'freeTypeSupport')->setValue(null, $hasFreeTypeSupport);
            }
            return $failsafeContainer->get(EnvironmentController::class)->{$action}();
        } finally {
            new \ReflectionProperty(GraphicsCanvas::class, 'freeTypeSupport')->setValue(null, null);
            // Drop output buffers opened by the bootstrap, but never phpunit's own one.
            while (ob_get_level() > $outputBufferingLevel) {
                ob_end_clean();
            }
            GeneralUtility::purgeInstances();
            GeneralUtility::setContainer($testContainer);
            GeneralUtility::resetSingletonInstances($testSingletonInstances);
            ExtensionManagementUtility::setPackageManager($testContainer->get(PackageManager::class));
        }
    }
}
