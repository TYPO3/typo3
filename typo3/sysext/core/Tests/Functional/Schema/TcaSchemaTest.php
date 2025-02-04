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

namespace TYPO3\CMS\Core\Tests\Functional\Schema;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Schema\Field\DateTimeFieldType;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\Field\TextFieldType;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TcaSchemaTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $coreExtensionsToLoad = ['felogin'];

    #[Test]
    public function tcaSchemaIsBuiltForMainCoreTables(): void
    {
        $factory = $this->get(TcaSchemaFactory::class);
        $mainSchema = $factory->get('pages');
        self::assertEquals('pages', $mainSchema->getName());
        self::assertCount(count($GLOBALS['TCA']['pages']['types']), $mainSchema->getSubSchemata());
        self::assertSame(array_keys($GLOBALS['TCA']), $factory->all()->getNames());
        $subSchema = $factory->get('pages.4');
        self::assertNotSame($mainSchema->getName(), $subSchema->getName());

        $contentSchema = $factory->get('tt_content');
        $textMediaSchema = $contentSchema->getSubSchema('textmedia');
        self::assertEquals($textMediaSchema, $factory->get('tt_content.textmedia'));

        $textMediaFields = $textMediaSchema->getFields();
        $usedColumns = array_keys(iterator_to_array($textMediaFields));
        self::assertEquals([
            'CType',
            'colPos',
            'header',
            'header_layout',
            'header_position',
            'date',
            'header_link',
            'subheader',
            'bodytext',
            'assets',
            'imagewidth',
            'imageheight',
            'imageborder',
            'imageorient',
            'imagecols',
            'image_zoom',
            'layout',
            'frame_class',
            'space_before_class',
            'space_after_class',
            'sectionIndex',
            'linkToTop',
            'categories',
            'sys_language_uid',
            'l18n_parent',
            'hidden',
            'starttime',
            'endtime',
            'fe_group',
            'editlock',
            'rowDescription',
        ], $usedColumns);

        self::assertInstanceOf(DateTimeFieldType::class, $textMediaFields['starttime']);
        self::assertNull($textMediaFields['foo']);
        /** @var TextFieldType $bodyTextFieldInTextMedia */
        $bodyTextFieldInTextMedia = $textMediaFields['bodytext'];
        self::assertTrue($bodyTextFieldInTextMedia->isRichText());
        /** @var TextFieldType $regularBodyTextField */
        $regularBodyTextField = $contentSchema->getFields()['bodytext'];
        self::assertFalse($regularBodyTextField->isRichText());
    }

    #[Test]
    public function tcaSchemaReturnsFieldsByFilterCallback(): void
    {
        $factory = $this->get(TcaSchemaFactory::class);
        $mainSchema = $factory->get('pages');
        $fields = $mainSchema->getFields(static fn(FieldTypeInterface $field): bool => $field->getName() === 'title');
        self::assertCount(1, $fields);
        self::assertEquals('input', $fields['title']->getType());
    }

    #[Test]
    public function passiveRelationsAreAttachedToSchema(): void
    {
        $factory = $this->get(TcaSchemaFactory::class);
        $fileReferences = $factory->get('sys_file_reference');
        $passiveRelations = $fileReferences->getPassiveRelations();
        self::assertCount(10, $passiveRelations);
    }
}
