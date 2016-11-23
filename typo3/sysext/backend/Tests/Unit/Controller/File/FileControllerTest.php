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
        $this->fileResourceMock = $this->getMock(\TYPO3\CMS\Core\Resource\File::class, ['toArray', 'getModificationTime', 'getExtension'], [], '', false);
        $this->folderResourceMock = $this->getMock(\TYPO3\CMS\Core\Resource\Folder::class, ['getIdentifier'], [], '', false);
        $this->mockFileProcessor = $this->getMock(\TYPO3\CMS\Core\Utility\File\ExtendedFileUtility::class, ['getErrorMessages'], [], '', false);

        $this->fileResourceMock->expects($this->any())->method('toArray')->will($this->returnValue(['id' => 'foo']));
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
        $this->fileController = $this->getAccessibleMock(\TYPO3\CMS\Backend\Controller\File\FileController::class, ['dummy']);

        $this->folderResourceMock->expects($this->once())->method('getIdentifier')->will($this->returnValue('bar'));

        $this->mockFileProcessor->expects($this->any())->method('getErrorMessages')->will($this->returnValue([]));

        $this->assertTrue($this->fileController->_call('flattenResultDataValue', true));
        $this->assertSame([], $this->fileController->_call('flattenResultDataValue', []));
        $result = $this->fileController->_call('flattenResultDataValue', $this->fileResourceMock);
        $this->assertContains('<span class="t3js-icon icon icon-size-small icon-state-default icon-mimetypes-text-html" data-identifier="mimetypes-text-html">', $result['icon']);
        unset($result['icon']);
        $this->assertSame(
            [
                'id' => 'foo',
                'date' => '29-11-73',
                'thumbUrl' => '',
            ],
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
        $this->fileController = $this->getAccessibleMock(\TYPO3\CMS\Backend\Controller\File\FileController::class, ['init', 'main']);

        $fileData = ['delete' => [true]];
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
        $this->fileController = $this->getAccessibleMock(\TYPO3\CMS\Backend\Controller\File\FileController::class, ['init', 'main']);

        $fileData = ['editfile' => [true]];
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
        $this->fileController = $this->getAccessibleMock(\TYPO3\CMS\Backend\Controller\File\FileController::class, ['init', 'main']);

        $fileData = ['unzip' => [true]];
        $this->fileController->_set('fileProcessor', $this->mockFileProcessor);
        $this->fileController->_set('fileData', $fileData);
        $this->fileController->_set('redirect', false);

        $this->fileController->expects($this->once())->method('main');

        $this->fileController->processAjaxRequest($this->request, $this->response);
    }

    /**
     * @test
     */
    public function processAjaxRequestReturnsStatus200IfNoErrorOccures()
    {
        $this->fileController = $this->getAccessibleMock(\TYPO3\CMS\Backend\Controller\File\FileController::class, ['init', 'main']);

        $fileData = ['editfile' => [true]];
        $this->fileController->_set('fileProcessor', $this->mockFileProcessor);
        $this->fileController->_set('fileData', $fileData);
        $this->fileController->_set('redirect', false);

        $result = $this->fileController->processAjaxRequest($this->request, $this->response);
        $this->assertEquals(200, $result->getStatusCode());
    }

    /**
     * @test
     */
    public function processAjaxRequestReturnsStatus500IfErrorOccurs()
    {
        $this->fileController = $this->getAccessibleMock(\TYPO3\CMS\Backend\Controller\File\FileController::class, ['init', 'main']);
        $this->mockFileProcessor->expects($this->any())->method('getErrorMessages')->will($this->returnValue(['error occured']));
        $this->fileController->_set('fileProcessor', $this->mockFileProcessor);
        $result = $this->fileController->processAjaxRequest($this->request, $this->response);
        $this->assertEquals(500, $result->getStatusCode());
    }
}
