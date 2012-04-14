<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012
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
 * A repository for extensions
 */
class Tx_Extensionmanager_Domain_Repository_ExtensionRepository extends Tx_Extbase_Persistence_Repository {

	public function countAll() {
		$query = $this->createQuery();
		$query = $this->addDefaultConstraints($query);
		return $query->execute()->count();
	}

	public function findAll() {
		$query = $this->createQuery();
		$query = $this->addDefaultConstraints($query);
		return $query->execute();
	}

	public function findByTitleOrAuthorNameOrExtensionKey($searchString) {
		$searchString = '%' . $searchString . '%';
		$query = $this->createQuery();
		$query->matching(
			$query->logicalOr(
				$query->like('extensionKey', $searchString),
				$query->like('title', $searchString),
				$query->like('authorName', $searchString)
			)
		);
		$query = $this->addDefaultConstraints($query);
		return $query->execute();
	}

	protected function addDefaultConstraints(Tx_Extbase_Persistence_Query $query) {
		if($query->getConstraint()) {
			$query->matching(
				$query->logicalAnd(
					$query->getConstraint(),
					$query->equals('lastversion', TRUE)
				)
			);
		} else {
			$query->matching(
				$query->equals('lastversion', TRUE)
			);
		}
		return $query;
	}
}
?>
