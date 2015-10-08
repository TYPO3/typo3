<?php
namespace TYPO3\CMS\Fluid\Core\Widget;

/*
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
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
 */
class Bootstrap
{
    /**
     * Back reference to the parent content object
     * This has to be public as it is set directly from TYPO3
     *
     * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    public $cObj;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * @param string $content The content
     * @param array $configuration The TS configuration array
     * @return string $content The processed content
     */
    public function run($content, $configuration)
    {
        $this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $this->initializeConfiguration($configuration);
        $this->configureObjectManager();
        $ajaxWidgetContextHolder = $this->objectManager->get(\TYPO3\CMS\Fluid\Core\Widget\AjaxWidgetContextHolder::class);
        $widgetIdentifier = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('fluid-widget-id');
        $widgetContext = $ajaxWidgetContextHolder->get($widgetIdentifier);
        $configuration['extensionName'] = $widgetContext->getParentExtensionName();
        $configuration['pluginName'] = $widgetContext->getParentPluginName();
        $extbaseBootstrap = $this->objectManager->get(\TYPO3\CMS\Extbase\Core\Bootstrap::class);
        $extbaseBootstrap->cObj = $this->cObj;
        return $extbaseBootstrap->run($content, $configuration);
    }

    /**
     * Initializes the Object framework.
     *
     * @param array $configuration
     * @return void
     * @see initialize()
     */
    public function initializeConfiguration($configuration)
    {
        $this->configurationManager = $this->objectManager->get(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::class);
        /** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObject */
        $contentObject = isset($this->cObj) ? $this->cObj : \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);
        $this->configurationManager->setContentObject($contentObject);
        $this->configurationManager->setConfiguration($configuration);
    }

    /**
     * Configures the object manager object configuration from
     * config.tx_extbase.objects
     *
     * @return void
     * @see initialize()
     * @todo this is duplicated code (see \TYPO3\CMS\Extbase\Core\Bootstrap::configureObjectManager())
     */
    public function configureObjectManager()
    {
        $typoScriptSetup = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
        if (!is_array($typoScriptSetup['config.']['tx_extbase.']['objects.'])) {
            return;
        }
        $objectContainer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\Container\Container::class);
        foreach ($typoScriptSetup['config.']['tx_extbase.']['objects.'] as $classNameWithDot => $classConfiguration) {
            if (isset($classConfiguration['className'])) {
                $originalClassName = rtrim($classNameWithDot, '.');
                $objectContainer->registerImplementation($originalClassName, $classConfiguration['className']);
            }
        }
    }
}
