<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
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
 * Testcase for class tslib_cObj
 *
 * @package TYPO3
 * @subpackage cms
 */
class tslib_cObj_testcase extends tx_phpunit_testcase {

	/**
	 * Holds the backed up $GLOBASL array()
	 *
	 * @var array
	 **/
	protected $backedUpGlobals;

	/**
	 * The content object
	 *
	 * @var tslib_cObj
	 **/
	protected $cObj;
	/**
	 * Sets up this test case
	 */
	public function setUp() {
		$this->backedUpGlobals = $GLOBALS;
		$this->cObj = new tslib_cObj();
		$GLOBALS['TSFE']->csConvObj = new t3lib_cs();
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['t3lib_cs_utils'] = 'mbstring';
	}

	/**
	 * Tears down this test case
	 */
	public function tearDown() {
		$GLOBALS = $this->backedUpGlobals;
	}

	/**
	 * This is the data provider for the tests of crop and cropHTML below. It provides all combinations
	 * of charset, text type, and configuration options to be tested.
	 *
	 * @return void
	 */
	public function providerForCrop() {
		$plainText = 'Kasper Skårhøj implemented the original version of the crop function.';
	 	$textWithMarkup = '<strong><a href="mailto:kasper@typo3.org">Kasper Skårhøj</a> implemented</strong> the original version of the crop function.';
		$textWithEntities = 'Kasper Sk&aring;rh&oslash;j implemented the; original version of the crop function.';
		
		$charsets = array();
		$charsets[] = 'iso-8859-1';
		$charsets[] = 'utf-8';
		// Enable more charsets if necessary. This will slow down overall test execution time!
		// $charsets[] = 'ascii';
		// $charsets[] = 'big5';

		$data = array();
		foreach ($charsets as $charset) {
			$data = array_merge($data, array(
				$charset . ' plain text; 11|...' => array('11|...', $plainText, 'Kasper Skår...', $charset),
				$charset . ' plain text; -58|...' => array('-58|...', $plainText, '...høj implemented the original version of the crop function.', $charset),
				$charset . ' plain text; 20|...|1' => array('20|...|1', $plainText, 'Kasper Skårhøj...', $charset),
				$charset . ' plain text; -49|...|1' => array('-49|...|1', $plainText, '...the original version of the crop function.', $charset),
				$charset . ' text with markup; 11|...' => array('11|...', $textWithMarkup, '<strong><a href="mailto:kasper@typo3.org">Kasper Skår...</a></strong>', $charset),
				$charset . ' text with markup; 13|...' => array('13|...', $textWithMarkup, '<strong><a href="mailto:kasper@typo3.org">Kasper Skårhø...</a></strong>', $charset),
				$charset . ' text with markup; 14|...' => array('14|...', $textWithMarkup, '<strong><a href="mailto:kasper@typo3.org">Kasper Skårhøj</a>...</strong>', $charset),
				$charset . ' text with markup; 15|...' => array('15|...', $textWithMarkup, '<strong><a href="mailto:kasper@typo3.org">Kasper Skårhøj</a> ...</strong>', $charset),
				$charset . ' text with markup; 29|...' => array('29|...', $textWithMarkup, '<strong><a href="mailto:kasper@typo3.org">Kasper Skårhøj</a> implemented</strong> th...', $charset),
				$charset . ' text with markup; -58|...' => array('-58|...', $textWithMarkup, '<strong><a href="mailto:kasper@typo3.org">...høj</a> implemented</strong> the original version of the crop function.', $charset),
				$charset . ' text with markup; 11|...|1' => array('11|...|1', $textWithMarkup, '<strong><a href="mailto:kasper@typo3.org">Kasper...</a></strong>', $charset),
				$charset . ' text with markup; 13|...|1' => array('13|...|1', $textWithMarkup, '<strong><a href="mailto:kasper@typo3.org">Kasper...</a></strong>', $charset),
				$charset . ' text with markup; 14|...|1' => array('14|...|1', $textWithMarkup, '<strong><a href="mailto:kasper@typo3.org">Kasper Skårhøj</a>...</strong>', $charset),
				$charset . ' text with markup; 15|...|1' => array('15|...|1', $textWithMarkup, '<strong><a href="mailto:kasper@typo3.org">Kasper Skårhøj</a>...</strong>', $charset),
				$charset . ' text with markup; 29|...|1' => array('29|...|1', $textWithMarkup, '<strong><a href="mailto:kasper@typo3.org">Kasper Skårhøj</a> implemented</strong>...', $charset),
				$charset . ' text with markup; -66|...|1' => array('-66|...|1', $textWithMarkup, '<strong><a href="mailto:kasper@typo3.org">...Skårhøj</a> implemented</strong> the original version of the crop function.', $charset),
				$charset . ' text with entities 9|...' => array('9|...', $textWithEntities, 'Kasper Sk...', $charset),
				$charset . ' text with entities 10|...' => array('10|...', $textWithEntities, 'Kasper Sk&aring;...', $charset),
				$charset . ' text with entities 11|...' => array('11|...', $textWithEntities, 'Kasper Sk&aring;r...', $charset),
				$charset . ' text with entities 13|...' => array('13|...', $textWithEntities, 'Kasper Sk&aring;rh&oslash;...', $charset),
				$charset . ' text with entities 14|...' => array('14|...', $textWithEntities, 'Kasper Sk&aring;rh&oslash;j...', $charset),
				$charset . ' text with entities 15|...' => array('15|...', $textWithEntities, 'Kasper Sk&aring;rh&oslash;j ...', $charset),
				$charset . ' text with entities 16|...' => array('16|...', $textWithEntities, 'Kasper Sk&aring;rh&oslash;j i...', $charset),
				$charset . ' text with entities -57|...' => array('-57|...', $textWithEntities, '...j implemented the; original version of the crop function.', $charset),
				$charset . ' text with entities -58|...' => array('-58|...', $textWithEntities, '...&oslash;j implemented the; original version of the crop function.', $charset),
				$charset . ' text with entities -59|...' => array('-59|...', $textWithEntities, '...h&oslash;j implemented the; original version of the crop function.', $charset),
				$charset . ' text with entities 9|...|1' => array('9|...|1', $textWithEntities, 'Kasper...', $charset),
				$charset . ' text with entities 10|...|1' => array('10|...|1', $textWithEntities, 'Kasper...', $charset),
				$charset . ' text with entities 11|...|1' => array('11|...|1', $textWithEntities, 'Kasper...', $charset),
				$charset . ' text with entities 13|...|1' => array('13|...|1', $textWithEntities, 'Kasper...', $charset),
				$charset . ' text with entities 14|...|1' => array('14|...|1', $textWithEntities, 'Kasper Sk&aring;rh&oslash;j...', $charset),
				$charset . ' text with entities 15|...|1' => array('15|...|1', $textWithEntities, 'Kasper Sk&aring;rh&oslash;j...', $charset),
				$charset . ' text with entities 16|...|1' => array('16|...|1', $textWithEntities, 'Kasper Sk&aring;rh&oslash;j...', $charset),
				$charset . ' text with entities -57|...|1' => array('-57|...|1', $textWithEntities, '...implemented the; original version of the crop function.', $charset),
				$charset . ' text with entities -58|...|1' => array('-58|...|1', $textWithEntities, '...implemented the; original version of the crop function.', $charset),
				$charset . ' text with entities -59|...|1' => array('-59|...|1', $textWithEntities, '...implemented the; original version of the crop function.', $charset)
				));
		}
		return $data;
	}

