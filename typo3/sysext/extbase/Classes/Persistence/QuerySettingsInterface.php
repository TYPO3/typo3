<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3. 
*  All credits go to the v5 team.
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
 * A query settings interface. This interface is NOT part of the FLOW3 API.
 * It reflects the settings unique to TYPO3 4.x.
 *
 * @package TYPO3
 * @subpackage Extbase
 * @version $Id: QueryInterface.php 658 2009-05-16 13:54:16Z jocrau $
 */
interface Tx_Extbase_Persistence_QuerySettingsInterface {

	/**
	 * Use storage page
	 * 
	 * @param $useStoragePage if TRUE, should use storage PID. use FALSE to disable the storage Page ID checking 
	 * @return void
	 */
	public function useStoragePage($useStoragePage);
	
	/**
	 * Use enable fields
	 * 
	 * @param $useEnableFields if TRUE, will add enable fields. use FALSE to disable the enable fields checking
	 * @return void
	 */
	public function useEnableFields($useEnableFields);
}
?>