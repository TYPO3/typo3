<?php
namespace TYPO3\CMS\Install\SystemEnvironment;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 * Interface for SystemEnvironment status
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
interface StatusInterface {

	/**
	 * @return string The severity
	 */
	public function getSeverity();

	/**
	 * @return string The title
	 */
	public function getTitle();

	/**
	 * Set title
	 *
	 * @param string $title The title
	 * @return void
	 */
	public function setTitle($title);

	/**
	 * Get status message
	 *
	 * @return string Status message
	 */
	public function getMessage();

	/**
	 * Set status message
	 *
	 * @param string $message Status message
	 * @return void
	 */
	public function setMessage($message);
}
?>