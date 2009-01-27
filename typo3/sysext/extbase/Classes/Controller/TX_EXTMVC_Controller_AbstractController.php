<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/TX_EXTMVC_ControllerInterface.php');

/**
 * An abstract base class for Controllers
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
abstract class TX_EXTMVC_Controller_AbstractController implements TX_EXTMVC_ControllerInterface {

	/**
	 * @var string Key of the extension this controller belongs to
	 */
	protected $extensionKey;

	/**
	 * @var F3_FLOW3_Package_Package The extension this controller belongs to
	 */
	protected $extension;

	/**
	 * Contains the settings of the current extension
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Constructs the controller.
	 *
	 * @param F3_FLOW3_Object_FactoryInterface $objectFactory A reference to the Object Factory
	 * @param F3_FLOW3_Package_ManagerInterface $packageManager A reference to the Package Manager
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct() {
		list(, $this->extensionKey) = explode('_', get_class($this));
		// $this->extension = $packageManager->getPackage($this->extensionKey);
	}

	/**
	 * Injects the settings of the extension.
	 *
	 * @param array $settings Settings container of the current extension
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Initializes this object after all dependencies have been resolved.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeObject() {
		$this->initializeController();
	}

	/**
	 * Initializes this controller.
	 *
	 * Override this method for initializing your concrete controller implementation.
	 * Recommended actions for your controller initialization method are setting up the expected
	 * arguments and narrowing down the supported request types if neccessary.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function initializeController() {
	}
}

?>