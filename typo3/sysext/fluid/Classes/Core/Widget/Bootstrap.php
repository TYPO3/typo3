<?php

/*
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
 * This is the bootstrap for Ajax Widget responses
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Fluid_Core_Widget_Bootstrap {

	/**
	 * Back reference to the parent content object
	 * This has to be public as it is set directly from TYPO3
	 *
	 * @var tslib_cObj
	 */
	public $cObj;

	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @param string $content The content
	 * @param array $configuration The TS configuration array
	 * @return string $content The processed content
	 */
	public function run($content, $configuration) {
		$this->initializeClassLoader();
		$this->objectManager = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager');
		$this->initializeConfiguration($configuration);
		$this->configureObjectManager();
		$ajaxWidgetContextHolder = $this->objectManager->get('Tx_Fluid_Core_Widget_AjaxWidgetContextHolder');

		$widgetIdentifier = t3lib_div::_GET('fluid-widget-id');
		$widgetContext = $ajaxWidgetContextHolder->get($widgetIdentifier);
		$configuration['extensionName'] = $widgetContext->getParentExtensionName();
		$configuration['pluginName'] = $widgetContext->getParentPluginName();

		$extbaseBootstrap = $this->objectManager->get('Tx_Extbase_Core_Bootstrap');
		$extbaseBootstrap->cObj = $this->cObj;
		return $extbaseBootstrap->run($content, $configuration);
	}

	/**
	 * Initializes the autoload mechanism of Extbase. This is supplement to the core autoloader.
	 *
	 * @return void
	 * @see initialize()
	 */
	protected function initializeClassLoader() {
		if (!class_exists('Tx_Extbase_Utility_ClassLoader', FALSE)) {
			require(t3lib_extmgm::extPath('extbase') . 'Classes/Utility/ClassLoader.php');
		}

		$classLoader = new Tx_Extbase_Utility_ClassLoader();
		spl_autoload_register(array($classLoader, 'loadClass'));
	}

	/**
	 * Initializes the Object framework.
	 *
	 * @return void
	 * @see initialize()
	 */
	public function initializeConfiguration($configuration) {
		$this->configurationManager = $this->objectManager->get('Tx_Extbase_Configuration_ConfigurationManagerInterface');
		$contentObject = isset($this->cObj) ? $this->cObj : t3lib_div::makeInstance('tslib_cObj');
		$this->configurationManager->setContentObject($contentObject);
		$this->configurationManager->setConfiguration($configuration);
	}

	/**
	 * Configures the object manager object configuration from
	 * config.tx_extbase.objects
	 *
	 * @return void
	 * @see initialize()
	 * @todo this is duplicated code (see Tx_Extbase_Core_Bootstrap::configureObjectManager())
	 */
	public function configureObjectManager() {
		$typoScriptSetup = $this->configurationManager->getConfiguration(Tx_Extbase_Configuration_ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
		if (!is_array($typoScriptSetup['config.']['tx_extbase.']['objects.'])) {
			return;
		}
		$objectContainer = t3lib_div::makeInstance('Tx_Extbase_Object_Container_Container');
		foreach ($typoScriptSetup['config.']['tx_extbase.']['objects.'] as $classNameWithDot => $classConfiguration) {
			if (isset($classConfiguration['className'])) {
				$originalClassName = rtrim($classNameWithDot, '.');
				$objectContainer->registerImplementation($originalClassName, $classConfiguration['className']);
			}
		}
	}
}

?>