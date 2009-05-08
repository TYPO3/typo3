<?php

require_once(t3lib_extMgm::extPath('extbase', 'class.tx_extbase_dispatcher.php')); spl_autoload_register(array(t3lib_div::makeInstance('Tx_Extbase_Dispatcher'), 'autoLoadClasses'));
class Tx_Fluid_Core_Fixtures_TestViewHelper extends Tx_Fluid_Core_AbstractViewHelper {

	/**
	 * My comments. Bla blubb.
	 *
	 * @param integer $param1 P1 Stuff
	 * @param array $param2 P2 Stuff
	 * @param string $param3 P3 Stuff
	 */
	public function render($param1, array $param2, $param3 = "default") {

	}
}


?>