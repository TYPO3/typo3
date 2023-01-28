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

use TYPO3\CMS\Core\Resource\Collection\AbstractFileCollection;
use TYPO3\CMS\Core\Resource\Collection\FileCollectionRegistry;
use TYPO3\CMS\Core\Resource\Collection\StaticFileCollection;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class FileCollectionRegistryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function registeredFileCollectionClassesCanBeRetrieved(): void
    {
        $className = get_class($this->getMockForAbstractClass(AbstractFileCollection::class));
        $subject = new FileCollectionRegistry();
        $subject->registerFileCollectionClass($className, 'foobar');
        $returnedClassName = $subject->getFileCollectionClass('foobar');
        self::assertEquals($className, $returnedClassName);
    }

    /**
     * @test
     */
    public function registerFileCollectionClassThrowsExceptionIfClassDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1391295613);
        $subject = new FileCollectionRegistry();
        $subject->registerFileCollectionClass(StringUtility::getUniqueId('class_'), substr(StringUtility::getUniqueId('type_'), 0, 30));
    }

    /**
     * @test
     */
    public function registerFileCollectionClassThrowsExceptionIfTypeIsTooLong(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1391295611);
        $subject = new FileCollectionRegistry();
        $className = get_class($this->getMockForAbstractClass(AbstractFileCollection::class));
        $type = str_pad('', 40);
        $subject->registerFileCollectionClass($className, $type);
    }

    /**
     * @test
     */
    public function registerFileCollectionClassThrowsExceptionIfTypeIsAlreadyRegistered(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1391295643);
        $subject = new FileCollectionRegistry();
        $className = get_class($this->getMockForAbstractClass(AbstractFileCollection::class));
        $className2 = get_class($this->getMockForAbstractClass(StaticFileCollection::class));
        $subject->registerFileCollectionClass($className, 'foobar');
        $subject->registerFileCollectionClass($className2, 'foobar');
    }

    /**
     * @test
     */
    public function registerFileCollectionClassOverridesExistingRegisteredFileCollectionClass(): void
    {
        $className = get_class($this->getMockForAbstractClass(AbstractFileCollection::class));
        $className2 = get_class($this->getMockForAbstractClass(StaticFileCollection::class));
        $subject = new FileCollectionRegistry();
        $subject->registerFileCollectionClass($className, 'foobar');
        $subject->registerFileCollectionClass($className2, 'foobar', true);
    }

    /**
     * @test
     */
    public function getFileCollectionClassThrowsExceptionIfClassIsNotRegistered(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1391295644);
        $subject = new FileCollectionRegistry();
        $subject->getFileCollectionClass(StringUtility::getUniqueId());
    }

    /**
     * @test
     */
    public function getFileCollectionClassAcceptsClassNameIfClassIsRegistered(): void
    {
        $className = get_class($this->getMockForAbstractClass(AbstractFileCollection::class));
        $subject = new FileCollectionRegistry();
        $subject->registerFileCollectionClass($className, 'foobar');
        self::assertEquals($className, $subject->getFileCollectionClass('foobar'));
    }

    /**
     * @test
     */
    public function fileCollectionRegistryIsInitializedWithPreconfiguredFileCollections(): void
    {
        $className = get_class($this->getMockForAbstractClass(AbstractFileCollection::class));
        $type = substr(StringUtility::getUniqueId('type_'), 0, 30);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredCollections'] = [
            $type => $className,
        ];
        $subject = new FileCollectionRegistry();
        self::assertEquals($className, $subject->getFileCollectionClass($type));
    }

    /**
     * @test
     */
    public function fileCollectionExistsReturnsTrueForAllExistingFileCollections(): void
    {
        $className = get_class($this->getMockForAbstractClass(AbstractFileCollection::class));
        $type = 'foo';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredCollections'] = [
            $type => $className,
        ];
        $subject = new FileCollectionRegistry();
        self::assertTrue($subject->fileCollectionTypeExists($type));
        self::assertFalse($subject->fileCollectionTypeExists('bar'));
    }

    /**
     * @test
     */
    public function fileCollectionExistsReturnsFalseIfFileCollectionDoesNotExist(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredFileCollections'] = [];
        $subject = new FileCollectionRegistry();
        self::assertFalse($subject->fileCollectionTypeExists(StringUtility::getUniqueId('name_')));
    }

    /**
     * @test
     */
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
