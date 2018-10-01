<?php
namespace TYPO3\CMS\Fluid\Core\Widget;

/*
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
 * This is the bootstrap for Ajax Widget responses
 *
 * @internal This class is only used internally by the widget framework.
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
        $this->configureObjectManager(true);
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
     * @see initialize()
     */
    public function initializeConfiguration($configuration)
    {
        $this->configurationManager = $this->objectManager->get(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::class);
        /** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObject */
        $contentObject = $this->cObj ?? \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);
        $this->configurationManager->setContentObject($contentObject);
        $this->configurationManager->setConfiguration($configuration);
    }

    /**
     * Configures the object manager object configuration from
     * config.tx_extbase.objects
     *
     * @param $isInternalCall bool Set to true by Boostrap, not by extensions
     * @see initialize()
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function configureObjectManager(bool $isInternalCall = false)
    {
        if (!$isInternalCall) {
            trigger_error(self::class . '->configureObjectManager() will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        }
        $typoScriptSetup = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
        if (!is_array($typoScriptSetup['config.']['tx_extbase.']['objects.'])) {
            return;
        }
        trigger_error('Overriding object implementations via TypoScript settings config.tx_extbase.objects and plugin.tx_%plugin%.objects will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        $objectContainer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\Container\Container::class);
        foreach ($typoScriptSetup['config.']['tx_extbase.']['objects.'] as $classNameWithDot => $classConfiguration) {
            if (isset($classConfiguration['className'])) {
                $originalClassName = rtrim($classNameWithDot, '.');
                $objectContainer->registerImplementation($originalClassName, $classConfiguration['className']);
            }
        }
    }
}
