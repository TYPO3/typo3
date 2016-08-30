<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Module;

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

/**
 * Test case
 */
class ModuleControllerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Backend\Module\ModuleController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $moduleController;

    protected function setUp()
    {
        $this->moduleController = $this->getAccessibleMock(\TYPO3\CMS\Backend\Module\ModuleController::class, ['getLanguageService'], [], '', false);
    }

    /**
     * @test
     */
    public function createEntryFromRawDataGeneratesMenuEntry()
    {
        $entry = $this->moduleController->_call('createEntryFromRawData', []);
        $this->assertInstanceOf(\TYPO3\CMS\Backend\Domain\Model\Module\BackendModule::class, $entry);
    }

    /**
     * @test
     */
    public function createEntryFromRawDataSetsPropertiesInEntryObject()
    {
        $rawModule = [
            'name' => 'nameTest',
            'title' => 'titleTest',
            'onclick' => 'onclickTest',
            'icon' => [
                'test' => '123'
            ],
            'link' => 'linkTest',
            'description' => 'descriptionTest',
            'navigationComponentId' => 'navigationComponentIdTest'
        ];

        $languageServiceMock = $this->getMock(\TYPO3\CMS\Lang\LanguageService::class, [], [], '', false);
        $languageServiceMock->expects($this->once())->method('sL')->will($this->returnValue('titleTest'));
        $this->moduleController->expects($this->once())->method('getLanguageService')->will($this->returnValue($languageServiceMock));

        /** @var $entry \TYPO3\CMS\Backend\Domain\Model\Module\BackendModule */
        $entry = $this->moduleController->_call('createEntryFromRawData', $rawModule);
        $this->assertEquals('nameTest', $entry->getName());
        $this->assertEquals('titleTest', $entry->getTitle());
        $this->assertEquals('linkTest', $entry->getLink());
        $this->assertEquals('onclickTest', $entry->getOnClick());
        $this->assertEquals('navigationComponentIdTest', $entry->getNavigationComponentId());
        $this->assertEquals('descriptionTest', $entry->getDescription());
        $this->assertEquals(['test' => '123'], $entry->getIcon());
    }

    /**
     * @test
     */
    public function createEntryFromRawDataSetsLinkIfPathIsGivenInEntryObject()
    {
        $rawModule = [
            'path' => 'pathTest'
        ];
        /** @var $entry \TYPO3\CMS\Backend\Domain\Model\Module\BackendModule */
        $entry = $this->moduleController->_call('createEntryFromRawData', $rawModule);
        $this->assertEquals('pathTest', $entry->getLink());
    }
}
