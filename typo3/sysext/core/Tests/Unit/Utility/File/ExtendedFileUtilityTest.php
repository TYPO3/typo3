<?php
namespace TYPO3\CMS\Core\Tests\Unit\Utility\File;

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
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;

/**
 * Testcase for class \TYPO3\CMS\Core\Utility\File\ExtendedFileUtility
 */
class ExtendedFileUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * Sets up this testcase
     */
    protected function setUp()
    {
        $GLOBALS['LANG'] = $this->getMock(\TYPO3\CMS\Lang\LanguageService::class, ['sL']);
        $GLOBALS['TYPO3_DB'] = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, []);
    }

    /**
     * @test
     */
    public function folderHasFilesInUseReturnsTrueIfItHasFiles()
    {
        $fileUid = 1;
        $file = $this->getMock(File::class, ['getUid'], [], '', false);
        $file->expects($this->once())->method('getUid')->will($this->returnValue($fileUid));

        $folder = $this->getMock(Folder::class, ['getFiles'], [], '', false);
        $folder->expects($this->once())
            ->method('getFiles')->with(0, 0, Folder::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS, true)
            ->will($this->returnValue([$file])
        );

        /** @var \TYPO3\CMS\Core\Utility\File\ExtendedFileUtility $subject */
        $subject = $this->getMock(\TYPO3\CMS\Core\Utility\File\ExtendedFileUtility::class, ['addFlashMessage'], [], '');
        $GLOBALS['TYPO3_DB']->expects($this->once())
            ->method('exec_SELECTcountRows')->with('*', 'sys_refindex', 'deleted=0 AND ref_table="sys_file" AND ref_uid IN (' . $fileUid . ') AND tablename<>"sys_file_metadata"')
            ->will($this->returnValue(1));

        $GLOBALS['LANG']->expects($this->at(0))->method('sL')
            ->with('LLL:EXT:lang/locallang_core.xlf:message.description.folderNotDeletedHasFilesWithReferences')
            ->will($this->returnValue('folderNotDeletedHasFilesWithReferences'));
        $GLOBALS['LANG']->expects($this->at(1))->method('sL')
            ->with('LLL:EXT:lang/locallang_core.xlf:message.header.folderNotDeletedHasFilesWithReferences')
            ->will($this->returnValue('folderNotDeletedHasFilesWithReferences'));

        $result = $subject->folderHasFilesInUse($folder);
        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function folderHasFilesInUseReturnsFalseIfItHasNoFiles()
    {
        $folder = $this->getMock(Folder::class, ['getFiles'], [], '', false);
        $folder->expects($this->once())->method('getFiles')->with(0, 0, Folder::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS, true)->will(
            $this->returnValue([])
        );

        /** @var \TYPO3\CMS\Core\Utility\File\ExtendedFileUtility $subject */
        $subject = $this->getMock(\TYPO3\CMS\Core\Utility\File\ExtendedFileUtility::class, ['addFlashMessage'], [], '');
        $this->assertFalse($subject->folderHasFilesInUse($folder));
    }
}
