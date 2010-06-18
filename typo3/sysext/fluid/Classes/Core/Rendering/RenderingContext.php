<?php

/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
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
 *
 *
 * @version $Id: RenderingContext.php 2043 2010-03-16 08:49:45Z sebastian $
 * @package Fluid
 * @subpackage Core\Rendering
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Tx_Fluid_Core_Rendering_RenderingContext {

	/**
	 * Template Variable Container. Contains all variables available through object accessors in the template
	 * @var Tx_Fluid_Core_ViewHelper_TemplateVariableContainer
	 */
	protected $templateVariableContainer;

	/**
	 * Object manager which is bubbled through. The ViewHelperNode cannot get an ObjectManager injected because
	 * the whole syntax tree should be cacheable
	 * @var Tx_Fluid_Compatibility_ObjectManager
	 */
	protected $objectManager;

	/**
	 * Controller context being passed to the ViewHelper
	 * @var Tx_Extbase_MVC_Controller_ControllerContext
	 */
	protected $controllerContext;

	/**
	 * ViewHelper Variable Container
	 * @var Tx_Fluid_Core_ViewHelpers_ViewHelperVariableContainer
	 */
	protected $viewHelperVariableContainer;

	/**
	 * Inject the object manager
	 *
	 * @param Tx_Fluid_Compatibility_ObjectManager $objectManager
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function injectObjectManager(Tx_Fluid_Compatibility_ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Returns the object manager. Only the ViewHelperNode should do this.
	 *
	 * @param Tx_Fluid_Compatibility_ObjectManager $objectManager
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getObjectManager() {
		return $this->objectManager;
	}

	/**
	 * Sets the template variable container containing all variables available through ObjectAccessors
	 * in the template
	 *
	 * @param Tx_Fluid_Core_ViewHelper_TemplateVariableContainer $templateVariableContainer The template variable container to set
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setTemplateVariableContainer(Tx_Fluid_Core_ViewHelper_TemplateVariableContainer $templateVariableContainer) {
		$this->templateVariableContainer = $templateVariableContainer;
	}

	/**
	 * Get the template variable container
	 *
	 * @return Tx_Fluid_Core_ViewHelper_TemplateVariableContainer The Template Variable Container
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getTemplateVariableContainer() {
		return $this->templateVariableContainer;
	}

	/**
	 * Set the controller context which will be passed to the ViewHelper
	 *
	 * @param Tx_Extbase_MVC_Controller_ControllerContext $controllerContext The controller context to set
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setControllerContext(Tx_Extbase_MVC_Controller_ControllerContext $controllerContext) {
		$this->controllerContext = $controllerContext;
	}

	/**
	 * Get the controller context which will be passed to the ViewHelper
	 *
	 * @return Tx_Extbase_MVC_Controller_ControllerContext The controller context to set
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getControllerContext() {
		return $this->controllerContext;
	}

	/**
	 * Set the ViewHelperVariableContainer
	 *
	 * @param Tx_Fluid_Core_ViewHelper_ViewHelperVariableContainer $viewHelperVariableContainer
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setViewHelperVariableContainer(Tx_Fluid_Core_ViewHelper_ViewHelperVariableContainer $viewHelperVariableContainer) {
		$this->viewHelperVariableContainer = $viewHelperVariableContainer;
	}

	/**
	 * Get the ViewHelperVariableContainer
	 *
	 * @return Tx_Fluid_Core_ViewHelper_ViewHelperVariableContainer
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getViewHelperVariableContainer() {
		return $this->viewHelperVariableContainer;
	}
}
?>