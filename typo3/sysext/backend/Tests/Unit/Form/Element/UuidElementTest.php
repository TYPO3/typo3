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

use TYPO3\CMS\Backend\Form\Element\UuidElement;
use TYPO3\CMS\Backend\Form\NodeExpansion\FieldInformation;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class UuidElementTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['LANG'] = $this->createMock(LanguageService::class);
    }

    /**
     * @test
     */
    public function renderThrowsExceptionOnEmptyElementValue(): void
    {
        $data = [
            'tableName' => 'aTable',
            'fieldName' => 'identifier',
            'parameterArray' => [
                'itemFormElName' => 'identifier',
                'itemFormElValue' => '',
                'fieldConf' => [
                    'config' => [
                        'type' => 'uuid',
                        'required' => true,
                    ],
                ],
            ],
        ];

        GeneralUtility::addInstance(IconFactory::class, $this->createMock(IconFactory::class));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1678895476);

        (new UuidElement($this->createMock(NodeFactory::class), $data))->render();
    }

    /**
     * @test
     */
    public function renderThrowsExceptionOnInvalidUuid(): void
    {
        $data = [
            'tableName' => 'aTable',
            'fieldName' => 'identifier',
            'parameterArray' => [
                'itemFormElName' => 'identifier',
                'itemFormElValue' => '_-invalid-_',
                'fieldConf' => [
                    'config' => [
                        'type' => 'uuid',
                        'required' => true,
                    ],
                ],
            ],
        ];

        GeneralUtility::addInstance(IconFactory::class, $this->createMock(IconFactory::class));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1678895476);

        (new UuidElement($this->createMock(NodeFactory::class), $data))->render();
    }

    /**
     * @test
     */
    public function renderReturnsInputElementWithUuidAndCopyToClipboardButton(): void
    {
        $uuid = 'b3190536-1431-453e-afbb-25b8c5022513';
        $data = [
            'tableName' => 'aTable',
            'fieldName' => 'identifier',
            'parameterArray' => [
                'itemFormElName' => 'identifier',
                'itemFormElValue' => $uuid,
                'fieldConf' => [
                    'config' => [
                        'type' => 'uuid',
                    ],
                ],
            ],
        ];

        GeneralUtility::addInstance(IconFactory::class, $this->createMock(IconFactory::class));

        $nodeFactoryMock = $this->createMock(NodeFactory::class);
        $fieldInformationMock = $this->createMock(FieldInformation::class);
        $fieldInformationMock->method('render')->willReturn(['html' => '']);
        $nodeFactoryMock->method('create')->with(self::anything())->willReturn($fieldInformationMock);

        $subject = new UuidElement($nodeFactoryMock, $data);
        $result = $subject->render();

        self::assertEquals('@typo3/backend/copy-to-clipboard.js', $result['javaScriptModules'][0]->getName());
        self::assertMatchesRegularExpression('/<typo3-copy-to-clipboard.*text="' . $uuid . '"/s', $result['html']);
        self::assertMatchesRegularExpression('/<input.*value="' . $uuid . '".*id="formengine-uuid-/s', $result['html']);
    }

    /**
     * @test
     */
    public function renderReturnsInputElementWithUuidAndWithoutCopyToClipboardButton(): void
    {
        $uuid = 'b3190536-1431-453e-afbb-25b8c5022513';
        $data = [
            'tableName' => 'aTable',
            'fieldName' => 'identifier',
            'parameterArray' => [
                'itemFormElName' => 'identifier',
                'itemFormElValue' => $uuid,
                'fieldConf' => [
                    'config' => [
                        'type' => 'uuid',
                        'enableCopyToClipboard' => false,
                    ],
                ],
            ],
        ];

        GeneralUtility::addInstance(IconFactory::class, $this->createMock(IconFactory::class));

        $nodeFactoryMock = $this->createMock(NodeFactory::class);
        $fieldInformationMock = $this->createMock(FieldInformation::class);
        $fieldInformationMock->method('render')->willReturn(['html' => '']);
        $nodeFactoryMock->method('create')->with(self::anything())->willReturn($fieldInformationMock);

        $subject = new UuidElement($nodeFactoryMock, $data);
        $result = $subject->render();

        self::assertEmpty($result['javaScriptModules']);
        self::assertDoesNotMatchRegularExpression('/<typo3-copy-to-clipboard.*text="' . $uuid . '"/s', $result['html']);
        self::assertMatchesRegularExpression('/<input.*value="' . $uuid . '".*id="formengine-uuid-/s', $result['html']);
    }
}
