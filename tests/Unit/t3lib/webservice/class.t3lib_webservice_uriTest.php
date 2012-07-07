<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Thomas Maroschik <tmaroschik@dfau.de>
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
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Enter descriptions here
 *
 * @package $PACKAGE$
 * @subpackage $SUBPACKAGE$
 * @scope prototype
 * @entity
 * @api
 */
class t3lib_webservice_UriTest extends Tx_Phpunit_TestCase {

	/**
	 * Checks if a complete URI with all parts is transformed into an object correctly.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function constructorParsesAFullBlownUriStringCorrectly() {
		$uriString = 'http://username:password@subdomain.domain.com:8080/path1/path2/index.php?argument1=value1&argument2=value2&argument3[subargument1]=subvalue1#anchor';
		$uri = new t3lib_webservice_Uri($uriString);

		$check = (
			$uri->getScheme() == 'http' &&
			$uri->getUsername() == 'username' &&
			$uri->getPassword() == 'password' &&
			$uri->getHost() == 'subdomain.domain.com' &&
			$uri->getPort() === 8080 &&
			$uri->getPath() == '/path1/path2/index.php' &&
			$uri->getQuery() == 'argument1=value1&argument2=value2&argument3[subargument1]=subvalue1' &&
			$uri->getArguments() == array('argument1' => 'value1', 'argument2' => 'value2', 'argument3' => array('subargument1' => 'subvalue1')) &&
			$uri->getFragment() == 'anchor'
		);
		$this->assertTrue($check, 'The valid and complete URI has not been correctly transformed to an URI object');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function constructorParsesArgumentsWithSpecialCharactersCorrectly() {
		$uriString = 'http://www.typo3.com/path1/?argumentäöü1=' . urlencode('valueåø€œ');
		$uri = new t3lib_webservice_Uri($uriString);

		$check = (
			$uri->getScheme() == 'http' &&
			$uri->getHost() == 'www.typo3.com' &&
			$uri->getPath() == '/path1/' &&
			$uri->getQuery() == 'argumentäöü1=value%C3%A5%C3%B8%E2%82%AC%C5%93' &&
			$uri->getArguments() == array('argumentäöü1' => 'valueåø€œ')
		);
		$this->assertTrue($check, 'The URI with special arguments has not been correctly transformed to an URI object');
	}

	/**
	 * Checks if a complete URI with all parts is transformed into an object correctly.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function stringRepresentationIsCorrect() {
		$uriString = 'http://username:password@subdomain.domain.com:1234/pathx1/pathx2/index.php?argument1=value1&argument2=value2&argument3[subargument1]=subvalue1#anchorman';
		$uri = new t3lib_webservice_Uri($uriString);
		$this->assertEquals($uriString, (string)$uri, 'The string representation of the URI is not equal to the original URI string.');
	}

}

?>