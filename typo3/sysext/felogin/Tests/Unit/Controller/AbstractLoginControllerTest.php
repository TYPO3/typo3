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

namespace TYPO3\CMS\Felogin\Tests\Unit\Controller;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\FrontendLogin\Controller\AbstractLoginFormController;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class AbstractLoginControllerTest extends UnitTestCase
{
    /**
     * @var MockObject|ContentObjectRenderer
     */
    protected $cObj;

    /**
     * @var MockObject|AbstractLoginFormController|AccessibleObjectInterface
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->getAccessibleMockForAbstractClass(AbstractLoginFormController::class);
        $this->cObj = $this->createMock(ContentObjectRenderer::class);
        $configurationManager = $this->createMock(ConfigurationManagerInterface::class);
        $configurationManager->method('getContentObject')->willReturn($this->cObj);
        $this->subject->_set('configurationManager', $configurationManager);
    }

    /**
     * @dataProvider \TYPO3\CMS\Felogin\Tests\Unit\Controller\SettingsDataProvider::storageFoldersDataProvider
     * @param string $settingsPages
     * @param int $settingsRecursive
     * @param array $expected
     */
    public function testGetStorageFolders(string $settingsPages, int $settingsRecursive, array $expected): void
    {
        $this->cObj->method('getTreeList')
            ->willReturnCallback([SettingsDataProvider::class, 'treeListMethodMock']);

        $this->subject->_set(
            'settings',
            [
                'pages' => $settingsPages,
                'recursive' => $settingsRecursive,
            ]
        );

        $actual = $this->subject->_call('getStorageFolders');
        sort($actual);
        self::assertEquals(
            $expected,
            $actual
        );
    }
}
