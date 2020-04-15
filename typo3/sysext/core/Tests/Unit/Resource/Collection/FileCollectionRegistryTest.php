<?php

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
use TYPO3\CMS\Core\Tests\Unit\Resource\BaseTestCase;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Test cases for FileCollectionRegistry
 */
class FileCollectionRegistryTest extends BaseTestCase
{
    /**
     * @var \TYPO3\CMS\Core\Resource\Collection\FileCollectionRegistry
     */
    protected $testSubject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeTestSubject();
    }

    protected function initializeTestSubject()
    {
        $this->testSubject = new FileCollectionRegistry();
    }

    /**
     * @test
     */
    public function registeredFileCollectionClassesCanBeRetrieved()
    {
        $className = get_class($this->getMockForAbstractClass(AbstractFileCollection::class));
        $this->testSubject->registerFileCollectionClass($className, 'foobar');
        $returnedClassName = $this->testSubject->getFileCollectionClass('foobar');
        self::assertEquals($className, $returnedClassName);
    }

    /**
     * @test
     */
    public function registerFileCollectionClassThrowsExceptionIfClassDoesNotExist()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1391295613);

        $this->testSubject->registerFileCollectionClass(StringUtility::getUniqueId('class_'), substr(StringUtility::getUniqueId('type_'), 0, 30));
    }

    /**
     * @test
     */
    public function registerFileCollectionClassThrowsExceptionIfTypeIsTooLong()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1391295611);

        $className = get_class($this->getMockForAbstractClass(AbstractFileCollection::class));
        $type = str_pad('', 40);
        $this->testSubject->registerFileCollectionClass($className, $type);
    }

    /**
     * @test
     */
    public function registerFileCollectionClassThrowsExceptionIfTypeIsAlreadyRegistered()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1391295643);

        $className = get_class($this->getMockForAbstractClass(AbstractFileCollection::class));
        $className2 = get_class($this->getMockForAbstractClass(StaticFileCollection::class));
        $this->testSubject->registerFileCollectionClass($className, 'foobar');
        $this->testSubject->registerFileCollectionClass($className2, 'foobar');
    }

    /**
     * @test
     */
    public function registerFileCollectionClassOverridesExistingRegisteredFileCollectionClass()
    {
        $className = get_class($this->getMockForAbstractClass(AbstractFileCollection::class));
        $className2 = get_class($this->getMockForAbstractClass(StaticFileCollection::class));
        $this->testSubject->registerFileCollectionClass($className, 'foobar');
        $this->testSubject->registerFileCollectionClass($className2, 'foobar', true);
    }

    /**
     * @test
     */
    public function getFileCollectionClassThrowsExceptionIfClassIsNotRegistered()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1391295644);

        $this->testSubject->getFileCollectionClass(StringUtility::getUniqueId());
    }

    /**
     * @test
     */
    public function getFileCollectionClassAcceptsClassNameIfClassIsRegistered()
    {
        $className = get_class($this->getMockForAbstractClass(AbstractFileCollection::class));
        $this->testSubject->registerFileCollectionClass($className, 'foobar');
        self::assertEquals($className, $this->testSubject->getFileCollectionClass('foobar'));
    }

    /**
     * @test
     */
    public function fileCollectionRegistryIsInitializedWithPreconfiguredFileCollections()
    {
        $className = get_class($this->getMockForAbstractClass(AbstractFileCollection::class));
        $type = substr(StringUtility::getUniqueId('type_'), 0, 30);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredCollections'] = [
            $type => $className
        ];
        $this->initializeTestSubject();
        self::assertEquals($className, $this->testSubject->getFileCollectionClass($type));
    }

    /**
     * @test
     */
    public function fileCollectionExistsReturnsTrueForAllExistingFileCollections()
    {
        $className = get_class($this->getMockForAbstractClass(AbstractFileCollection::class));
        $type = 'foo';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredCollections'] = [
            $type => $className
        ];
        $this->initializeTestSubject();
        self::assertTrue($this->testSubject->fileCollectionTypeExists($type));
        self::assertFalse($this->testSubject->fileCollectionTypeExists('bar'));
    }

    /**
     * @test
     */
    public function fileCollectionExistsReturnsFalseIfFileCollectionDoesNotExist()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredFileCollections'] = [];
        $this->initializeTestSubject();
        self::assertFalse($this->testSubject->fileCollectionTypeExists(StringUtility::getUniqueId('name_')));
    }

    /**
     * @test
     */
    public function addNewTypeToTCA()
    {

        // Create a TCA fixture for sys_file_collection
        $GLOBALS['TCA']['sys_file_collection'] = [
            'types' => [
                'typeB' => ['showitem' => 'fieldA, fieldB, fieldC;labelC, --palette--;;paletteC, fieldD'],
            ],
            'columns' => [
                'type' => [
                    'config' => [
                        'items' => ['Type B', 'typeB']
                    ]
                ]
            ]
        ];

        $type = 'my_type';
        $label = 'The Label';

        $this->testSubject->addTypeToTCA($type, $label, 'something');

        // check type
        self::assertEquals('sys_language_uid, l10n_parent, l10n_diffsource, title, --palette--;;1, type, something', $GLOBALS['TCA']['sys_file_collection']['types']['my_type']['showitem']);

        $indexOfNewType = count($GLOBALS['TCA']['sys_file_collection']['columns']['type']['config']['items']) - 1;

        // check if columns.type.item exist
        self::assertEquals($type, $GLOBALS['TCA']['sys_file_collection']['columns']['type']['config']['items'][$indexOfNewType][1]);
        self::assertEquals($label, $GLOBALS['TCA']['sys_file_collection']['columns']['type']['config']['items'][$indexOfNewType][0]);
    }
}
