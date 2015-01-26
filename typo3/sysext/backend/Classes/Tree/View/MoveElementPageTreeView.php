<?php
namespace TYPO3\CMS\Backend\Tree\View;

/*
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
 * Local extension of the page tree class used inside MoveElementController / move_el.php
 *
 */
class MoveElementPageTreeView extends PageTreeView {

	/**
	 * Inserting uid-information in title-text for an icon
	 *
	 * @param string $icon Icon image
	 * @param array $row Item row
	 * @return string Wrapping icon image.
	 */
	public function wrapIcon($icon, $row) {
		return $this->addTagAttributes($icon, ' title="id=' . htmlspecialchars($row['uid']) . '"');
	}

}
