<?php
namespace TYPO3\CMS\Core\Tests\Unit\Page;

/***************************************************************
 * Copyright notice
 *
 * (c) 2009-2013 Steffen Kamper (info@sk-typo3.de)
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use TYPO3\CMS\Core\Page\PageRenderer;

/**
 * Unit test case
 *
 * @see According functional test case
 */
class PageRendererTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function renderMethodCallsResetInAnyCase() {
		$pageRenderer = $this->getMock('TYPO3\\CMS\\Core\\Page\\PageRenderer', array('reset', 'prepareRendering', 'renderJavaScriptAndCss', 'getPreparedMarkerArray', 'getTemplateForPart'));
		$pageRenderer->expects($this->exactly(3))->method('reset');

		$pageRenderer->render(PageRenderer::PART_COMPLETE);
		$pageRenderer->render(PageRenderer::PART_HEADER);
		$pageRenderer->render(PageRenderer::PART_FOOTER);
	}

	/**
	 * @expectedException \UnexpectedValueException
	 * @test
	 */
	public function includingNotAvailableLocalJqueryVersionThrowsException() {
		/** @var \TYPO3\CMS\Core\Page\PageRenderer|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
		$subject = $this->getAccessibleMock('TYPO3\\CMS\\Core\\Page\\PageRenderer', array('dummy'), array(), '', FALSE);
		$subject->_set('availableLocalJqueryVersions', array('1.1.1'));
		$subject->loadJquery('2.2.2');
	}

	/**
	 * @expectedException \UnexpectedValueException
	 * @test
	 */
	public function includingJqueryWithNonAlphnumericNamespaceThrowsException() {
		/** @var \TYPO3\CMS\Core\Page\PageRenderer|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
		$subject = $this->getAccessibleMock('TYPO3\\CMS\\Core\\Page\\PageRenderer', array('dummy'), array(), '', FALSE);
		$subject->loadJquery(NULL, NULL, '12sd.12fsd');
		$subject->render();
	}

	/**
	 * @test
	 */
	public function addBodyContentAddsContent() {
		/** @var \TYPO3\CMS\Core\Page\PageRenderer|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
		$subject = $this->getAccessibleMock('TYPO3\\CMS\\Core\\Page\\PageRenderer', array('dummy'), array(), '', FALSE);
		$expectedReturnValue = 'ABCDE';
		$subject->addBodyContent('A');
		$subject->addBodyContent('B');
		$subject->addBodyContent('C');
		$subject->addBodyContent('D');
		$subject->addBodyContent('E');
		$out = $subject->getBodyContent();
		$this->assertEquals($expectedReturnValue, $out);
	}

	/**
	 * @test
	 */
	public function addInlineLanguageLabelFileSetsInlineLanguageLabelFiles() {
		/** @var \TYPO3\CMS\Core\Page\PageRenderer|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
		$subject = $this->getAccessibleMock('TYPO3\\CMS\\Core\\Page\\PageRenderer', array('dummy'), array(), '', FALSE);
		$fileReference = uniqid('file_');
		$selectionPrefix = uniqid('prefix_');
		$stripFromSelectionName = uniqid('strip_');
		$errorMode = 0;

		$expectedInlineLanguageLabelFile = array(
			'fileRef' => $fileReference,
			'selectionPrefix' => $selectionPrefix,
			'stripFromSelectionName' => $stripFromSelectionName,
			'errorMode' => $errorMode
		);

		$subject->addInlineLanguageLabelFile($fileReference, $selectionPrefix, $stripFromSelectionName, $errorMode);
		$actualResult = $subject->getInlineLanguageLabelFiles();

		$this->assertSame($expectedInlineLanguageLabelFile, array_pop($actualResult));
	}

	/**
	 * @test
	 */
	public function addInlineLanguageLabelFileSetsTwoDifferentInlineLanguageLabelFiles() {
		/** @var \TYPO3\CMS\Core\Page\PageRenderer|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
		$subject = $this->getAccessibleMock('TYPO3\\CMS\\Core\\Page\\PageRenderer', array('dummy'), array(), '', FALSE);
		$fileReference1 = uniqid('file1_');
		$selectionPrefix1 = uniqid('prefix1_');
		$stripFromSelectionName1 = uniqid('strip1_');
		$errorMode1 = 0;
		$expectedInlineLanguageLabelFile1 = array(
			'fileRef' => $fileReference1,
			'selectionPrefix' => $selectionPrefix1,
			'stripFromSelectionName' => $stripFromSelectionName1,
			'errorMode' => $errorMode1
		);
		$fileReference2 = uniqid('file2_');
		$selectionPrefix2 = uniqid('prefix2_');
		$stripFromSelectionName2 = uniqid('strip2_');
		$errorMode2 = 0;
		$expectedInlineLanguageLabelFile2 = array(
			'fileRef' => $fileReference2,
			'selectionPrefix' => $selectionPrefix2,
			'stripFromSelectionName' => $stripFromSelectionName2,
			'errorMode' => $errorMode2
		);

		$subject->addInlineLanguageLabelFile($fileReference1, $selectionPrefix1, $stripFromSelectionName1, $errorMode1);
		$subject->addInlineLanguageLabelFile($fileReference2, $selectionPrefix2, $stripFromSelectionName2, $errorMode2);
		$actualResult = $subject->getInlineLanguageLabelFiles();

		$this->assertSame($expectedInlineLanguageLabelFile2, array_pop($actualResult));
		$this->assertSame($expectedInlineLanguageLabelFile1, array_pop($actualResult));
	}

	/**
	 * @test
	 */
	public function addInlineLanguageLabelFileDoesNotSetSameLanguageFileTwice() {
		/** @var \TYPO3\CMS\Core\Page\PageRenderer|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
		$subject = $this->getAccessibleMock('TYPO3\\CMS\\Core\\Page\\PageRenderer', array('dummy'), array(), '', FALSE);
		$fileReference = uniqid('file2_');
		$selectionPrefix = uniqid('prefix2_');
		$stripFromSelectionName = uniqid('strip2_');
		$errorMode = 0;

		$subject->addInlineLanguageLabelFile($fileReference, $selectionPrefix, $stripFromSelectionName, $errorMode);
		$subject->addInlineLanguageLabelFile($fileReference, $selectionPrefix, $stripFromSelectionName, $errorMode);
		$this->assertSame(1, count($subject->getInlineLanguageLabelFiles()));
	}
}