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

namespace TYPO3\CMS\Extbase\Tests\Unit\Configuration;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Extbase\Configuration\FrontendConfigurationManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FrontendConfigurationManagerTest extends UnitTestCase
{
    #[Test]
    public function getConfigurationRecursivelyMergesCurrentPluginConfigurationWithFrameworkConfiguration(): void
    {
        $testTypoScriptSetup = [
            'foo.' => [
                'bar' => 'baz',
            ],
            'config.' => [
                'tx_extbase.' => [
                    'settings.' => [
                        'setting1' => 'value1',
                        'setting2' => 'value2',
                    ],
                    'view.' => [
                        'viewSub.' => [
                            'key1' => 'value1',
                            'key2' => 'value2',
                        ],
                    ],
                ],
            ],
            'plugin.' => [
                'tx_currentextensionname.' => [
                ],
                'tx_currentextensionname_currentpluginname.' => [
                    'settings.' => [
                        'setting1' => 'overriddenValue1',
                        'setting3' => 'additionalValue',
                    ],
                    'view.' => [
                        'viewSub.' => [
                            'key1' => 'overridden',
                            'key3' => 'new key',
                        ],
                    ],
                    'persistence.' => [
                        'storagePid' => '123',
                    ],
                ],
            ],
        ];
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray($testTypoScriptSetup);
        $request = (new ServerRequest())->withAttribute('frontend.typoscript', $frontendTypoScript);
        $expectedResult = [
            'settings' => [
                'setting1' => 'overriddenValue1',
                'setting2' => 'value2',
                'setting3' => 'additionalValue',
            ],
            'view' => [
                'viewSub' => [
                    'key1' => 'overridden',
                    'key2' => 'value2',
                    'key3' => 'new key',
                ],
            ],
            'persistence' => [
                'storagePid' => '123',
            ],
            'controllerConfiguration' => [],
            'extensionName' => 'CurrentExtensionName',
            'pluginName' => 'CurrentPluginName',
        ];
        $subject = new FrontendConfigurationManager(
            new TypoScriptService(),
            $this->createMock(FlexFormService::class),
            $this->createMock(PageRepository::class),
            new NoopEventDispatcher()
        );
        $actualResult = $subject->getConfiguration($request, ['extensionName' => 'CurrentExtensionName', 'pluginName' => 'CurrentPluginName']);
        self::assertEquals($expectedResult, $actualResult);
    }
}
