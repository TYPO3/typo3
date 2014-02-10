<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\ContentObject;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Nicole Cordes <typo3@cordes.co>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Testcase for TYPO3\CMS\Frontend\ContentObject\FilesContentObject
 *
 * @author Nicole Cordes <typo3@cordes.co>
 */
class FilesContentObjectTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Frontend\ContentObject\FilesContentObject|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $subject = NULL;

	/**
	 * @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
	 */
	protected $tsfe = NULL;

	/**
	 * Set up
	 */
	public function setUp() {
		$templateService = $this->getMock('TYPO3\\CMS\\Core\\TypoScript\\TemplateService', array('getFileName', 'linkData'));
		$this->tsfe = $this->getMock('TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController', array('dummy'), array(), '', FALSE);
		$this->tsfe->tmpl = $templateService;
		$this->tsfe->config = array();
		$this->tsfe->page = array();
		$sysPageMock = $this->getMock('TYPO3\\CMS\\Frontend\\Page\\PageRepository', array('getRawRecord'));
		$this->tsfe->sys_page = $sysPageMock;
		$GLOBALS['TSFE'] = $this->tsfe;
		$GLOBALS['TSFE']->csConvObj = new \TYPO3\CMS\Core\Charset\CharsetConverter();
		$GLOBALS['TSFE']->renderCharset = 'utf-8';

		$contentObject = $this->getMock('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer', array('dummy'));
		$this->subject = $this->getMock('TYPO3\CMS\Frontend\ContentObject\FilesContentObject', array('dummy'), array($contentObject));
	}

	/**
	 * @return array
	 */
	public function renderReturnsFilesForFileReferencesDataProvider() {
		return array(
			'One file reference' => array(
				array(
					'references' => '1',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 1</p>',
			),
			'One file reference with begin higher than allowed' => array(
				array(
					'references' => '1',
					'begin' => '1',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'',
			),
			'One file reference with maxItems higher than allowed' => array(
				array(
					'references' => '1',
					'maxItems' => '2',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 1</p>',
			),
			'Multiple file references' => array(
				array(
					'references' => '1,2,3',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 1</p><p>File 2</p><p>File 3</p>',
			),
			'Multiple file references with begin' => array(
				array(
					'references' => '1,2,3',
					'begin' => '1',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 2</p><p>File 3</p>',
			),
			'Multiple file references with negative begin' => array(
				array(
					'references' => '1,2,3',
					'begin' => '-1',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 1</p><p>File 2</p><p>File 3</p>',
			),
			'Multiple file references with maxItems' => array(
				array(
					'references' => '1,2,3',
					'maxItems' => '2',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 1</p><p>File 2</p>',
			),
			'Multiple file references with negative maxItems' => array(
				array(
					'references' => '1,2,3',
					'maxItems' => '-2',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'',
			),
			'Multiple file references with begin and maxItems' => array(
				array(
					'references' => '1,2,3',
					'begin' => '1',
					'maxItems' => '1',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 2</p>',
			),
			'Multiple file references unsorted' => array(
				array(
					'references' => '1,3,2',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 1</p><p>File 3</p><p>File 2</p>',
			),
			'Multiple file references sorted by name' => array(
				array(
					'references' => '3,1,2',
					'sorting' => 'name',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 1</p><p>File 2</p><p>File 3</p>',
			),
		);
	}

	/**
	 * @test
	 * @dataProvider renderReturnsFilesForFileReferencesDataProvider
	 */
	public function renderReturnsFilesForFileReferences($configuration, $expected) {
		$fileReferenceMap = array();
		for ($i = 1; $i < 4; $i++) {
			$fileReference = $this->getMock('TYPO3\\CMS\\Core\\Resource\\FileReference', array(), array(), '', FALSE);
			$fileReference->expects($this->any())
				->method('getName')
				->will($this->returnValue('File ' . $i));
			$fileReference->expects($this->any())
				->method('hasProperty')
				->with('name')
				->will($this->returnValue(TRUE));
			$fileReference->expects($this->any())
				->method('getProperty')
				->with('name')
				->will($this->returnValue('File ' . $i));

			$fileReferenceMap[] = array($i, array(), $fileReference);
		}

		$resourceFactory = $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceFactory');
		$resourceFactory->expects($this->any())
			->method('getFileReferenceObject')
			->will($this->returnValueMap($fileReferenceMap));
		$this->subject->setFileFactory($resourceFactory);

		$this->assertSame($expected, $this->subject->render($configuration));
	}

	/**
	 * @return array
	 */
	public function renderReturnsFilesForFilesDataProvider() {
		return array(
			'One file' => array(
				array(
					'files' => '1',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 1</p>',
			),
			'One file with begin higher than allowed' => array(
				array(
					'files' => '1',
					'begin' => '1',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'',
			),
			'One file with maxItems higher than allowed' => array(
				array(
					'files' => '1',
					'maxItems' => '2',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 1</p>',
			),
			'Multiple files' => array(
				array(
					'files' => '1,2,3',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 1</p><p>File 2</p><p>File 3</p>',
			),
			'Multiple files with begin' => array(
				array(
					'files' => '1,2,3',
					'begin' => '1',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 2</p><p>File 3</p>',
			),
			'Multiple files with negative begin' => array(
				array(
					'files' => '1,2,3',
					'begin' => '-1',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 1</p><p>File 2</p><p>File 3</p>',
			),
			'Multiple files with maxItems' => array(
				array(
					'files' => '1,2,3',
					'maxItems' => '2',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 1</p><p>File 2</p>',
			),
			'Multiple files with negative maxItems' => array(
				array(
					'files' => '1,2,3',
					'maxItems' => '-2',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'',
			),
			'Multiple files with begin and maxItems' => array(
				array(
					'files' => '1,2,3',
					'begin' => '1',
					'maxItems' => '1',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 2</p>',
			),
			'Multiple files unsorted' => array(
				array(
					'files' => '1,3,2',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 1</p><p>File 3</p><p>File 2</p>',
			),
			'Multiple files sorted by name' => array(
				array(
					'files' => '3,1,2',
					'sorting' => 'name',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 1</p><p>File 2</p><p>File 3</p>',
			),
		);
	}

	/**
	 * @test
	 * @dataProvider renderReturnsFilesForFilesDataProvider
	 */
	public function renderReturnsFilesForFiles($configuration, $expected) {
		$fileMap = array();
		for ($i = 1; $i < 4; $i++) {
			$file = $this->getMock('TYPO3\\CMS\\Core\\Resource\\File', array(), array(), '', FALSE);
			$file->expects($this->any())
				->method('getName')
				->will($this->returnValue('File ' . $i));
			$file->expects($this->any())
				->method('hasProperty')
				->with('name')
				->will($this->returnValue(TRUE));
			$file->expects($this->any())
				->method('getProperty')
				->with('name')
				->will($this->returnValue('File ' . $i));

			$fileMap[] = array($i, array(), $file);
		}

		$resourceFactory = $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceFactory');
		$resourceFactory->expects($this->any())
			->method('getFileObject')
			->will($this->returnValueMap($fileMap));
		$this->subject->setFileFactory($resourceFactory);

		$this->assertSame($expected, $this->subject->render($configuration));
	}

	/**
	 * @return array
	 */
	public function renderReturnsFilesForCollectionsDataProvider() {
		return array(
			'One collection' => array(
				array(
					'collections' => '1',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 1</p><p>File 2</p><p>File 3</p>',
			),
			'One collection with begin' => array(
				array(
					'collections' => '1',
					'begin' => '1',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 2</p><p>File 3</p>',
			),
			'One collection with begin higher than allowed' => array(
				array(
					'collections' => '1',
					'begin' => '3',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'',
			),
			'One collection with maxItems' => array(
				array(
					'collections' => '1',
					'maxItems' => '2',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 1</p><p>File 2</p>',
			),
			'One collection with maxItems higher than allowed' => array(
				array(
					'collections' => '1',
					'maxItems' => '4',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 1</p><p>File 2</p><p>File 3</p>',
			),
			'One collections with begin and maxItems' => array(
				array(
					'collections' => '1',
					'begin' => '1',
					'maxItems' => '1',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 2</p>',
			),
			'Multiple collections' => array(
				array(
					'collections' => '1,2,3',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 1</p><p>File 2</p><p>File 3</p><p>File 4</p><p>File 5</p><p>File 6</p><p>File 7</p><p>File 8</p><p>File 9</p>',
			),
			'Multiple collections with begin' => array(
				array(
					'collections' => '1,2,3',
					'begin' => '3',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 4</p><p>File 5</p><p>File 6</p><p>File 7</p><p>File 8</p><p>File 9</p>',
			),
			'Multiple collections with negative begin' => array(
				array(
					'collections' => '1,2,3',
					'begin' => '-3',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 1</p><p>File 2</p><p>File 3</p><p>File 4</p><p>File 5</p><p>File 6</p><p>File 7</p><p>File 8</p><p>File 9</p>',
			),
			'Multiple collections with maxItems' => array(
				array(
					'collections' => '1,2,3',
					'maxItems' => '5',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 1</p><p>File 2</p><p>File 3</p><p>File 4</p><p>File 5</p>',
			),
			'Multiple collections with negative maxItems' => array(
				array(
					'collections' => '1,2,3',
					'maxItems' => '-5',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'',
			),
			'Multiple collections with begin and maxItems' => array(
				array(
					'collections' => '1,2,3',
					'begin' => '4',
					'maxItems' => '3',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 5</p><p>File 6</p><p>File 7</p>',
			),
			'Multiple collections unsorted' => array(
				array(
					'collections' => '1,3,2',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 1</p><p>File 2</p><p>File 3</p><p>File 7</p><p>File 8</p><p>File 9</p><p>File 4</p><p>File 5</p><p>File 6</p>',
			),
			'Multiple collections sorted by name' => array(
				array(
					'collections' => '3,1,2',
					'sorting' => 'name',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 1</p><p>File 2</p><p>File 3</p><p>File 4</p><p>File 5</p><p>File 6</p><p>File 7</p><p>File 8</p><p>File 9</p>',
			),
		);
	}

	/**
	 * @test
	 * @dataProvider renderReturnsFilesForCollectionsDataProvider
	 */
	public function renderReturnsFilesForCollections($configuration, $expected) {
		$collectionMap = array();
		$fileCount = 1;
		for ($i = 1; $i < 4; $i++) {
			$fileReferenceArray = array();
			for ($j = 1; $j < 4; $j++) {
				$fileReference = $this->getMock('TYPO3\\CMS\\Core\\Resource\\FileReference', array(), array(), '', FALSE);
				$fileReference->expects($this->any())
					->method('getName')
					->will($this->returnValue('File ' . $fileCount));
				$fileReference->expects($this->any())
					->method('hasProperty')
					->with('name')
					->will($this->returnValue(TRUE));
				$fileReference->expects($this->any())
					->method('getProperty')
					->with('name')
					->will($this->returnValue('File ' . $fileCount));

				$fileReferenceArray[] = $fileReference;
				$fileCount++;
			}

			$collection = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Collection\\StaticFileCollection', array(), array(), '', FALSE);
			$collection->expects($this->any())
				->method('getItems')
				->will($this->returnValue($fileReferenceArray));

			$collectionMap[] = array($i, $collection);
		}

		$collectionRepository = $this->getMock('TYPO3\\CMS\\Core\\Resource\\FileCollectionRepository');
		$collectionRepository->expects($this->any())
			->method('findByUid')
			->will($this->returnValueMap($collectionMap));
		$this->subject->setCollectionRepository($collectionRepository);

		$this->assertSame($expected, $this->subject->render($configuration));
	}

	/**
	 * @return array
	 */
	public function renderReturnsFilesForFoldersDataProvider() {
		return array(
			'One folder' => array(
				array(
					'folders' => '1:myfolder/',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 1</p><p>File 2</p><p>File 3</p>',
			),
			'One folder with begin' => array(
				array(
					'folders' => '1:myfolder/',
					'begin' => '1',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 2</p><p>File 3</p>',
			),
			'One folder with begin higher than allowed' => array(
				array(
					'folders' => '1:myfolder/',
					'begin' => '3',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'',
			),
			'One folder with maxItems' => array(
				array(
					'folders' => '1:myfolder/',
					'maxItems' => '2',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 1</p><p>File 2</p>',
			),
			'One folder with maxItems higher than allowed' => array(
				array(
					'folders' => '1:myfolder/',
					'maxItems' => '4',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 1</p><p>File 2</p><p>File 3</p>',
			),
			'One folder with begin and maxItems' => array(
				array(
					'folders' => '1:myfolder/',
					'begin' => '1',
					'maxItems' => '1',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 2</p>',
			),
			'Multiple folders' => array(
				array(
					'folders' => '1:myfolder/,2:myfolder/,3:myfolder/',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 1</p><p>File 2</p><p>File 3</p><p>File 4</p><p>File 5</p><p>File 6</p><p>File 7</p><p>File 8</p><p>File 9</p>',
			),
			'Multiple folders with begin' => array(
				array(
					'folders' => '1:myfolder/,2:myfolder/,3:myfolder/',
					'begin' => '3',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 4</p><p>File 5</p><p>File 6</p><p>File 7</p><p>File 8</p><p>File 9</p>',
			),
			'Multiple folders with negative begin' => array(
				array(
					'folders' => '1:myfolder/,2:myfolder/,3:myfolder/',
					'begin' => '-3',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 1</p><p>File 2</p><p>File 3</p><p>File 4</p><p>File 5</p><p>File 6</p><p>File 7</p><p>File 8</p><p>File 9</p>',
			),
			'Multiple folders with maxItems' => array(
				array(
					'folders' => '1:myfolder/,2:myfolder/,3:myfolder/',
					'maxItems' => '5',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 1</p><p>File 2</p><p>File 3</p><p>File 4</p><p>File 5</p>',
			),
			'Multiple folders with negative maxItems' => array(
				array(
					'folders' => '1:myfolder/,2:myfolder/,3:myfolder/',
					'maxItems' => '-5',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'',
			),
			'Multiple folders with begin and maxItems' => array(
				array(
					'folders' => '1:myfolder/,2:myfolder/,3:myfolder/',
					'begin' => '4',
					'maxItems' => '3',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 5</p><p>File 6</p><p>File 7</p>',
			),
			'Multiple folders unsorted' => array(
				array(
					'folders' => '1:myfolder/,3:myfolder/,2:myfolder/',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 1</p><p>File 2</p><p>File 3</p><p>File 7</p><p>File 8</p><p>File 9</p><p>File 4</p><p>File 5</p><p>File 6</p>',
			),
			'Multiple folders sorted by name' => array(
				array(
					'folders' => '3:myfolder/,1:myfolder/,2:myfolder/',
					'sorting' => 'name',
					'renderObj' => 'TEXT',
					'renderObj.' => array(
						'data' => 'file:current:name',
						'wrap' => '<p>|</p>',
					),
				),
				'<p>File 1</p><p>File 2</p><p>File 3</p><p>File 4</p><p>File 5</p><p>File 6</p><p>File 7</p><p>File 8</p><p>File 9</p>',
			),
		);
	}

	/**
	 * @test
	 * @dataProvider renderReturnsFilesForFoldersDataProvider
	 */
	public function renderReturnsFilesForFolders($configuration, $expected) {
		$folderMap = array();
		$fileCount = 1;
		for ($i = 1; $i < 4; $i++) {
			$fileArray = array();
			for ($j = 1; $j < 4; $j++) {
				$file = $this->getMock('TYPO3\\CMS\\Core\\Resource\\File', array(), array(), '', FALSE);
				$file->expects($this->any())
					->method('getName')
					->will($this->returnValue('File ' . $fileCount));
				$file->expects($this->any())
					->method('hasProperty')
					->with('name')
					->will($this->returnValue(TRUE));
				$file->expects($this->any())
					->method('getProperty')
					->with('name')
					->will($this->returnValue('File ' . $fileCount));

				$fileArray[] = $file;
				$fileCount++;
			}

			$folder = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Folder', array(), array(), '', FALSE);
			$folder->expects($this->any())
				->method('getFiles')
				->will($this->returnValue($fileArray));

			$folderMap[] = array($i . ':myfolder/', $folder);
		}

		$fileFactory = $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceFactory');
		$fileFactory->expects($this->any())
			->method('getFolderObjectFromCombinedIdentifier')
			->will($this->returnValueMap($folderMap));
		$this->subject->setFileFactory($fileFactory);

		$this->assertSame($expected, $this->subject->render($configuration));
	}
}
