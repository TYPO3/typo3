<?php
namespace TYPO3\CMS\Core\Collection;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Steffen Ritter <typo3steffen-ritter.net>
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
 * Marker interface for a collection class with title and description
 *
 * Collections might be used internally as well as being shown
 * With the nameable interface a title and a description are added
 * to an collection, allowing every collection implementing Nameable
 * being display by the same logic.
 *
 * @author Steffen Ritter <typo3steffen-ritter.net>
 */
interface NameableCollectionInterface
{
	/**
	 * Setter for the title
	 *
	 * @param string $title
	 * @return void
	 */
	public function setTitle($title);

	/**
	 * Setter for the description
	 *
	 * @param string $description
	 * @return void
	 */
	public function setDescription($description);

	/**
	 * Getter for the title
	 *
	 * @return string
	 */
	public function getTitle();

	/**
	 * Getter for the description
	 *
	 * @return void
	 */
	public function getDescription();

}

?>