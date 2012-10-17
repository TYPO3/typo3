<?php
/*                                                                        *
 * This script is backported from the FLOW3 package "TYPO3.Fluid".        *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
require_once dirname(__FILE__) . '/../ViewHelperBaseTestcase.php';
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Uri;

/**
 * Testcase for the email uri view helper
 */
class EmailViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase {

	/**
	 * @var \TYPO3\CMS\Fluid\ViewHelpers\Uri\EmailViewHelper
	 */
	protected $viewHelper;

	/**
	 * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	protected $cObjBackup;

	public function setUp() {
		parent::setUp();
		$this->cObjBackup = $GLOBALS['TSFE']->cObj;
		$GLOBALS['TSFE']->cObj = $this->getMock('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer', array(), array(), '', FALSE);
		$this->viewHelper = new \Tx_Fluid_ViewHelpers_Uri_EmailViewHelper();
		$this->injectDependenciesIntoViewHelper($this->viewHelper);
		$this->viewHelper->initializeArguments();
	}

	public function tearDown() {
		$GLOBALS['TSFE']->cObj = $this->cObjBackup;
	}

	/**
	 * @test
	 */
	public function renderReturnsFirstResultOfGetMailTo() {
		#$GLOBALS['TSFE']->cObj->expects($this->once())->method('getMailTo')->with('some@email.tld', 'some@email.tld')->will($this->returnValue(array('mailto:some@email.tld', 'some@email.tld')));
		$this->viewHelper->initialize();
		$actualResult = $this->viewHelper->render('some@email.tld');
		$this->assertEquals('mailto:some@email.tld', $actualResult);
	}

}


?>