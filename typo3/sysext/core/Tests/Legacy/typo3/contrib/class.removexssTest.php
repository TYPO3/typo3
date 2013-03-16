<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2013 Steffen Kamper <info@sk-typo3.de>
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

require_once(PATH_typo3 . 'contrib/RemoveXSS/RemoveXSS.php');

/**
 * Testcase for class RemoveXSS
 *
 * @author	Steffen Kamper <info@sk-typo3.de>
 * @ see http://ha.ckers.org/xss.html
 * @ examples from http://ha.ckers.org/xssAttacks.xml
 */
class RemoveXSSTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function checkAttackScriptAlert() {
		$testString = "<SCRIPT>alert('XSS')</SCRIPT>";
		$expectedString = "<sc<x>ript>alert('XSS')</SCRIPT>";
		$actualString = RemoveXSS::process($testString);

		$this->assertEquals($expectedString, $actualString);
	}
	/**
	 * @test
	 */
	public function checkAttackScriptSrcJs() {
		$testString = '<SCRIPT SRC=http://ha.ckers.org/xss.js></SCRIPT>';
		$expectedString = "<sc<x>ript SRC=http://ha.ckers.org/xss.js></SCRIPT>";
		$actualString = RemoveXSS::process($testString);

		$this->assertEquals($expectedString, $actualString);
	}
	/**
	 * @test
	 */
	public function checkAttackScriptAlertFromCharCode() {
		$testString = '<SCRIPT>alert(String.fromCharCode(88,83,83))</SCRIPT>';
		$expectedString = '<sc<x>ript>alert(String.fromCharCode(88,83,83))</SCRIPT>';
		$actualString = RemoveXSS::process($testString);

		$this->assertEquals($expectedString, $actualString);
	}
	/**
	 * @test
	 */
	public function checkAttackBaseHref() {
		$testString = "<BASE HREF=\"javascript:alert('XSS');//\">";
		$expectedString = "<ba<x>se HREF=\"ja<x>vascript:alert('XSS');//\">";
		$actualString = RemoveXSS::process($testString);

		$this->assertEquals($expectedString, $actualString);
	}
	/**
	 * @test
	 */
	public function checkAttackBgsound() {
		$testString = "<BGSOUND SRC=\"javascript:alert('XSS');\">";
		$expectedString = "<bg<x>sound SRC=\"ja<x>vascript:alert('XSS');\">";
		$actualString = RemoveXSS::process($testString);

		$this->assertEquals($expectedString, $actualString);
	}
	/**
	 * @test
	 */
	public function checkAttackBodyBackground() {
		$testString = "<BODY BACKGROUND=\"javascript:alert('XSS');\">";
		$expectedString = "<BODY BACKGROUND=\"ja<x>vascript:alert('XSS');\">";
		$actualString = RemoveXSS::process($testString);

		$this->assertEquals($expectedString, $actualString);
	}
	/**
	 * @test
	 */
	public function checkAttackBodyOnLoad() {
		$testString = "<BODY ONLOAD=alert('XSS')>";
		$expectedString = "<BODY on<x>load=alert('XSS')>";
		$actualString = RemoveXSS::process($testString);

		$this->assertEquals($expectedString, $actualString);
	}
	/**
	 * @test
	 */
	public function checkAttackStyleUrl() {
		$testString = "<DIV STYLE=\"background-image: url(javascript:alert('XSS'))\">";
		$expectedString = "<DIV st<x>yle=\"background-image: url(ja<x>vascript:alert('XSS'))\">";
		$actualString = RemoveXSS::process($testString);

		$this->assertEquals($expectedString, $actualString);
	}
	/**
	 * @test
	 */
	public function checkAttackStyleWidth() {
		$testString = "<DIV STYLE=\"width: expression(alert('XSS'));\">";
		$expectedString = "<DIV st<x>yle=\"width: expression(alert('XSS'));\">";
		$actualString = RemoveXSS::process($testString);

		$this->assertEquals($expectedString, $actualString);
	}
	/**
	 * @test
	 */
	public function checkAttackFrameset() {
		$testString = "<FRAMESET><FRAME SRC=\"javascript:alert('XSS');\"></FRAMESET>";
		$expectedString = "<fr<x>ameset><fr<x>ame SRC=\"ja<x>vascript:alert('XSS');\"></FRAMESET>";
		$actualString = RemoveXSS::process($testString);

		$this->assertEquals($expectedString, $actualString);
	}
	/**
	 * @test
	 */
	public function checkAttackIframe() {
		$testString = "<IFRAME SRC=\"javascript:alert('XSS');\"></IFRAME>";
		$expectedString = "<if<x>rame SRC=\"ja<x>vascript:alert('XSS');\"></IFRAME>";
		$actualString = RemoveXSS::process($testString);

		$this->assertEquals($expectedString, $actualString);
	}
	/**
	 * @test
	 */
	public function checkAttackInputImage() {
		$testString = "<INPUT TYPE=\"IMAGE\" SRC=\"javascript:alert('XSS');\">";
		$expectedString = "<INPUT TYPE=\"IMAGE\" SRC=\"ja<x>vascript:alert('XSS');\">";
		$actualString = RemoveXSS::process($testString);

		$this->assertEquals($expectedString, $actualString);
	}
	/**
	 * @test
	 */
	public function checkAttackImageSrc() {
		$testString = "<IMG SRC=\"javascript:alert('XSS');\">";
		$expectedString = "<IMG SRC=\"ja<x>vascript:alert('XSS');\">";
		$actualString = RemoveXSS::process($testString);

		$this->assertEquals($expectedString, $actualString);
	}
	/**
	 * @test
	 */
	public function checkAttackImageSrcNoQuotesNoSemicolon() {
		$testString = "<IMG SRC=javascript:alert('XSS')>";
		$expectedString = "<IMG SRC=ja<x>vascript:alert('XSS')>";
		$actualString = RemoveXSS::process($testString);

		$this->assertEquals($expectedString, $actualString);
	}
	/**
	 * @test
	 */
	public function checkAttackImageDynsrc() {
		$testString = "<IMG DYNSRC=\"javascript:alert('XSS');\">";
		$expectedString = "<IMG DYNSRC=\"ja<x>vascript:alert('XSS');\">";
		$actualString = RemoveXSS::process($testString);

		$this->assertEquals($expectedString, $actualString);
	}
	/**
	 * @test
	 */
	public function checkAttackImageLowsrc() {
		$testString = "<IMG LOWSRC=\"javascript:alert('XSS');\">";
		$expectedString = "<IMG LOWSRC=\"ja<x>vascript:alert('XSS');\">";
		$actualString = RemoveXSS::process($testString);

		$this->assertEquals($expectedString, $actualString);
	}
	/**
	 * @test
	 */
	public function checkAttackStyle() {
		$testString = "<STYLE>li {list-style-image: url(\"javascript:alert('XSS')\");}</STYLE>";
		$expectedString = "<st<x>yle>li {list-style-image: url(\"ja<x>vascript:alert('XSS')\");}</STYLE>";
		$actualString = RemoveXSS::process($testString);

		$this->assertEquals($expectedString, $actualString);
	}
	/**
	 * @test
	 */
	public function checkAttackImageVbscript() {
		$testString = "<IMG SRC='vbscript:msgbox(\"XSS\")'>";
		$expectedString = "<IMG SRC='vb<x>script:msgbox(\"XSS\")'>";
		$actualString = RemoveXSS::process($testString);

		$this->assertEquals($expectedString, $actualString);
	}
	/**
	 * @test
	 */
	public function checkAttackLayer() {
		$testString = "<LAYER SRC=\"http://ha.ckers.org/scriptlet.html\"></LAYER>";
		$expectedString = "<la<x>yer SRC=\"http://ha.ckers.org/scriptlet.html\"></LAYER>";
		$actualString = RemoveXSS::process($testString);

		$this->assertEquals($expectedString, $actualString);
	}
	/**
	 * @test
	 */
	public function checkAttackMeta() {
		$testString = '<META HTTP-EQUIV="refresh" CONTENT="0;url=javascript:alert(\'XSS\');">';
		$expectedString = '<me<x>ta HTTP-EQUIV="refresh" CONTENT="0;url=ja<x>vascript:alert(\'XSS\');">';
		$actualString = RemoveXSS::process($testString);

		$this->assertEquals($expectedString, $actualString);
	}
	/**
	 * @test
	 */
	public function checkAttackMetaWithUrl() {
		$testString = '<META HTTP-EQUIV="refresh" CONTENT="0;url=data:text/html;base64,PHNjcmlwdD5hbGVydCgnWFNTJyk8L3NjcmlwdD4K">';
		$expectedString = '<me<x>ta HTTP-EQUIV="refresh" CONTENT="0;url=data:text/html;base64,PHNjcmlwdD5hbGVydCgnWFNTJyk8L3NjcmlwdD4K">';
		$actualString = RemoveXSS::process($testString);

		$this->assertEquals($expectedString, $actualString);
	}
	/**
	 * @test
	 */
	public function checkAttackMetaWithUrlExtended() {
		$testString = '<META HTTP-EQUIV="refresh" CONTENT="0; URL=http://;URL=javascript:alert(\'XSS\');">';
		$expectedString = '<me<x>ta HTTP-EQUIV="refresh" CONTENT="0; URL=http://;URL=ja<x>vascript:alert(\'XSS\');">';
		$actualString = RemoveXSS::process($testString);

		$this->assertEquals($expectedString, $actualString);
	}
	/**
	 * @test
	 */
	public function checkAttackObject() {
		$testString = '<OBJECT TYPE="text/x-scriptlet" DATA="http://ha.ckers.org/scriptlet.html"></OBJECT>';
		$expectedString = '<ob<x>ject TYPE="text/x-scriptlet" DATA="http://ha.ckers.org/scriptlet.html"></OBJECT>';
		$actualString = RemoveXSS::process($testString);

		$this->assertEquals($expectedString, $actualString);
	}
	/**
	 * @test
	 */
	public function checkAttackObjectEmbeddedXss() {
		$testString = '<OBJECT classid=clsid:ae24fdae-03c6-11d1-8b76-0080c744f389><param name=url value=javascript:alert(\'XSS\')></OBJECT>';
		$expectedString = '<ob<x>ject classid=clsid:ae24fdae-03c6-11d1-8b76-0080c744f389><param name=url value=ja<x>vascript:alert(\'XSS\')></OBJECT>';
		$actualString = RemoveXSS::process($testString);

		$this->assertEquals($expectedString, $actualString);
	}
	/**
	 * @test
	 */
	public function checkAttackEmbedFlash() {
		$testString = '<EMBED SRC="http://ha.ckers.org/xss.swf" AllowScriptAccess="always"></EMBED>';
		$expectedString = '<em<x>bed SRC="http://ha.ckers.org/xss.swf" AllowScriptAccess="always"></EMBED>';
		$actualString = RemoveXSS::process($testString);

		$this->assertEquals($expectedString, $actualString);
	}
	/**
	 * @test
	 */
	public function checkAttackActionScriptEval() {
		$testString = 'a="get";b="URL("";c="javascript:";d="alert(\'XSS\');")";eval(a+b+c+d);";';
		$expectedString = 'a="get";b="URL("";c="ja<x>vascript:";d="alert(\'XSS\');")";eval(a+b+c+d);";';
		$actualString = RemoveXSS::process($testString);

		$this->assertEquals($expectedString, $actualString);
	}
	/**
	 * @test
	 */
	public function checkAttackImageStyleWithComment() {
		$testString = '<IMG STYLE="xss:expr/*XSS*/ession(alert(\'XSS\'))">';
		$expectedString = '<IMG st<x>yle="xss:expr/*XSS*/ession(alert(\'XSS\'))">';
		$actualString = RemoveXSS::process($testString);

		$this->assertEquals($expectedString, $actualString);
	}
	/**
	 * @test
	 */
	public function checkAttackStyleInAnonymousHtml() {
		$testString = '<XSS STYLE="xss:expression(alert(\'XSS\'))">';
		$expectedString = '<XSS st<x>yle="xss:expression(alert(\'XSS\'))">';
		$actualString = RemoveXSS::process($testString);

		$this->assertEquals($expectedString, $actualString);
	}
	/**
	 * @test
	 */
	public function checkAttackStyleWithBackgroundImage() {
		$testString = '<STYLE>.XSS{background-image:url("javascript:alert(\'XSS\')");}</STYLE><A CLASS=XSS></A>';
		$expectedString = '<st<x>yle>.XSS{background-image:url("ja<x>vascript:alert(\'XSS\')");}</STYLE><A CLASS=XSS></A>';
		$actualString = RemoveXSS::process($testString);

		$this->assertEquals($expectedString, $actualString);
	}
	/**
	 * @test
	 */
	public function checkAttackStyleWithBackground() {
		$testString = '<STYLE type="text/css">BODY{background:url("javascript:alert(\'XSS\')")}</STYLE>';
		$expectedString = '<st<x>yle type="text/css">BODY{background:url("ja<x>vascript:alert(\'XSS\')")}</STYLE>';
		$actualString = RemoveXSS::process($testString);

		$this->assertEquals($expectedString, $actualString);
	}
	/**
	 * @test
	 */
	public function checkAttackStylesheet() {
		$testString = '<LINK REL="stylesheet" HREF="javascript:alert(\'XSS\');">';
		$expectedString = '<li<x>nk REL="stylesheet" HREF="ja<x>vascript:alert(\'XSS\');">';
		$actualString = RemoveXSS::process($testString);

		$this->assertEquals($expectedString, $actualString);
	}
	/**
	 * @test
	 */
	public function checkAttackRemoteStylesheet() {
		$testString = '<LINK REL="stylesheet" HREF="http://ha.ckers.org/xss.css">';
		$expectedString = '<li<x>nk REL="stylesheet" HREF="http://ha.ckers.org/xss.css">';
		$actualString = RemoveXSS::process($testString);

		$this->assertEquals($expectedString, $actualString);
	}
	/**
	 * @test
	 */
	public function checkAttackImportRemoteStylesheet() {
		$testString = '<STYLE>@import\'http://ha.ckers.org/xss.css\';</STYLE>';
		$expectedString = '<st<x>yle>@import\'http://ha.ckers.org/xss.css\';</STYLE>';
		$actualString = RemoveXSS::process($testString);

		$this->assertEquals($expectedString, $actualString);
	}

	/**
	 * @return array<array> input strings and expected output strings to test
	 *
	 * @see processWithDataProvider
	 */
	public function processDataProvider() {
		return array(
			'attackWithHexEncodedCharacter' => array(
				'<a href="j&#x61;vascript:alert(123);">click</a>',
				'<a href="ja<x>vascript:alert(123);">click</a>',
			),
			'attackWithNestedHexEncodedCharacter' => array(
				'<a href="j&#x6&#x31;;vascript:alert(123);">click</a>',
				'<a href="ja<x>vascript:alert(123);">click</a>',
			),
			'attackWithUnicodeNumericalEncodedCharacter' => array(
				'<a href="j&#x6&#x31;;vascript:alert(123);">click</a>',
				'<a href="ja<x>vascript:alert(123);">click</a>',
			),
			'attackWithNestedUnicodeNumericalEncodedCharacter' => array(
				'<a href="j&#6&#53;;vascript:alert(123);">click</a>',
				'<a href="ja<x>vascript:alert(123);">click</a>',
			),
			'attack with null character' => array(
				'<scr' . chr(0) . 'ipt></script>',
				'<sc<x>ript></script>'
			),
			'attack with null character in attribute' => array(
				'<a href="j' . chr(0) . 'avascript:alert(123);"></a>',
				'<a href="ja<x>vascript:alert(123);"></a>'
			),
		);
	}

	/**
	 * @test
	 *
	 * @param string $input input value to test
	 * @param string $expected expected output value
	 *
	 * @dataProvider processDataProvider
	 */
	public function processWithDataProvider($input, $expected) {
		$this->assertEquals(
			$expected,
			RemoveXSS::process($input)
		);
	}

	/**
	 * Allowed combinations
	 */
	public function processValidDataProvider() {
		return array(
			'multibyte characters' => array(
				'<img®€ÜüÖöÄä></img>',
			),
			'tab' => array(
				'<im' . chr(9) . 'g></img>',
			),
			'line feed' => array(
				'<im' . chr(10) . 'g></img>',
			),
			'carriage return' => array(
				'<im' . chr(13) . 'g></img>',
			),
		);
	}

	/**
	 * @test
	 * @param string $input Value to test
	 * @dataProvider processValidDataProvider
	 */
	public function proccessValidStrings($input) {
		$this->assertEquals(
			$input,
			RemoveXSS::process($input)
		);
	}
}
?>