<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Controller\File;

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

use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;

/**
 * Tests for \TYPO3\CMS\Backend\Tests\Unit\Controller\File\FileController
 */
class FileControllerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Backend\Controller\File\FileController|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $fileController;

    /**
     * @var \TYPO3\CMS\Core\Resource\File|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fileResourceMock;

    /**
     * @var \TYPO3\CMS\Core\Resource\Folder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $folderResourceMock;

    /**
     * @var \TYPO3\CMS\Core\Utility\File\ExtendedFileUtility|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockFileProcessor;

    /**
     * @var ServerRequest|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    /**
     * @var Response|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $response;

    /**
     * Sets up this test case.
     */
    protected function setUp()
    {
        $this->fileResourceMock = $this->getMock(\TYPO3\CMS\Core\Resource\File::class, array('toArray', 'getModificationTime', 'getExtension'), array(), '', false);
        $this->folderResourceMock = $this->getMock(\TYPO3\CMS\Core\Resource\Folder::class, array('getIdentifier'), array(), '', false);
        $this->mockFileProcessor = $this->getMock(\TYPO3\CMS\Core\Utility\File\ExtendedFileUtility::class, array('getErrorMessages'), array(), '', false);

        $this->fileResourceMock->expects($this->any())->method('toArray')->will($this->returnValue(array('id' => 'foo')));
        $this->fileResourceMock->expects($this->any())->method('getModificationTime')->will($this->returnValue(123456789));
        $this->fileResourceMock->expects($this->any())->method('getExtension')->will($this->returnValue('html'));

        $this->request = new ServerRequest();
        $this->response = new Response();
    }

    /**
     * @test
     */
    public function flattenResultDataValueFlattensFileAndFolderResourcesButReturnsAnythingElseAsIs()
    {
        $this->fileController = $this->getAccessibleMock(\TYPO3\CMS\Backend\Controller\File\FileController::class, array('dummy'));

        $this->folderResourceMock->expects($this->once())->method('getIdentifier')->will($this->returnValue('bar'));

        $this->mockFileProcessor->expects($this->any())->method('getErrorMessages')->will($this->returnValue(array()));

        $this->assertTrue($this->fileController->_call('flattenResultDataValue', true));
        $this->assertSame(array(), $this->fileController->_call('flattenResultDataValue', array()));
        $result = $this->fileController->_call('flattenResultDataValue', $this->fileResourceMock);
        $this->assertContains('<span class="t3js-icon icon icon-size-small icon-state-default icon-mimetypes-text-html" data-identifier="mimetypes-text-html">', $result['icon']);
        unset($result['icon']);
        $this->assertSame(
            array(
                'id' => 'foo',
                'date' => '29-11-73',
                'thumbUrl' => '',
            ),
            $result
        );

        $this->assertSame(
            'bar',
            $this->fileController->_call('flattenResultDataValue', $this->folderResourceMock)
        );
    }

    /**
     * @test
     */
    public function processAjaxRequestDeleteProcessActuallyDoesNotChangeFileData()
    {
        $this->fileController = $this->getAccessibleMock(\TYPO3\CMS\Backend\Controller\File\FileController::class, array('init', 'main'));

        $fileData = array('delete' => array(true));
        $this->fileController->_set('fileProcessor', $this->mockFileProcessor);
        $this->fileController->_set('fileData', $fileData);
        $this->fileController->_set('redirect', false);

        $this->fileController->expects($this->once())->method('main');

        $this->fileController->processAjaxRequest($this->request, $this->response);
    }

    /**
     * @test
     */
    public function processAjaxRequestEditFileProcessActuallyDoesNotChangeFileData()
    {
        $this->fileController = $this->getAccessibleMock(\TYPO3\CMS\Backend\Controller\File\FileController::class, array('init', 'main'));

        $fileData = array('editfile' => array(true));
        $this->fileController->_set('fileProcessor', $this->mockFileProcessor);
        $this->fileController->_set('fileData', $fileData);
        $this->fileController->_set('redirect', false);

        $this->fileController->expects($this->once())->method('main');

        $this->fileController->processAjaxRequest($this->request, $this->response);
    }

    /**
     * @test
     */
    public function processAjaxRequestUnzipProcessActuallyDoesNotChangeFileData()
    {
        $this->fileController = $this->getAccessibleMock(\TYPO3\CMS\Backend\Controller\File\FileController::class, array('init', 'main'));

        $fileData = array('unzip' => array(true));
        $this->fileController->_set('fileProcessor', $this->mockFileProcessor);
        $this->fileController->_set('fileData', $fileData);
        $this->fileController->_set('redirect', false);

        $this->fileController->expects($this->once())->method('main');

        $this->fileController->processAjaxRequest($this->request, $this->response);
    }
}
