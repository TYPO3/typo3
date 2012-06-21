<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2012 Steffen Ritter <steffen.ritter@typo3.org>
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
 * Data-Layer representing the Clipboard of TYPO3 Backend
 *
 * @author Steffen Ritter <steffen-ritter@typo3.org>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_clipboard_Clipboard implements t3lib_Singleton {

	/**
	 * Is clipboard is perstisted after logout
	 *
	 * @var boolean
	 */
	public static $doCrossSessionPersistence = TRUE;

	/**
	 * How many "pads" will the clipboard provide
	 *
	 * @var integer
	 */
	protected $numberOfPads = 3;

	/**
	 * Is locked to pad zero (aka NORMAL)
	 *
	 * @var boolean
	 */
	protected $lockedToNormal = FALSE;

	/**
	 * Storage for display-specific options
	 *
	 * @var array
	 */
	protected $renderData = array();

	/**
	 * Reference to the current pad
	 *
	 * @var t3lib_clipboard_Pad
	 */
	protected $currentPad = NULL;


	/**
	 * Constructor
	 */
	public function __construct() {
		self::$doCrossSessionPersistence = $GLOBALS['BE_USER']->getTSConfigVal('options.saveClipboard');

			// Get the number of pads from user TS
		$configuredAmount = $GLOBALS['BE_USER']->getTSConfigVal('options.clipboardNumberPads');
		if (t3lib_utility_Math::canBeInterpretedAsInteger($configuredAmount) && $configuredAmount >= 0) {
			$this->numberOfPads = t3lib_utility_Math::forceIntegerInRange($configuredAmount, 0, 20);
		}

		$moduleData = $this->getData();
		$this->currentPad = $this->createPad(intval($moduleData['current']));
		$this->renderData = $moduleData['renderData'];
	}

	/**
	 * Returns a clipboard Pad
	 * Ensures that the pad is not higher than allowed,
	 * else Default ClipBoardPad will be returned
	 *
	 * @param integer $id
	 * @return t3lib_clipboard_Pad
	 */
	protected function createPad($id) {
		return t3lib_clipboard_Pad::load(t3lib_utility_Math::forceIntegerInRange($id, 0, $this->numberOfPads - 1, 0));
	}

	/**
	 * Switch the current pad
	 *
	 * @param integer $newPadId
	 * @return void
	 */
	public function switchPad($newPadId) {
		if (!$this->lockedToNormal) {
			$this->currentPad = $this->createPad($newPadId);
		}
	}

	/**
	 * Locks the ClipBoard to the Default ClipBoard,
	 * switches to that one, if not there, too.
	 */
	public function lockToNormal() {
		$this->lockedToNormal = TRUE;
		$this->currentPad = $this->createPad(0);
	}

	/**
	 * Persists the clipboard to the storage
	 *
	 * @return void
	 */
	public function persist() {
		for ($i = 0; $i < $this->numberOfPads; $i++) {
			$this->createPad($i)->persist();
		}
		$moduleData = $this->getData();
		$moduleData['current'] = $this->getActivePadId();
		$moduleData['renderData'] = $this->renderData;
		$this->setData($moduleData);
	}

	/**
	 * Returns the current active clipboard Pad
	 *
	 * @param integer $id the id of the pad which should be returned. If none given the current is returned
	 * @return t3lib_clipboard_Pad
	 */
	public function getPad($id = -1) {
		if ($id === -1) {
			return $this->currentPad;
		} else {
			return $this->createPad(intval($id));
		}
	}

	/**
	 * Checks if a property for display purposes is set
	 *
	 * @param string $property
	 * @return boolean
	 */
	public function hasDispplayProperty($property) {
		return isset($this->renderData[$property]);
	}

	/**
	 * Returns a property for display purposes if set, otherwise NULL
	 *
	 * @param string $property
	 * @return mixed
	 */
	public function getDisplayProperty($property) {
		return $this->renderData[$property];
	}

	/**
	 * Sets a value of a display related property
	 *
	 * @param string $property
	 * @param mixed $value
	 * @return void
	 */
	public function setDisplayProperty($property, $value) {
		$this->renderData[$property] = $value;
	}

	/**
	 * Returns the identifier of the active Pad
	 *
	 * @return integer
	 */
	public function getActivePadId() {
		return $this->currentPad->getIdentifier();
	}

	/**
	 * Sets the amount of the maximum available ClipBoardPads
	 *
	 * @param integer $numberOfPads
	 */
	public function setNumberOfPads($numberOfPads) {
		$this->numberOfPads = $numberOfPads;
	}

	/**
	 * Returns the configured amount of available ClipBoardPads
	 *
	 * @return integer
	 */
	public function getNumberOfPads() {
		return $this->numberOfPads;
	}

	/**
	 * Loads the stored clipboard data.
	 *
	 * If clipboard is persisted between logins, Data is recieved from
	 * UC configuration, otherwise it is resolved from the session data.
	 *
	 * @return array
	 */
	protected function getData() {
		if (t3lib_clipboard_Clipboard::$doCrossSessionPersistence) {
			return (array)$GLOBALS['BE_USER']->uc['moduleData']['clipboard'];
		} else {
			return (array)$sessionData = $GLOBALS['BE_USER']->getSessionData('clipboard');
		}
	}

	/**
	 * Writes the clipboard data to the underlying persistence
	 *
	 * If crossSessionPersistence is activated, it's saved to the UC,
	 * otherwise to the session.
	 *
	 * @param array $array
	 * @return void
	 */
	protected function setData(array $array) {
		if (t3lib_clipboard_Clipboard::$doCrossSessionPersistence) {
			$GLOBALS['BE_USER']->uc['moduleData']['clipboard'] = $array;
		} else {
			$GLOBALS['BE_USER']->setAndSaveSessionData('clipboard', $array);
		}
	}

}

?>