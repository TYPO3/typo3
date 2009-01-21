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

/**
 * An abstract base class for Controllers
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
abstract class TX_EXTMVC_Controller_AbstractController {

	/**
	 * @var F3_FLOW3_Object_FactoryInterface A reference to the Object Factory
	 */
	protected $objectFactory;

	/**
	 * @var string Key of the package this controller belongs to
	 */
	protected $extensionKey;

	/**
	 * @var F3_FLOW3_Package_Package The package this controller belongs to
	 */
	protected $package;

	/**
	 * Contains the settings of the current package
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
	public function __construct(F3_FLOW3_Object_FactoryInterface $objectFactory, F3_FLOW3_Package_ManagerInterface $packageManager) {
		$this->objectFactory = $objectFactory;
		list(, $this->extensionKey) = explode('\\', get_class($this));
		// $this->package = $packageManager->getPackage($this->extensionKey);
	}

	/**
	 * Sets / injects the settings of the package this controller belongs to.
	 *
	 * @param array $settings Settings container of the current package
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setSettings(array $settings) {
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