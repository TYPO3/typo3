<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2012 Andreas Wolf <andreas.wolf@ikt-werk.de>
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
 * A dispatcher which dispatches signals by calling its registered slot methods
 * and passing them the method arguments which were originally passed to the
 * signal method.
 *
 * @api
 */
class t3lib_SignalSlot_Dispatcher implements t3lib_Singleton {

	/**
	 * Information about all slots connected a certain signal.
	 * Indexed by [$signalClassName][$signalMethodName] and then numeric with an
	 * array of information about the slot
	 * @var array
	 */
	protected $slots = array();

	/**
	 * Gets a singleton instance of this class.
	 *
	 * @return t3lib_SignalSlot_Dispatcher
	 */
	public static function getInstance() {
		return t3lib_div::makeInstance('t3lib_SignalSlot_Dispatcher');
	}

	/**
	 * Connects a signal with a slot.
	 * One slot can be connected with multiple signals by calling this method multiple times.
	 *
	 * @param string $signalClassName Name of the class containing the signal
	 * @param string $signalName Name of the signal
	 * @param mixed $slotClassNameOrObject Name of the class containing the slot or the instantiated class or a Closure object
	 * @param string $slotMethodName Name of the method to be used as a slot. If $slotClassNameOrObject is a Closure object, this parameter is ignored
	 * @param boolean $passSignalInformation If set to TRUE, the last argument passed to the slot will be information about the signal (EmitterClassName::signalName)
	 * @return void
	 * @api
	 */
	public function connect($signalClassName, $signalName, $slotClassNameOrObject, $slotMethodName = '', $passSignalInformation = TRUE) {
		$class = NULL;
		$object = NULL;

		if (strpos($signalName, 'emit') === 0) {
			$possibleSignalName = lcfirst(substr($signalName, strlen('emit')));
			throw new \InvalidArgumentException('The signal should not be connected with the method name ("' . $signalName .  '"). Try "' . $possibleSignalName . '" for the signal name.', 1314016630);
		}

		if (is_object($slotClassNameOrObject)) {
			$object = $slotClassNameOrObject;
			$method = ($slotClassNameOrObject instanceof \Closure) ? '__invoke' : $slotMethodName;
		} else {
			if ($slotMethodName === '') throw new \InvalidArgumentException('The slot method name must not be empty (except for closures).', 1229531659);
			$class = $slotClassNameOrObject;
			$method = $slotMethodName;
		}

		$this->slots[$signalClassName][$signalName][] = array(
			'class' => $class,
			'method' => $method,
			'object' => $object,
			'passSignalInformation' => ($passSignalInformation === TRUE)
		);
	}

	/**
	 * Dispatches a signal by calling the registered Slot methods
	 *
	 * @param string $signalClassName Name of the class containing the signal
	 * @param string $signalName Name of the signal
	 * @param array $signalArguments arguments passed to the signal method
	 * @return void
	 * @throws t3lib_SignalSlot_InvalidSlotException if the slot is not valid
	 * @api
	 */
	public function dispatch($signalClassName, $signalName, array $signalArguments = array()) {
		if (!isset($this->slots[$signalClassName][$signalName])) {
			return;
		}

		foreach ($this->slots[$signalClassName][$signalName] as $slotInformation) {
			if (isset($slotInformation['object'])) {
				$object = $slotInformation['object'];
			} else {
				if (!class_exists($slotInformation['class'], TRUE)) {
					throw new t3lib_SignalSlot_InvalidSlotException('The given class "' . $slotInformation['class'] . '" does not exist.', 1319136841);
				}
				$object = t3lib_div::makeInstance($slotInformation['class']);
			}
			if ($slotInformation['passSignalInformation'] === TRUE) {
				$signalArguments[] = $signalClassName . '::' . $signalName;
			}
			if (!method_exists($object, $slotInformation['method'])) {
				throw new t3lib_SignalSlot_InvalidSlotException('The slot method ' . get_class($object) . '->' . $slotInformation['method'] . '() does not exist.', 1245673368);
			}
			call_user_func_array(array($object, $slotInformation['method']), $signalArguments);
		}
	}

	/**
	 * Returns all slots which are connected with the given signal
	 *
	 * @param string $signalClassName Name of the class containing the signal
	 * @param string $signalName Name of the signal
	 * @return array An array of arrays with slot information
	 * @api
	 */
	public function getSlots($signalClassName, $signalName) {
		return (isset($this->slots[$signalClassName][$signalName])) ? $this->slots[$signalClassName][$signalName] : array();
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/signalslot/Dispatcher.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/signalslot/Dispatcher.php']);
}

?>