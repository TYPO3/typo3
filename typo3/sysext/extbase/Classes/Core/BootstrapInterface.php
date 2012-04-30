<?php
/*                                                                        *
 * This script belongs to the Extbase framework.                          *
 *                                                                        *
 * This class is a backport of the corresponding class of FLOW3.          *
 * All credits go to the v5 team.                                         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Bootstrap Interface
 *
 * @package Extbase
 */
interface Tx_Extbase_Core_BootstrapInterface {

	/**
	 * Runs the the Extbase Framework by resolving an appropriate Request Handler and passing control to it.
	 * If the Framework is not initialized yet, it will be initialized.
	 *
	 * @param string $content The content. Not used
	 * @param array $configuration The TS configuration array
	 * @return string $content The processed content
	 * @api
	 */
	public function run($content, $configuration);

	 /**
	  * This method forwards the call to run(). This method is invoked by the mod.php
	  * function of TYPO3.
	  *
	  * @param string $moduleSignature
	  * @return boolean TRUE, if the request request could be dispatched
	  * @see run()
	  **/
	public function callModule($moduleSignature);
}
?>