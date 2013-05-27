<?php
namespace TYPO3\CMS\Fluid;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */
class Fluid {

	const LEGACY_NAMESPACE_SEPARATOR = '_';
	const NAMESPACE_SEPARATOR = '\\';

	/**
	 * Can be used to enable the verbose mode of Fluid.
	 *
	 * This enables the following things:
	 * - ViewHelper argument descriptions are being parsed from the PHPDoc
	 *
	 * This is NO PUBLIC API and the way this mode is enabled might change without
	 * notice in the future.
	 *
	 * @var boolean
	 */
	static public $debugMode = FALSE;
}

?>