	/**
	 * Checks if stdWrap.cropHTML works with plain text cropping from left
	 *
	 * @test
     * @dataProvider providerForCrop
	 */
	public function cropHtmlWorks($settings, $subject, $expected, $charset) {
		$this->handleCharset($charset, $subject, $expected);
		$this->assertEquals($expected, $this->cObj->cropHTML($subject, $settings), 'cropHTML failed with settings: "' . $settings . '" and charset "' . $charset . '"');
	}

	/**
	 * Checks if stdWrap.cropHTML works with a complex content with many tags. Currently cropHTML
	 * counts multiple invisible characters not as one (as the browser will output the content).
	 *
	 * @test
	 */
	public function cropHtmlWorksWithComplexContent() {
		$GLOBALS['TSFE']->renderCharset = 'iso-8859-1';
		$subject = '
<h1>Blog Example</h1>
<hr>
<div class="csc-header csc-header-n1">
	<h2 class="csc-firstHeader">Welcome to Blog #1</h2>
</div>
<p class="bodytext">
	A blog about TYPO3 extension development. In order to start blogging, read the <a href="#">Help section</a>. If you have any further questions, feel free to contact the administrator John Doe (<a href="mailto:john.doe@example.com">john.doe@example.com)</a>.
</p>
<div class="tx-blogexample-list-container">
	<p class="bodytext">
		Below are the most recent posts:
	</p>
	<ul>
		<li>
			<h3>
				<a href="index.php?id=99&amp;tx_blogexample_pi1[post][uid]=211&amp;tx_blogexample_pi1[blog]=&amp;tx_blogexample_pi1[action]=show&amp;tx_blogexample_pi1[controller]=Post&amp;cHash=003b0131ed">The Post #1</a>
			</h3>
			<p class="bodytext">
				Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut...
			</p>
			<p class="metadata">
				Published on 26.08.2009 by Jochen Rau
			</p>
			<p>
				Tags: [MVC]&nbsp;[Domain Driven Design]&nbsp;<br>
				<a href="index.php?id=99&amp;tx_blogexample_pi1[post][uid]=211&amp;tx_blogexample_pi1[action]=show&amp;tx_blogexample_pi1[controller]=Post&amp;cHash=f982643bc3">read more &gt;&gt;</a><br>
				<a href="index.php?id=99&amp;tx_blogexample_pi1[post][uid]=211&amp;tx_blogexample_pi1[blog][uid]=70&amp;tx_blogexample_pi1[action]=edit&amp;tx_blogexample_pi1[controller]=Post&amp;cHash=5b481bc8f0">Edit</a>&nbsp;<a href="index.php?id=99&amp;tx_blogexample_pi1[post][uid]=211&amp;tx_blogexample_pi1[blog][uid]=70&amp;tx_blogexample_pi1[action]=delete&amp;tx_blogexample_pi1[controller]=Post&amp;cHash=4e52879656">Delete</a>
			</p>
		</li>
	</ul>
	<p>
		<a href="index.php?id=99&amp;tx_blogexample_pi1[blog][uid]=70&amp;tx_blogexample_pi1[action]=new&amp;tx_blogexample_pi1[controller]=Post&amp;cHash=2718a4b1a0">Create a new Post</a>
	</p>
</div>
<hr>
<p>
	© TYPO3 Association
</p>
';

		$result = $this->cObj->cropHTML($subject, '300');
		$expected = '
<h1>Blog Example</h1>
<hr>
<div class="csc-header csc-header-n1">
	<h2 class="csc-firstHeader">Welcome to Blog #1</h2>
</div>
<p class="bodytext">
	A blog about TYPO3 extension development. In order to start blogging, read the <a href="#">Help section</a>. If you have any further questions, feel free to contact the administrator John Doe (<a href="mailto:john.doe@example.com">john.doe@example.com)</a>.
</p>
<div class="tx-blogexample-list-container">
	<p class="bodytext">
		Below are the most recent posts:
	</p>
	<ul>
		<li>
			<h3>
				<a href="index.php?id=99&amp;tx_blogexample_pi1[post][uid]=211&amp;tx_blogexample_pi1[blog]=&amp;tx_blogexample_pi1[action]=show&amp;tx_blogexample_pi1[controller]=Post&amp;cHash=003b0131ed">The Pos</a></h3></li></ul></div>';
		$this->assertEquals($expected, $result);

		$result = $this->cObj->cropHTML($subject, '-100');
		$expected = '<div class="tx-blogexample-list-container"><ul><li><p>Design]&nbsp;<br>
				<a href="index.php?id=99&amp;tx_blogexample_pi1[post][uid]=211&amp;tx_blogexample_pi1[action]=show&amp;tx_blogexample_pi1[controller]=Post&amp;cHash=f982643bc3">read more &gt;&gt;</a><br>
				<a href="index.php?id=99&amp;tx_blogexample_pi1[post][uid]=211&amp;tx_blogexample_pi1[blog][uid]=70&amp;tx_blogexample_pi1[action]=edit&amp;tx_blogexample_pi1[controller]=Post&amp;cHash=5b481bc8f0">Edit</a>&nbsp;<a href="index.php?id=99&amp;tx_blogexample_pi1[post][uid]=211&amp;tx_blogexample_pi1[blog][uid]=70&amp;tx_blogexample_pi1[action]=delete&amp;tx_blogexample_pi1[controller]=Post&amp;cHash=4e52879656">Delete</a>
			</p>
		</li>
	</ul>
	<p>
		<a href="index.php?id=99&amp;tx_blogexample_pi1[blog][uid]=70&amp;tx_blogexample_pi1[action]=new&amp;tx_blogexample_pi1[controller]=Post&amp;cHash=2718a4b1a0">Create a new Post</a>
	</p>
</div>
<hr>
<p>
	© TYPO3 Association
</p>
';
		$this->assertEquals($expected, $result);
	}

	/**
	 * Converts the subject and the expected result into the target charset.
	 *
	 * @param string $charset The target charset
	 * @param string $subject The subject
	 * @param string $expected The expected result
	 * @return void
	 */
	protected function handleCharset($charset, &$subject, &$expected) {
		$GLOBALS['TSFE']->renderCharset = $charset;
		$subject = $GLOBALS['TSFE']->csConvObj->conv($subject, 'iso-8859-1', $charset);
		$expected = $GLOBALS['TSFE']->csConvObj->conv($expected, 'iso-8859-1', $charset);
	}
}
?>