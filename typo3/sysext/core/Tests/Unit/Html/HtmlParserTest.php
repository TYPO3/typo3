<?php
namespace TYPO3\CMS\Core\Tests\Unit\Html;

/**
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
 * Testcase for \TYPO3\CMS\Core\Html\HtmlParser
 *
 * @author Nicole Cordes <typo3@cordes.co>
 */
class HtmlParserTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Html\HtmlParser
	 */
	protected $fixture = NULL;

	public function setUp() {
		$this->fixture = new \TYPO3\CMS\Core\Html\HtmlParser();
	}

	/**
	 * Data provider for substituteMarkerAndSubpartArrayRecursiveResolvesMarkersAndSubpartsArray
	 */
	public function substituteMarkerAndSubpartArrayRecursiveResolvesMarkersAndSubpartsArrayDataProvider() {
		$template = '###SINGLEMARKER1###
<!-- ###FOO### begin -->
<!-- ###BAR### begin -->
###SINGLEMARKER2###
<!-- ###BAR### end -->
<!-- ###FOOTER### begin -->
###SINGLEMARKER3###
<!-- ###FOOTER### end -->
<!-- ###FOO### end -->';

		$expected ='Value 1


Value 2.1

Value 2.2


Value 3.1

Value 3.2

';

		return array(
			'Single marker' => array(
				'###SINGLEMARKER###',
				array(
					'###SINGLEMARKER###' => 'Value 1'
				),
				'',
				FALSE,
				FALSE,
				'Value 1'
			),
			'Subpart marker' => array(
				$template,
				array(
					'###SINGLEMARKER1###' => 'Value 1',
					'###FOO###' => array(
						array(
							'###BAR###' => array(
								array(
									'###SINGLEMARKER2###' => 'Value 2.1'
								),
								array(
									'###SINGLEMARKER2###' => 'Value 2.2'
								)
							),
							'###FOOTER###' => array(
								array(
									'###SINGLEMARKER3###' => 'Value 3.1'
								),
								array(
									'###SINGLEMARKER3###' => 'Value 3.2'
								)
							)
						)
					)
				),
				'',
				FALSE,
				FALSE,
				$expected
			),
			'Subpart marker with wrap' => array(
				$template,
				array(
					'SINGLEMARKER1' => 'Value 1',
					'FOO' => array(
						array(
							'BAR' => array(
								array(
									'SINGLEMARKER2' => 'Value 2.1'
								),
								array(
									'SINGLEMARKER2' => 'Value 2.2'
								)
							),
							'FOOTER' => array(
								array(
									'SINGLEMARKER3' => 'Value 3.1'
								),
								array(
									'SINGLEMARKER3' => 'Value 3.2'
								)
							)
						)
					)
				),
				'###|###',
				FALSE,
				FALSE,
				$expected
			),
			'Subpart marker with lower marker array keys' => array(
				$template,
				array(
					'###singlemarker1###' => 'Value 1',
					'###foo###' => array(
						array(
							'###bar###' => array(
								array(
									'###singlemarker2###' => 'Value 2.1'
								),
								array(
									'###singlemarker2###' => 'Value 2.2'
								)
							),
							'###footer###' => array(
								array(
									'###singlemarker3###' => 'Value 3.1'
								),
								array(
									'###singlemarker3###' => 'Value 3.2'
								)
							)
						)
					)
				),
				'',
				TRUE,
				FALSE,
				$expected
			),
			'Subpart marker with unused markers' => array(
				$template,
				array(
					'###FOO###' => array(
						array(
							'###BAR###' => array(
								array(
									'###SINGLEMARKER2###' => 'Value 2.1'
								)
							),
							'###FOOTER###' => array(
								array(
									'###SINGLEMARKER3###' => 'Value 3.1'
								)
							)
						)
					)
				),
				'',
				FALSE,
				TRUE,
				'


Value 2.1


Value 3.1

'
			),
			'Subpart marker with empty subpart' => array(
				$template,
				array(
					'###SINGLEMARKER1###' => 'Value 1',
					'###FOO###' => array(
						array(
							'###BAR###' => array(
								array(
									'###SINGLEMARKER2###' => 'Value 2.1'
								),
								array(
									'###SINGLEMARKER2###' => 'Value 2.2'
								)
							),
							'###FOOTER###' => array()
						)
					)
				),
				'',
				FALSE,
				FALSE,
				'Value 1


Value 2.1

Value 2.2


'
			)
		);
	}

	/**
	 * @test
	 * @dataProvider substituteMarkerAndSubpartArrayRecursiveResolvesMarkersAndSubpartsArrayDataProvider
	 */
	public function substituteMarkerAndSubpartArrayRecursiveResolvesMarkersAndSubpartsArray($template, $markersAndSubparts, $wrap, $uppercase, $deleteUnused, $expected) {
		$this->assertSame($expected, $this->fixture->substituteMarkerAndSubpartArrayRecursive($template, $markersAndSubparts, $wrap, $uppercase, $deleteUnused));
	}

	/**
	 * @return array
	 */
	public function cDataWillRemainUnmodifiedDataProvider() {
		return array(
			'single-line CDATA' => array(
				'/*<![CDATA[*/ <hello world> /*]]>*/',
				'/*<![CDATA[*/ <hello world> /*]]>*/',
			),
			'multi-line CDATA #1' => array(
				'/*<![CDATA[*/' . LF . '<hello world> /*]]>*/',
				'/*<![CDATA[*/' . LF . '<hello world> /*]]>*/',
			),
			'multi-line CDATA #2' => array(
				'/*<![CDATA[*/ <hello world>' . LF . '/*]]>*/',
				'/*<![CDATA[*/ <hello world>' . LF . '/*]]>*/',
			),
			'multi-line CDATA #3' => array(
				'/*<![CDATA[*/' . LF . '<hello world>' . LF . '/*]]>*/',
				'/*<![CDATA[*/' . LF . '<hello world>' . LF . '/*]]>*/',
			),
		);
	}

	/**
	 * @test
	 * @param string $source
	 * @param string $expected
	 * @dataProvider cDataWillRemainUnmodifiedDataProvider
	 */
	public function xHtmlCleaningDoesNotModifyCDATA($source, $expected) {
		$result = $this->fixture->XHTML_clean($source);
		$this->assertSame($expected, $result);
	}

}
