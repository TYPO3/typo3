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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\Element;

use TYPO3\CMS\Backend\Form\Element\JsonElement;
use TYPO3\CMS\Backend\Form\NodeExpansion\FieldInformation;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\T3editor\Mode;
use TYPO3\CMS\T3editor\Registry\ModeRegistry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class JsonElementTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    /**
     * @test
     */
    public function renderReturnsJsonInStandardTextarea(): void
    {
        $data = [
                'parameterArray' => [
                'itemFormElName' => 'config',
                'itemFormElValue' => ['foo' => 'bar'],
                'fieldConf' => [
                    'config' => [
                        'type' => 'json',
                        'enableCodeEditor' => false,
                        'placeholder' => 'placeholder',
                    ],
                ],
            ],
        ];

        GeneralUtility::addInstance(IconFactory::class, $this->createMock(IconFactory::class));

        $nodeFactoryMock = $this->createMock(NodeFactory::class);
        $fieldInformationMock = $this->createMock(FieldInformation::class);
        $fieldInformationMock->method('render')->willReturn(['html' => '']);
        $nodeFactoryMock->method('create')->with(self::anything())->willReturn($fieldInformationMock);

        $subject = new JsonElement($nodeFactoryMock, $data);
        $result = $subject->render();

        self::assertEquals('@typo3/backend/form-engine/element/json-element.js', $result['javaScriptModules'][0]->getName());
        self::assertStringContainsString('<typo3-formengine-element-json', $result['html']);
        self::assertStringContainsString('placeholder="placeholder"', $result['html']);
        self::assertStringContainsString('&quot;foo&quot;: &quot;bar&quot;', $result['html']);
    }

    /**
     * @test
     */
    public function renderReturnsJsonInCodeEditor(): void
    {
        $data = [
            'tableName' => 'aTable',
            'fieldName' => 'aField',
            'parameterArray' => [
                'itemFormElName' => 'config',
                'itemFormElValue' => ['foo' => 'bar'],
                'fieldConf' => [
                    'config' => [
                        'type' => 'json',
                        'placeholder' => 'placeholder',
                    ],
                ],
            ],
        ];

        GeneralUtility::addInstance(IconFactory::class, $this->createMock(IconFactory::class));
        GeneralUtility::setSingletonInstance(PackageManager::class, $this->createMock(PackageManager::class));

        $cacheManagerMock = $this->createMock(CacheManager::class);
        $cacheMock = $this->createMock(FrontendInterface::class);
        $cacheManagerMock->method('getCache')->with('assets')->willReturn($cacheMock);
        $cacheMock->method('get')->withAnyParameters()->willReturn([]);
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerMock);

        $modeRegistryMock = $this->createMock(ModeRegistry::class);
        $modeRegistryMock->method('getDefaultMode')->willReturn(new Mode(JavaScriptModuleInstruction::create('foo')));
        GeneralUtility::setSingletonInstance(ModeRegistry::class, $modeRegistryMock);

        $nodeFactoryMock = $this->createMock(NodeFactory::class);
        $fieldInformationMock = $this->createMock(FieldInformation::class);
        $fieldInformationMock->method('render')->willReturn(['html' => '']);
        $nodeFactoryMock->method('create')->with(self::anything())->willReturn($fieldInformationMock);

        $subject = new JsonElement($nodeFactoryMock, $data);
        $result = $subject->render();

        self::assertEquals('@typo3/t3editor/element/code-mirror-element.js', $result['javaScriptModules'][0]->getName());
        self::assertStringContainsString('<typo3-t3editor-codemirror', $result['html']);
        self::assertStringContainsString('placeholder="placeholder"', $result['html']);
        self::assertStringContainsString('&quot;foo&quot;: &quot;bar&quot;', $result['html']);
    }
}
