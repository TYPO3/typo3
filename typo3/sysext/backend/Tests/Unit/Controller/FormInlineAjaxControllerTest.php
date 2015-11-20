<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Controller;

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

use TYPO3\CMS\Backend\Controller\FormInlineAjaxController;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case
 */
class FormInlineAjaxControllerTest extends UnitTestCase
{
    /**
     * Checks if the given file type may be uploaded without *ANY* limit to file types being given
     *
     * @test
     */
    public function checkInlineFileTypeAccessForFieldForFieldNoFiletypesReturnsTrue()
    {
        $selectorData = array();
        $fileData['extension'] = 'png';
        $mockObject = $this->getAccessibleMock(FormInlineAjaxController::class, array('dummy'), array(), '', false);
        $mayUploadFile = $mockObject->_call('checkInlineFileTypeAccessForField', $selectorData, $fileData);
        $this->assertTrue($mayUploadFile);
    }

    /**
     * Checks if the given file type may be uploaded and the given file type is *NOT* in the list of allowed files
     *
     * @test
     */
    public function checkInlineFileTypeAccessForFieldFiletypesSetRecordTypeNotInListReturnsFalse()
    {
        $selectorData['PA']['fieldConf']['config']['appearance']['elementBrowserAllowed'] = 'doc, png, jpg, tiff';
        $fileData['extension'] = 'php';
        $mockObject = $this->getAccessibleMock(FormInlineAjaxController::class, array('dummy'), array(), '', false);
        $mayUploadFile = $mockObject->_call('checkInlineFileTypeAccessForField', $selectorData, $fileData);
        $this->assertFalse($mayUploadFile);
    }

    /**
     * Checks if the given file type may be uploaded and the given file type *is* in the list of allowed files
     * @test
     */
    public function checkInlineFileTypeAccessForFieldFiletypesSetRecordTypeInListReturnsTrue()
    {
        $selectorData['PA']['fieldConf']['config']['appearance']['elementBrowserAllowed'] = 'doc, png, jpg, tiff';
        $fileData['extension'] = 'png';
        $mockObject = $this->getAccessibleMock(FormInlineAjaxController::class, array('dummy'), array(), '', false);
        $mayUploadFile = $mockObject->_call('checkInlineFileTypeAccessForField', $selectorData, $fileData);
        $this->assertTrue($mayUploadFile);
    }
}
