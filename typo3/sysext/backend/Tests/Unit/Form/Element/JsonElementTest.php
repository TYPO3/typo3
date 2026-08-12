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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\CodeEditor\CodeEditorConfiguration;
use TYPO3\CMS\Backend\CodeEditor\Mode;
use TYPO3\CMS\Backend\Form\Element\JsonElement;
use TYPO3\CMS\Backend\Form\NodeExpansion\FieldInformation;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class JsonElementTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
    }

    #[Test]
    public function renderReturnsJsonInStandardTextarea(): void
    {
        $data = [
            'parameterArray' => [
                'itemFormElName' => 'config',
                'itemFormElValue' => ['foo' => 'bar'],
                'fieldConf' => [
                    'label' => 'foo',
                    'config' => [
                        'type' => 'json',
                        'enableCodeEditor' => false,
                        'placeholder' => 'placeholder',
                    ],
                ],
            ],
        ];

        $nodeFactoryStub = self::createStub(NodeFactory::class);
        $fieldInformationStub = self::createStub(FieldInformation::class);
        $fieldInformationStub->method('render')->willReturn(['html' => '']);
        $nodeFactoryStub->method('create')->willReturn($fieldInformationStub);

        $subject = new JsonElement(self::createStub(CodeEditorConfiguration::class));
        $subject->injectNodeFactory($nodeFactoryStub);
        $subject->setData($data);
        $result = $subject->render();

        self::assertEquals('@typo3/backend/form-engine/element/json-element.js', $result['javaScriptModules'][0]->getName());
        self::assertStringContainsString('<typo3-formengine-element-json', $result['html']);
        self::assertStringContainsString('placeholder="placeholder"', $result['html']);
        self::assertStringContainsString('&quot;foo&quot;: &quot;bar&quot;', $result['html']);
    }

    #[Test]
    public function renderReturnsJsonInCodeEditor(): void
    {
        $data = [
            'tableName' => 'aTable',
            'fieldName' => 'aField',
            'parameterArray' => [
                'itemFormElName' => 'config',
                'itemFormElValue' => ['foo' => 'bar'],
                'fieldConf' => [
                    'label' => 'foo',
                    'config' => [
                        'type' => 'json',
                        'placeholder' => 'placeholder',
                    ],
                ],
            ],
        ];

        $codeEditorConfigurationStub = self::createStub(CodeEditorConfiguration::class);
        $codeEditorConfigurationStub->method('getDefaultMode')->willReturn(new Mode(JavaScriptModuleInstruction::create('foo')));

        $nodeFactoryStub = self::createStub(NodeFactory::class);
        $fieldInformationStub = self::createStub(FieldInformation::class);
        $fieldInformationStub->method('render')->willReturn(['html' => '']);
        $nodeFactoryStub->method('create')->willReturn($fieldInformationStub);

        $subject = new JsonElement($codeEditorConfigurationStub);
        $subject->injectNodeFactory($nodeFactoryStub);
        $subject->setData($data);
        $result = $subject->render();

        self::assertEquals('@typo3/backend/code-editor/element/code-mirror-element.js', $result['javaScriptModules'][0]->getName());
        self::assertStringContainsString('<typo3-t3editor-codemirror', $result['html']);
        self::assertStringContainsString('placeholder="placeholder"', $result['html']);
        self::assertStringContainsString('&quot;foo&quot;: &quot;bar&quot;', $result['html']);
    }
}
