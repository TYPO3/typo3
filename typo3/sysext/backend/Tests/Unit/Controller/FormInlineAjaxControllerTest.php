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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
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
        $selectorData = [];
        $fileData['extension'] = 'png';
        $mockObject = $this->getAccessibleMock(FormInlineAjaxController::class, ['dummy'], [], '', false);
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
        $mockObject = $this->getAccessibleMock(FormInlineAjaxController::class, ['dummy'], [], '', false);
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
        $mockObject = $this->getAccessibleMock(FormInlineAjaxController::class, ['dummy'], [], '', false);
        $mayUploadFile = $mockObject->_call('checkInlineFileTypeAccessForField', $selectorData, $fileData);
        $this->assertTrue($mayUploadFile);
    }

    /**
     * @dataProvider splitDomObjectIdDataProviderForTableName
     * @param string $dataStructure
     * @param string $expectedTableName
     * @test
     *
     * test for the flexform domobject identifier split
     */
    public function splitDomObjectIdResolvesTablenameCorrectly($dataStructure, $expectedTableName)
    {
        $mock = $this->getAccessibleMock(FormInlineAjaxController::class, ['dummy'], [], '', false);
        $result = $mock->_call('splitDomObjectId', $dataStructure);
        $this->assertSame($expectedTableName, $result[1]);
    }

    /**
     * @return array
     */
    public function splitDomObjectIdDataProviderForTableName()
    {
        return [
            'news new' => [
                'data-335-tx_news_domain_model_news-2-content_elements-tt_content-999-pi_flexform---data---sheet.tabGeneral---lDEF---settings.related_files---vDEF-tx_news_domain_model_file',
                'tx_news_domain_model_file'
            ],
            'load existing child' => [
                'data-318-tx_styleguide_flex-4-flex_3---data---sInline---lDEF---inline_1---vDEF-tx_styleguide_flex_flex_3_inline_1_child-4',
                'tx_styleguide_flex_flex_3_inline_1_child'
            ],
            'create new child' => [
                'data-318-tx_styleguide_flex-4-flex_3---data---sInline---lDEF---inline_1---vDEF-tx_styleguide_flex_flex_3_inline_1_child',
                'tx_styleguide_flex_flex_3_inline_1_child'
            ],
            'insert new after' => [
                'data-336-tt_content-1000-pi_flexform---data---sheet.tabGeneral---lDEF---settings.related_files---vDEF-tx_news_domain_model_file-6',
                'tx_news_domain_model_file'
            ],
            'fal simple' => [
                'data-336-tt_content-998-pi_flexform---data---sheet.tabGeneral---lDEF---settings.image---vDEF-sys_file_reference-837',
                'sys_file_reference'
            ],
            'fal down deep' => [
                'data-335-tx_news_domain_model_news-2-content_elements-tt_content-999-pi_flexform---data---sheet.tabGeneral---lDEF---settings.image---vDEF-sys_file_reference',
                'sys_file_reference'
            ],
            'new record after others' => ['data-336-tt_content-1000-pi_flexform---data---sheet.tabGeneral---lDEF---settings.related_files---vDEF-tx_news_domain_model_file-NEW5757f36287214984252204', 'tx_news_domain_model_file'],
        ];
    }

    /**
     * @dataProvider splitDomObjectIdDataProviderForFlexFormPath
     *
     * @param string $dataStructure
     * @param string $expectedFlexformPath
     *
     * @test
     *
     * test for the flexform domobject identifier split
     */
    public function splitDomObjectIdResolvesFlexformPathCorrectly($dataStructure, $expectedFlexformPath)
    {
        $mock = $this->getAccessibleMock(FormInlineAjaxController::class, ['dummy'], [], '', false);
        $result = $mock->_call('splitDomObjectId', $dataStructure);
        $this->assertSame($expectedFlexformPath, $result[0]);
    }

    /**
     * @return array
     */
    public function splitDomObjectIdDataProviderForFlexFormPath()
    {
        return [
            'news new' => [
                'data-335-tx_news_domain_model_news-2-content_elements-tt_content-999-pi_flexform---data---sheet.tabGeneral---lDEF---settings.related_files---vDEF-tx_news_domain_model_file',
                'sheet.tabGeneral:lDEF:settings.related_files:vDEF'
            ],
            'load existing child' => [
                'data-318-tx_styleguide_flex-4-flex_3---data---sInline---lDEF---inline_1---vDEF-tx_styleguide_flex_flex_3_inline_1_child-4',
                'sInline:lDEF:inline_1:vDEF'
            ],
            'create new child' => [
                'data-318-tx_styleguide_flex-4-flex_3---data---sInline---lDEF---inline_1---vDEF-tx_styleguide_flex_flex_3_inline_1_child',
                'sInline:lDEF:inline_1:vDEF'
            ],
            'insert new after' => [
                'data-336-tt_content-1000-pi_flexform---data---sheet.tabGeneral---lDEF---settings.related_files---vDEF-tx_news_domain_model_file-6',
                'sheet.tabGeneral:lDEF:settings.related_files:vDEF'
            ],
            'fal simple' => [
                'data-336-tt_content-998-pi_flexform---data---sheet.tabGeneral---lDEF---settings.image---vDEF-sys_file_reference-837',
                'sheet.tabGeneral:lDEF:settings.image:vDEF'
            ],
            'fal down deep' => [
                'data-335-tx_news_domain_model_news-2-content_elements-tt_content-999-pi_flexform---data---sheet.tabGeneral---lDEF---settings.image---vDEF-sys_file_reference',
                'sheet.tabGeneral:lDEF:settings.image:vDEF'
            ],
            'new record after others' => [
                'data-336-tt_content-1000-pi_flexform---data---sheet.tabGeneral---lDEF---settings.related_files---vDEF-tx_news_domain_model_file-NEW5757f36287214984252204',
                'sheet.tabGeneral:lDEF:settings.related_files:vDEF'
            ],
        ];
    }

    /**
     * Fallback for IRRE items without inline view attribute
     * @issue https://forge.typo3.org/issues/76561
     *
     * @test
     */
    public function getInlineExpandCollapseStateArraySwitchesToFallbackIfTheBackendUserDoesNotHaveAnUCInlineViewProperty()
    {
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $backendUserProphecy->uc = [];
        $backendUser = $backendUserProphecy->reveal();

        $mockObject = $this->getAccessibleMock(
            FormInlineAjaxController::class,
            ['getBackendUserAuthentication'],
            [],
            '',
            false
        );
        $mockObject->method('getBackendUserAuthentication')->willReturn($backendUser);
        $result = $mockObject->_call('getInlineExpandCollapseStateArray');

        $this->assertEmpty($result);
    }

    /**
     * Unserialize uc inline view string for IRRE item
     * @issue https://forge.typo3.org/issues/76561
     *
     * @test
     */
    public function getInlineExpandCollapseStateArrayWillUnserializeUCInlineViewPropertyAsAnArrayWithData()
    {
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $backendUserProphecy->uc = ['inlineView' => serialize(['foo' => 'bar'])];
        $backendUser = $backendUserProphecy->reveal();

        $mockObject = $this->getAccessibleMock(
            FormInlineAjaxController::class,
            ['getBackendUserAuthentication'],
            [],
            '',
            false
        );
        $mockObject->method('getBackendUserAuthentication')->willReturn($backendUser);
        $result = $mockObject->_call('getInlineExpandCollapseStateArray');

        $this->assertNotEmpty($result);
    }
}
