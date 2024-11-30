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

namespace TYPO3\CMS\Core\Tests\Unit\Resource\Collection;

use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\Collection\FileCollectionRegistry;
use TYPO3\CMS\Core\Tests\Unit\Resource\Collection\Fixtures\OtherTestingFileCollection;
use TYPO3\CMS\Core\Tests\Unit\Resource\Collection\Fixtures\TestingFileCollection;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FileCollectionRegistryTest extends UnitTestCase
{
    #[Test]
    public function registeredFileCollectionClassesCanBeRetrieved(): void
    {
        $className = TestingFileCollection::class;
        $subject = new FileCollectionRegistry();
        $subject->registerFileCollectionClass($className, 'foobar');
        $returnedClassName = $subject->getFileCollectionClass('foobar');
        self::assertEquals($className, $returnedClassName);
    }

    #[Test]
    public function registerFileCollectionClassThrowsExceptionIfClassDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1391295613);
        $subject = new FileCollectionRegistry();
        $subject->registerFileCollectionClass(StringUtility::getUniqueId('class_'), substr(StringUtility::getUniqueId('type_'), 0, 30));
    }

    #[Test]
    public function registerFileCollectionClassThrowsExceptionIfTypeIsTooLong(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1391295611);
        $subject = new FileCollectionRegistry();
        $className = TestingFileCollection::class;
        $type = str_pad('', 40);
        $subject->registerFileCollectionClass($className, $type);
    }

    #[Test]
    public function registerFileCollectionClassThrowsExceptionIfTypeIsAlreadyRegistered(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1391295643);
        $subject = new FileCollectionRegistry();
        $className = TestingFileCollection::class;
        $className2 = OtherTestingFileCollection::class;
        $subject->registerFileCollectionClass($className, 'foobar');
        $subject->registerFileCollectionClass($className2, 'foobar');
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function registerFileCollectionClassOverridesExistingRegisteredFileCollectionClass(): void
    {
        $className = TestingFileCollection::class;
        $className2 = OtherTestingFileCollection::class;
        $subject = new FileCollectionRegistry();
        $subject->registerFileCollectionClass($className, 'foobar');
        $subject->registerFileCollectionClass($className2, 'foobar', true);
    }

    #[Test]
    public function getFileCollectionClassThrowsExceptionIfClassIsNotRegistered(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1391295644);
        $subject = new FileCollectionRegistry();
        $subject->getFileCollectionClass(StringUtility::getUniqueId());
    }

    #[Test]
    public function getFileCollectionClassAcceptsClassNameIfClassIsRegistered(): void
    {
        $className = TestingFileCollection::class;
        $subject = new FileCollectionRegistry();
        $subject->registerFileCollectionClass($className, 'foobar');
        self::assertEquals($className, $subject->getFileCollectionClass('foobar'));
    }

    #[Test]
    public function fileCollectionRegistryIsInitializedWithPreconfiguredFileCollections(): void
    {
        $className = TestingFileCollection::class;
        $type = substr(StringUtility::getUniqueId('type_'), 0, 30);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredCollections'] = [
            $type => $className,
        ];
        $subject = new FileCollectionRegistry();
        self::assertEquals($className, $subject->getFileCollectionClass($type));
    }

    #[Test]
    public function fileCollectionExistsReturnsTrueForAllExistingFileCollections(): void
    {
        $className = TestingFileCollection::class;
        $type = 'foo';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredCollections'] = [
            $type => $className,
        ];
        $subject = new FileCollectionRegistry();
        self::assertTrue($subject->fileCollectionTypeExists($type));
        self::assertFalse($subject->fileCollectionTypeExists('bar'));
    }

    #[Test]
    public function fileCollectionExistsReturnsFalseIfFileCollectionDoesNotExist(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredFileCollections'] = [];
        $subject = new FileCollectionRegistry();
        self::assertFalse($subject->fileCollectionTypeExists(StringUtility::getUniqueId('name_')));
    }

    #[Test]
    public function addNewTypeToTCA(): void
    {
        // Create a TCA fixture for sys_file_collection
        $GLOBALS['TCA']['sys_file_collection'] = [
            'types' => [
                'typeB' => ['showitem' => 'fieldA, fieldB, fieldC;labelC, --palette--;;paletteC, fieldD'],
            ],
            'columns' => [
                'type' => [
                    'config' => [
                        'items' => [
                            ['label' => 'Type B', 'value' => 'typeB'],
                        ],
                    ],
                ],
            ],
        ];

        $type = 'my_type';
        $label = 'The Label';

        $subject = new FileCollectionRegistry();
        $subject->addTypeToTCA($type, $label, 'something');

        // Add another item, so that phpstan doesn't complain about non-existing array keys.
        $GLOBALS['TCA']['sys_file_collection']['columns']['type']['config']['items'][] = [
            ['label' => 'Type C', 'value' => 'typeC'],
        ];

        // check type
        self::assertEquals('sys_language_uid, l10n_parent, l10n_diffsource, title, --palette--;;1, type, something', $GLOBALS['TCA']['sys_file_collection']['types']['my_type']['showitem']);

        // check if columns.type.item exist
        self::assertEquals($type, $GLOBALS['TCA']['sys_file_collection']['columns']['type']['config']['items'][1]['value']);
        self::assertEquals($label, $GLOBALS['TCA']['sys_file_collection']['columns']['type']['config']['items'][1]['label']);
    }
}
