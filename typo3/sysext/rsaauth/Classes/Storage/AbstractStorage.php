<?php
namespace TYPO3\CMS\Rsaauth\Storage;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Dmitry Dulepov <dmitry@typo3.org>
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
 * This class contains the abstract storage for the RSA private keys
 *
 * @author Dmitry Dulepov <dmitry@typo3.org>
 */
abstract class AbstractStorage {

	/**
	 * Retrieves the key from the storage
	 *
	 * @return string The key or NULL
	 */
	abstract public function get();

	/**
	 * Stores the key in the storage
	 *
	 * @param string $key The key
	 */
	abstract public function put($key);

}


?>