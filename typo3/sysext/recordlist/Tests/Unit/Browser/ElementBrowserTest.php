<?php
namespace TYPO3\CMS\Recordlist\Tests\Unit\Browser;

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
 * Test case
 */
class ElementBrowserTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function printCurrentUrlWithAnyTextReturnsThatText() {
		$GLOBALS['LANG'] = $this->getMock('TYPO3\\CMS\\Lang\\LanguageService', array(), array(), '', FALSE);
		$subject = new \TYPO3\CMS\Recordlist\Browser\ElementBrowser();
		$subject->act = 'file';
		$result = $subject->printCurrentUrl('Teststring');
		$this->assertContains('Teststring', $result);
	}
}
