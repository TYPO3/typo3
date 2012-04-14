<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Marcus Krause <marcus#exp2010@t3sec.info>
 *		   Steffen Kamper <info@sk-typo3.de>
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
 * Repository mirrors object for extension manager.
 *
 * @author	  Marcus Krause <marcus#exp2010@t3sec.info>
 * @author	  Steffen Kamper <info@sk-typo3.de>
 *
 * @since	   2010-02-11
 * @package	 TYPO3
 * @subpackage  EM
 */
class Tx_Extensionmanager_Domain_Model_Mirrors extends Tx_Extbase_DomainObject_AbstractEntity  {

	/**
	 * Keeps mirrors.
	 *
	 * @var  array
	 */
	protected $mirrors = array();

	/**
	 * Keeps currently select mirror.
	 *
	 * Is array index.
	 *
	 * @var  integer
	 */
	protected $currentMirror;

	/**
	 * Keeps information if a mirror should
	 * be randomly selected.
	 *
	 * @var  boolean
	 */
	protected $isRandomSelection = TRUE;

	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @param Tx_Extbase_Object_ObjectManagerInterface $objectManager
	 * @return Tx_Extbase_Object_ObjectManagerInterface
	 */
	public function injectObjectManager(Tx_Extbase_Object_ObjectManagerInterface $objectManager) {
		return $this->objectManager = $objectManager;
	}

	/**
	 * Method selects one specific mirror to be used.
	 *
	 * @access  public
	 * @param   integer  $mirrorID  an order number (>=1) of mirror or NULL for random selection
	 * @return  void
	 * @see	 $currentMirror
	 */
	public function setSelect($mirrorID = NULL) {
		if (is_null($mirrorID)) {
			$this->isRandomSelection = TRUE;
		} else {
			if (is_int($mirrorID) && $mirrorID >= 1 && $mirrorID <= count($this->mirrors)) {
				$this->currentMirror = $mirrorID - 1;
			}
		}
	}

	/**
	 * Method returns one mirror for use.
	 *
	 * Mirror has previously been selected or is chosen
	 * randomly.
	 *
	 * @access  public
	 * @return  array  array of a mirror's properties or NULL in case of errors
	 */
	public function getMirror() {
		$sumMirrors = count($this->mirrors);
		if ($sumMirrors > 0) {
			if (!is_int($this->currentMirror)) {
				$this->currentMirror = rand(0, $sumMirrors - 1);
			}
			return $this->mirrors[$this->currentMirror];
		}
		return NULL;
	}

	/**
	 * Gets the mirror url from selected mirror
	 *
	 * @return string
	 */
	public function getMirrorUrl() {
		$mirror = $this->getMirror();
		$mirrorUrl = $mirror['host'] . $mirror['path'];

		return 'http://' . $mirrorUrl;
	}

	/**
	 * Method returns all available mirrors.
	 *
	 * @access  public
	 * @return  array  multidimensional array with mirrors and their properties
	 * @see	 $mirrors, setMirrors()
	 */
	public function getMirrors() {
		return $this->mirrors;
	}

	/**
	 * Method sets available mirrors.
	 *
	 * @access  public
	 * @param   array  $mirrors  multidimensional array with mirrors and their properties to set
	 * @return  void
	 * @see	 $mirrors, getMirrors()
	 */
	public function setMirrors(array $mirrors) {
		if (count($mirrors) >= 1) {
			$this->mirrors = $mirrors;
		}
	}
}

?>