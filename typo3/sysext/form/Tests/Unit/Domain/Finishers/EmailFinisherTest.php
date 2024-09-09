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

namespace TYPO3\CMS\Form\Tests\Unit\Domain\Finishers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Finishers\EmailFinisher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class EmailFinisherTest extends UnitTestCase
{
    public static function templatePathsAreMergedCorrectlyDataProvider(): array
    {
        return [
            'default configuration' => [
                'globalConfig' => [
                    'templateRootPaths' => [
                        0 => 'EXT:core/Resources/Private/Templates/Email/',
                        10 => 'EXT:backend/Resources/Private/Templates/Email/',
                    ],
                    'layoutRootPaths' => [
                        0 => 'EXT:core/Resources/Private/Layouts/',
                        10 => 'EXT:backend/Resources/Private/Layouts/',
                    ],
                    'partialRootPaths' => [
                        0 => 'EXT:core/Resources/Private/Partials/',
                        10 => 'EXT:backend/Resources/Private/Partials/',
                    ],
                ],
                'localConfig' => [
                    'templateRootPaths' => [
                        10 => 'EXT:form/Resources/Private/Frontend/Templates/Finishers/Email/',
                    ],
                    'layoutRootPaths' => [],
                    'partialRootPaths' => [],
                ],
                'expected' => [
                    'templateRootPaths' => [
                        'EXT:core/Resources/Private/Templates/Email/',
                        'EXT:form/Resources/Private/Frontend/Templates/Finishers/Email/',
                    ],
                    'layoutRootPaths' => [
                        'EXT:core/Resources/Private/Layouts/',
                        'EXT:backend/Resources/Private/Layouts/',
                    ],
                    'partialRootPaths' => [
                        'EXT:core/Resources/Private/Partials/',
                        'EXT:backend/Resources/Private/Partials/',
                    ],
                ],
            ],

            'user-defined form templates' => [
                'globalConfig' => [
                    'templateRootPaths' => [
                        0 => 'EXT:core/Resources/Private/Templates/Email/',
                        10 => 'EXT:backend/Resources/Private/Templates/Email/',
                    ],
                    'layoutRootPaths' => [
                        0 => 'EXT:core/Resources/Private/Layouts/',
                        10 => 'EXT:backend/Resources/Private/Layouts/',
                    ],
                    'partialRootPaths' => [
                        0 => 'EXT:core/Resources/Private/Partials/',
                        10 => 'EXT:backend/Resources/Private/Partials/',
                    ],
                ],
                'localConfig' => [
                    'templateRootPaths' => [
                        10 => 'EXT:form/Resources/Private/Frontend/Templates/Finishers/Email/',
                        20 => 'EXT:myextension/Resources/Private/Templates/Form/Email/',
                    ],
                    'layoutRootPaths' => [],
                    'partialRootPaths' => [],
                ],
                'expected' => [
                    'templateRootPaths' => [
                        'EXT:core/Resources/Private/Templates/Email/',
                        'EXT:form/Resources/Private/Frontend/Templates/Finishers/Email/',
                        'EXT:myextension/Resources/Private/Templates/Form/Email/',
                    ],
                    'layoutRootPaths' => [
                        'EXT:core/Resources/Private/Layouts/',
                        'EXT:backend/Resources/Private/Layouts/',
                    ],
                    'partialRootPaths' => [
                        'EXT:core/Resources/Private/Partials/',
                        'EXT:backend/Resources/Private/Partials/',
                    ],
                ],
            ],

            'user-defined global and local templates' => [
                'globalConfig' => [
                    'templateRootPaths' => [
                        0 => 'EXT:core/Resources/Private/Templates/Email/',
                        10 => 'EXT:backend/Resources/Private/Templates/Email/',
                        20 => 'path/to/myextension/Resources/Private/Templates/Email/',
                    ],
                    'layoutRootPaths' => [
                        0 => 'EXT:core/Resources/Private/Layouts/',
                        10 => 'EXT:backend/Resources/Private/Layouts/',
                        20 => 'path/to/myextension/Resources/Private/Layouts/Email/',
                    ],
                    'partialRootPaths' => [
                        0 => 'EXT:core/Resources/Private/Partials/',
                        10 => 'EXT:backend/Resources/Private/Partials/',
                        20 => 'path/to/myextension/Resources/Private/Partials/Email/',
                    ],
                ],
                'localConfig' => [
                    'templateRootPaths' => [
                        10 => 'EXT:form/Resources/Private/Frontend/Templates/Finishers/Email/',
                        100 => 'path/to/myextension/Resources/Private/Templates/Form/Email/',
                    ],
                    'layoutRootPaths' => [
                        100 => 'path/to/myextension/Resources/Private/Layouts/Form/Email/',
                    ],
                    'partialRootPaths' => [
                        100 => 'path/to/myextension/Resources/Private/Partials/Form/Email/',
                    ],
                ],
                'expected' => [
                    'templateRootPaths' => [
                        'EXT:core/Resources/Private/Templates/Email/',
                        'EXT:form/Resources/Private/Frontend/Templates/Finishers/Email/',
                        'path/to/myextension/Resources/Private/Templates/Email/',
                        'path/to/myextension/Resources/Private/Templates/Form/Email/',
                    ],
                    'layoutRootPaths' => [
                        'EXT:core/Resources/Private/Layouts/',
                        'EXT:backend/Resources/Private/Layouts/',
                        'path/to/myextension/Resources/Private/Layouts/Email/',
                        'path/to/myextension/Resources/Private/Layouts/Form/Email/',
                    ],
                    'partialRootPaths' => [
                        'EXT:core/Resources/Private/Partials/',
                        'EXT:backend/Resources/Private/Partials/',
                        'path/to/myextension/Resources/Private/Partials/Email/',
                        'path/to/myextension/Resources/Private/Partials/Form/Email/',
                    ],
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('templatePathsAreMergedCorrectlyDataProvider')]
    public function templatePathsAreMergedCorrectly(array $globalConfig, array $localConfig, array $expected): void
    {
        $subject = $this->getAccessibleMock(EmailFinisher::class, null, [], '', false);
        $templatePaths = $subject->_call('initializeTemplatePaths', $globalConfig, $localConfig);

        self::assertSame(array_map(fn($path) => GeneralUtility::getFileAbsFileName($path), $expected['templateRootPaths']), $templatePaths->getTemplateRootPaths());
        self::assertSame(array_map(fn($path) => GeneralUtility::getFileAbsFileName($path), $expected['layoutRootPaths']), $templatePaths->getLayoutRootPaths());
        self::assertSame(array_map(fn($path) => GeneralUtility::getFileAbsFileName($path), $expected['partialRootPaths']), $templatePaths->getPartialRootPaths());
    }
}
