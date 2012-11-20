<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence;

/***************************************************************
 *  Copyright notice
 *
 *  This class is a backport of the corresponding class of TYPO3 Flow.
 *  All credits go to the TYPO3 Flow team.
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
class SessionTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function newSessionIsEmpty() {
		$persistenceSession = new \TYPO3\CMS\Extbase\Persistence\Generic\Session();
		$reconstitutedObjects = $persistenceSession->getReconstitutedObjects();
		$this->assertEquals(0, count($reconstitutedObjects), 'The reconstituted objects storage was not empty.');
	}

	/**
	 * @test
	 */
	public function objectCanBeRegisteredAsReconstituted() {
		$persistenceSession = new \TYPO3\CMS\Extbase\Persistence\Generic\Session();
		$entity = $this->getMock('TYPO3\\CMS\\Extbase\\DomainObject\\AbstractEntity');
		$persistenceSession->registerReconstitutedObject($entity);
		$reconstitutedObjects = $persistenceSession->getReconstitutedObjects();
		$this->assertTrue($reconstitutedObjects->contains($entity), 'The object was not registered as reconstituted.');
	}

	/**
	 * @test
	 */
	public function objectCanBeUnregisteredAsReconstituted() {
		$persistenceSession = new \TYPO3\CMS\Extbase\Persistence\Generic\Session();
		$entity = $this->getMock('TYPO3\\CMS\\Extbase\\DomainObject\\AbstractEntity');
		$persistenceSession->registerReconstitutedObject($entity);
		$persistenceSession->unregisterReconstitutedObject($entity);
		$reconstitutedObjects = $persistenceSession->getReconstitutedObjects();
		$this->assertEquals(0, count($reconstitutedObjects), 'The reconstituted objects storage was not empty.');
	}
}

?>