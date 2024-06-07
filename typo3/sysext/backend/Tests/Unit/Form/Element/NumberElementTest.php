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

use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\Element\NumberElement;
use TYPO3\CMS\Backend\Form\NodeExpansion\FieldInformation;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[BackupGlobals(true)]
final class NumberElementTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $GLOBALS['LANG'] = self::createStub(LanguageService::class);
    }

    public static function stepAttributeDataProvider(): \Generator
    {
        yield 'scale one' => [['scale' => 1], '0.1'];
        yield 'default scale' => [['scale' => 2], '0.01'];
        yield 'scale two' => [['scale' => 2], '0.01'];
        yield 'scale four' => [['scale' => 4], '0.0001'];
        yield 'highest scale a browser still validates' => [
            ['scale' => 18],
            '0.000000000000000001',
        ];
        yield 'first scale a browser no longer validates' => [['scale' => 19], 'any'];
        yield 'maximum scale' => [['scale' => 30], 'any'];
    }

    /**
     * A step below 1e-18 makes Chromium and WebKit report a stepMismatch for every non zero
     * value, which keeps requestSubmit() from submitting the form at all.
     */
    #[DataProvider('stepAttributeDataProvider')]
    #[Test]
    public function renderStepIsOnlySetToAValueBrowsersCanValidate(array $config, string $expectedStep): void
    {
        $result = $this->renderElement($config + ['type' => 'number'], '5.23');

        self::assertStringContainsString('step="' . $expectedStep . '"', $result);
    }

    #[Test]
    public function renderDoesNotSetAStepForAnIntegerField(): void
    {
        $result = $this->renderElement(['type' => 'number', 'scale' => 0], '5');

        self::assertStringNotContainsString('step="', $result);
    }

    public static function itemValueIsRoundedToScaleDataProvider(): \Generator
    {
        yield 'more decimal digits than the scale are cut off' => [['scale' => 1], '5.23', '5.2'];
        yield 'the last kept digit is rounded up' => [['scale' => 1], '5.26', '5.3'];
        yield 'rounding up carries over to the integer digits' => [['scale' => 1], '9.96', '10.0'];
        yield 'fewer decimal digits than the scale are padded' => [['scale' => 4], '5.23', '5.2300'];
        yield 'a matching value is kept' => [['scale' => 2], '5.23', '5.23'];
        yield 'the default scale applies without configuration' => [[], '5.239', '5.24'];
        yield 'a negative value keeps its sign' => [['scale' => 1], '-5.26', '-5.3'];
        yield 'a value rounded to zero is not negative' => [['scale' => 1], '-0.04', '0.0'];
        yield 'a scale beyond browser validation is rounded as well' => [['scale' => 30], '5.23', '5.230000000000000000000000000000'];
    }

    /**
     * A value that is no multiple of the rendered "step" makes the browser refuse to submit the
     * form, silently so if the field sits on an inactive tab.
     */
    #[DataProvider('itemValueIsRoundedToScaleDataProvider')]
    #[Test]
    public function renderRoundsItemValueToTheConfiguredScale(array $config, string $itemValue, string $expectedValue): void
    {
        $result = $this->renderElement($config + ['type' => 'number', 'scale' => 2], $itemValue);

        self::assertStringContainsString('<input type="hidden" name="myItemFormElName" value="' . $expectedValue . '" />', $result);
    }

    #[Test]
    public function renderKeepsAnEmptyValueOfANullableField(): void
    {
        $config = ['type' => 'number', 'scale' => 4, 'nullable' => true];

        $result = $this->renderElement($config, null);

        self::assertStringContainsString('<input type="hidden" name="myItemFormElName" value="" />', $result);
    }

    #[Test]
    public function renderDoesNotRoundAnIntegerField(): void
    {
        $result = $this->renderElement(['type' => 'number', 'scale' => 0], '523');

        self::assertStringContainsString('<input type="hidden" name="myItemFormElName" value="523" />', $result);
    }

    private function renderElement(array $config, ?string $itemValue): string
    {
        $data = [
            'tableName' => 'table_foo',
            'fieldName' => 'field_bar',
            'databaseRow' => [
                'uid' => 5,
            ],
            'parameterArray' => [
                'tableName' => 'table_foo',
                'fieldName' => 'field_bar',
                'fieldConf' => [
                    'label' => 'foo',
                    'config' => $config,
                ],
                'itemFormElName' => 'myItemFormElName',
                'itemFormElValue' => $itemValue,
            ],
        ];

        $nodeFactoryStub = self::createStub(NodeFactory::class);
        $fieldInformationStub = self::createStub(FieldInformation::class);
        $fieldInformationStub->method('render')->willReturn(['html' => '']);
        $nodeFactoryStub->method('create')->willReturn($fieldInformationStub);

        $subject = new NumberElement();
        $subject->injectNodeFactory($nodeFactoryStub);
        $subject->setData($data);

        return $subject->render()['html'];
    }
}
