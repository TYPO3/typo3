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

namespace TYPO3\CMS\Form\Tests\Unit\Domain\DTO\FormConfiguration\Prototype;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Form\Domain\DTO\FormConfiguration\Prototype\FormElementDefinitionCollection;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FormElementDefinitionCollectionTest extends UnitTestCase
{
    #[Test]
    public function preservesCustomDefinitionsAndExposesKnownConfiguration(): void
    {
        $collection = FormElementDefinitionCollection::fromArray([
            'Text' => [
                'implementationClassName' => 'TYPO3\\CMS\\Form\\Domain\\Model\\FormElements\\TextElement',
                'rendererClassName' => 'Vendor\\Site\\Form\\TextRenderer',
                'renderingOptions' => ['templateName' => 'Text'],
                'properties' => ['placeholder' => 'Enter text'],
                'validators' => [['identifier' => 'NotEmpty', 'options' => ['message' => 'Required']]],
                'variants' => [['identifier' => 'hidden', 'condition' => 'foo == 1']],
                'formEditor' => [
                    'label' => 'Text',
                    'group' => 'input',
                    'groupSorting' => 100,
                    'iconIdentifier' => 'form-text',
                    'customEditorOption' => ['kept' => true],
                ],
            ],
            'FancyCaptcha' => [
                'implementationClassName' => 'Vendor\\Site\\Form\\FancyCaptcha',
                'customOption' => ['nested' => ['value' => 'kept']],
                'formEditor' => [
                    'label' => 'Fancy captcha',
                    'group' => 'custom',
                    'groupSorting' => 250,
                    'iconIdentifier' => 'form-fancy-captcha',
                ],
            ],
        ]);

        self::assertSame(['Text', 'FancyCaptcha'], array_keys(iterator_to_array($collection)));
        self::assertSame(
            'TYPO3\\CMS\\Form\\Domain\\Model\\FormElements\\TextElement',
            $collection->get('Text')?->implementationClassName,
        );
        self::assertSame('Vendor\\Site\\Form\\TextRenderer', $collection->require('Text')->rendererClassName);
        self::assertSame(['placeholder' => 'Enter text'], $collection->require('Text')->properties);
        self::assertSame([['identifier' => 'NotEmpty', 'options' => ['message' => 'Required']]], $collection->require('Text')->validators);
        self::assertSame([['identifier' => 'hidden', 'condition' => 'foo == 1']], $collection->require('Text')->variants);
        self::assertSame('custom', $collection->require('FancyCaptcha')->formEditor->group);
        self::assertSame(250, $collection->require('FancyCaptcha')->formEditor->groupSorting);
        self::assertSame(['nested' => ['value' => 'kept']], $collection->require('FancyCaptcha')->get('customOption'));
        self::assertSame(['kept' => true], $collection->require('Text')->formEditor->get('customEditorOption'));
    }
}
