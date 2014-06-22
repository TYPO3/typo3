<?php
namespace TYPO3\CMS\Extbase\Core;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Interface for module runners that can execute requests to Extbase controllers in the backend
 */
interface ModuleRunnerInterface {
	/**
	 * Initializes and runs a module.
	 *
	 * @param string $moduleSignature
	 * @throws \RuntimeException
	 * @return boolean TRUE, if the request request could be dispatched
	 * @see run()
	 */
	public function callModule($moduleSignature);
}
