<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\View;

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
 * @package FLOW3
 * @subpackage MVC
 * @version $Id: F3_FLOW3_MVC_View_AbstractView.php 1749 2009-01-15 15:06:30Z k-fish $
 */

/**
 * An abstract View
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id: F3_FLOW3_MVC_View_AbstractView.php 1749 2009-01-15 15:06:30Z k-fish $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
abstract class AbstractView {

	/**
	 * @var \F3\FLOW3\Object\FactoryInterface A reference to the Object Factory
	 */
	protected $objectFactory;

	/**
	 * @var \F3\FLOW3\Package\FactoryInterface
	 */
	protected $packageManager;

	/**
	 * @var \F3\FLOW3\Resource\ManagerInterface
	 */
	protected $resourceManager;

	/**
	 * @var \F3\FLOW3\Object\ManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\MVC\Request
	 */
	protected $request;

	/**
	 * @var array of \F3\FLOW3\MVC\View\Helper\HelperInterface
	 */
	protected $viewHelpers;

	/**
	 * Constructs the view.
	 *
	 * @param \F3\FLOW3\Object\FactoryInterface $objectFactory A reference to the Object Factory
	 * @param \F3\FLOW3\Package\ManagerInterface $packageManager A reference to the Package Manager
	 * @param \F3\FLOW3\Resource\Manager $resourceManager A reference to the Resource Manager
	 * @param \F3\FLOW3\Object\ManagerInterface $objectManager A reference to the Object Manager
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(\F3\FLOW3\Object\FactoryInterface $objectFactory, \F3\FLOW3\Package\ManagerInterface $packageManager, \F3\FLOW3\Resource\Manager $resourceManager, \F3\FLOW3\Object\ManagerInterface $objectManager) {
		$this->objectFactory = $objectFactory;
		$this->objectManager = $objectManager;
		$this->packageManager = $packageManager;
		$this->resourceManager = $resourceManager;
	}

	/**
	 * Initializes the view after all dependencies have been injected
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeObject() {
		$this->initializeView();
	}

	/**
	 * Sets the current request
	 *
	 * @param \F3\FLOW3\MVC\Request $request
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setRequest(\F3\FLOW3\MVC\Request $request) {
		$this->request = $request;
	}

	/**
	 * Returns an View Helper instance.
	 * View Helpers must implement the interface \F3\FLOW3\MVC\View\Helper\HelperInterface
	 *
	 * @param string $viewHelperClassName the full name of the View Helper Class including namespace
	 * @return \F3\FLOW3\MVC\View\Helper\HelperInterface The View Helper instance
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getViewHelper($viewHelperClassName) {
		if (!isset($this->viewHelpers[$viewHelperClassName])) {
			$viewHelper = $this->objectManager->getObject($viewHelperClassName);
			if (!$viewHelper instanceof \F3\FLOW3\MVC\View\Helper\HelperInterface) {
				throw new \F3\FLOW3\MVC\Exception\InvalidViewHelper('View Helpers must implement interface "\F3\FLOW3\MVC\View\Helper\HelperInterface"', 1222895456);
			}
			$viewHelper->setRequest($this->request);
			$this->viewHelpers[$viewHelperClassName] = $viewHelper;
		}
		return $this->viewHelpers[$viewHelperClassName];
	}

	/**
	 * Initializes this view.
	 *
	 * Override this method for initializing your concrete view implementation.
	 *
	 * @return void
	 */
	protected function initializeView() {
	}

	/**
	 * Renders the view
	 *
	 * @return string The rendered view
	 */
	abstract public function render();
}

?>
