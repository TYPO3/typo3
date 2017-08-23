<?php
namespace TYPO3\CMS\Extbase\Mvc\Web;

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

use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Exception as MvcException;

/**
 * Builds a web request.
 */
class RequestBuilder implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * This is the vendor name of the extension
     *
     * @var string
     */
    protected $vendorName;

    /**
     * This is a unique key for a plugin (not the extension key!)
     *
     * @var string
     */
    protected $pluginName = 'plugin';

    /**
     * The name of the extension (in UpperCamelCase)
     *
     * @var string
     */
    protected $extensionName;

    /**
     * The default controller name
     *
     * @var string
     */
    protected $defaultControllerName = '';

    /**
     * The default format of the response object
     *
     * @var string
     */
    protected $defaultFormat = 'html';

    /**
     * The allowed actions of the controller. This actions can be called via $_GET and $_POST.
     *
     * @var array
     */
    protected $allowedControllerActions = [];

    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * @var \TYPO3\CMS\Extbase\Service\ExtensionService
     */
    protected $extensionService;

    /**
     * @var \TYPO3\CMS\Extbase\Service\EnvironmentService
     */
    protected $environmentService;

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
     */
    public function injectConfigurationManager(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Service\ExtensionService $extensionService
     */
    public function injectExtensionService(\TYPO3\CMS\Extbase\Service\ExtensionService $extensionService)
    {
        $this->extensionService = $extensionService;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Service\EnvironmentService $environmentService
     */
    public function injectEnvironmentService(\TYPO3\CMS\Extbase\Service\EnvironmentService $environmentService)
    {
        $this->environmentService = $environmentService;
    }

    /**
     * @throws MvcException
     */
    protected function loadDefaultValues()
    {
        $configuration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        if (empty($configuration['extensionName'])) {
            throw new MvcException('"extensionName" is not properly configured. Request can\'t be dispatched!', 1289843275);
        }
        if (empty($configuration['pluginName'])) {
            throw new MvcException('"pluginName" is not properly configured. Request can\'t be dispatched!', 1289843277);
        }
        if (!empty($configuration['vendorName'])) {
            $this->vendorName = $configuration['vendorName'];
        } else {
            $this->vendorName = null;
        }
        $this->extensionName = $configuration['extensionName'];
        $this->pluginName = $configuration['pluginName'];
        $this->defaultControllerName = (string)current(array_keys($configuration['controllerConfiguration']));
        $this->allowedControllerActions = [];
        foreach ($configuration['controllerConfiguration'] as $controllerName => $controllerActions) {
            $this->allowedControllerActions[$controllerName] = $controllerActions['actions'];
        }
        if (!empty($configuration['format'])) {
            $this->defaultFormat = $configuration['format'];
        }
    }

    /**
     * Builds a web request object from the raw HTTP information and the configuration
     *
     * @return \TYPO3\CMS\Extbase\Mvc\Web\Request The web request as an object
     */
    public function build()
    {
        $this->loadDefaultValues();
        $pluginNamespace = $this->extensionService->getPluginNamespace($this->extensionName, $this->pluginName);
        $parameters = \TYPO3\CMS\Core\Utility\GeneralUtility::_GPmerged($pluginNamespace);
        $files = $this->untangleFilesArray($_FILES);
        if (isset($files[$pluginNamespace]) && is_array($files[$pluginNamespace])) {
            $parameters = array_replace_recursive($parameters, $files[$pluginNamespace]);
        }
        $controllerName = $this->resolveControllerName($parameters);
        $actionName = $this->resolveActionName($controllerName, $parameters);
        /** @var $request \TYPO3\CMS\Extbase\Mvc\Web\Request */
        $request = $this->objectManager->get(\TYPO3\CMS\Extbase\Mvc\Web\Request::class);
        if ($this->vendorName !== null) {
            $request->setControllerVendorName($this->vendorName);
        }
        $request->setPluginName($this->pluginName);
        $request->setControllerExtensionName($this->extensionName);
        $request->setControllerName($controllerName);
        $request->setControllerActionName($actionName);
        $request->setRequestUri(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'));
        $request->setBaseUri(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL'));
        $request->setMethod($this->environmentService->getServerRequestMethod());
        if (is_string($parameters['format']) && $parameters['format'] !== '') {
            $request->setFormat(filter_var($parameters['format'], FILTER_SANITIZE_STRING));
        } else {
            $request->setFormat($this->defaultFormat);
        }
        foreach ($parameters as $argumentName => $argumentValue) {
            $request->setArgument($argumentName, $argumentValue);
        }
        return $request;
    }

    /**
     * Returns the current ControllerName extracted from given $parameters.
     * If no controller is specified, the defaultControllerName will be returned.
     * If that's not available, an exception is thrown.
     *
     * @param array $parameters
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerNameException
     * @throws MvcException if the controller could not be resolved
     * @throws \TYPO3\CMS\Core\Error\Http\PageNotFoundException
     * @return string
     */
    protected function resolveControllerName(array $parameters)
    {
        if (!isset($parameters['controller']) || $parameters['controller'] === '') {
            if (empty($this->defaultControllerName)) {
                throw new MvcException('The default controller for extension "' . $this->extensionName . '" and plugin "' . $this->pluginName . '" can not be determined. Please check for TYPO3\\CMS\\Extbase\\Utility\\ExtensionUtility::configurePlugin() in your ext_localconf.php.', 1316104317);
            }
            return $this->defaultControllerName;
        }
        $allowedControllerNames = array_keys($this->allowedControllerActions);
        if (!in_array($parameters['controller'], $allowedControllerNames)) {
            $configuration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
            if (isset($configuration['mvc']['throwPageNotFoundExceptionIfActionCantBeResolved']) && (bool)$configuration['mvc']['throwPageNotFoundExceptionIfActionCantBeResolved']) {
                throw new \TYPO3\CMS\Core\Error\Http\PageNotFoundException('The requested resource was not found', 1313857897);
            }
            if (isset($configuration['mvc']['callDefaultActionIfActionCantBeResolved']) && (bool)$configuration['mvc']['callDefaultActionIfActionCantBeResolved']) {
                return $this->defaultControllerName;
            }
            throw new \TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerNameException(
                'The controller "' . $parameters['controller'] . '" is not allowed by plugin "' . $this->pluginName . '". Please check for TYPO3\\CMS\\Extbase\\Utility\\ExtensionUtility::configurePlugin() in your ext_localconf.php.',
                1313855173
            );
        }
        return filter_var($parameters['controller'], FILTER_SANITIZE_STRING);
    }

    /**
     * Returns the current actionName extracted from given $parameters.
     * If no action is specified, the defaultActionName will be returned.
     * If that's not available or the specified action is not defined in the current plugin, an exception is thrown.
     *
     * @param string $controllerName
     * @param array $parameters
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidActionNameException
     * @throws MvcException
     * @throws \TYPO3\CMS\Core\Error\Http\PageNotFoundException
     * @return string
     */
    protected function resolveActionName($controllerName, array $parameters)
    {
        $defaultActionName = is_array($this->allowedControllerActions[$controllerName]) ? current($this->allowedControllerActions[$controllerName]) : '';
        if (!isset($parameters['action']) || $parameters['action'] === '') {
            if ($defaultActionName === '') {
                throw new MvcException('The default action can not be determined for controller "' . $controllerName . '". Please check TYPO3\\CMS\\Extbase\\Utility\\ExtensionUtility::configurePlugin() in your ext_localconf.php.', 1295479651);
            }
            return $defaultActionName;
        }
        $actionName = $parameters['action'];
        $allowedActionNames = $this->allowedControllerActions[$controllerName];
        if (!in_array($actionName, $allowedActionNames)) {
            $configuration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
            if (isset($configuration['mvc']['throwPageNotFoundExceptionIfActionCantBeResolved']) && (bool)$configuration['mvc']['throwPageNotFoundExceptionIfActionCantBeResolved']) {
                throw new \TYPO3\CMS\Core\Error\Http\PageNotFoundException('The requested resource was not found', 1313857898);
            }
            if (isset($configuration['mvc']['callDefaultActionIfActionCantBeResolved']) && (bool)$configuration['mvc']['callDefaultActionIfActionCantBeResolved']) {
                return $defaultActionName;
            }
            throw new \TYPO3\CMS\Extbase\Mvc\Exception\InvalidActionNameException('The action "' . $actionName . '" (controller "' . $controllerName . '") is not allowed by this plugin. Please check TYPO3\\CMS\\Extbase\\Utility\\ExtensionUtility::configurePlugin() in your ext_localconf.php.', 1313855175);
        }
        return filter_var($actionName, FILTER_SANITIZE_STRING);
    }

    /**
     * Transforms the convoluted _FILES superglobal into a manageable form.
     *
     * @param array $convolutedFiles The _FILES superglobal
     * @return array Untangled files
     * @see TYPO3\Flow\Utility\Environment
     */
    protected function untangleFilesArray(array $convolutedFiles)
    {
        $untangledFiles = [];
        $fieldPaths = [];
        foreach ($convolutedFiles as $firstLevelFieldName => $fieldInformation) {
            if (!is_array($fieldInformation['error'])) {
                $fieldPaths[] = [$firstLevelFieldName];
            } else {
                $newFieldPaths = $this->calculateFieldPaths($fieldInformation['error'], $firstLevelFieldName);
                array_walk($newFieldPaths, function (&$value, $key) {
                    $value = explode('/', $value);
                });
                $fieldPaths = array_merge($fieldPaths, $newFieldPaths);
            }
        }
        foreach ($fieldPaths as $fieldPath) {
            if (count($fieldPath) === 1) {
                $fileInformation = $convolutedFiles[$fieldPath[0]];
            } else {
                $fileInformation = [];
                foreach ($convolutedFiles[$fieldPath[0]] as $key => $subStructure) {
                    try {
                        $fileInformation[$key] = \TYPO3\CMS\Core\Utility\ArrayUtility::getValueByPath($subStructure, array_slice($fieldPath, 1));
                    } catch (\RuntimeException $e) {
                        // do nothing if the path is invalid
                    }
                }
            }
            $untangledFiles = \TYPO3\CMS\Core\Utility\ArrayUtility::setValueByPath($untangledFiles, $fieldPath, $fileInformation);
        }
        return $untangledFiles;
    }

    /**
     * Returns an array of all possibles "field paths" for the given array.
     *
     * @param array $structure The array to walk through
     * @param string $firstLevelFieldName
     * @return array An array of paths (as strings) in the format "key1/key2/key3" ...
     */
    protected function calculateFieldPaths(array $structure, $firstLevelFieldName = null)
    {
        $fieldPaths = [];
        if (is_array($structure)) {
            foreach ($structure as $key => $subStructure) {
                $fieldPath = ($firstLevelFieldName !== null ? $firstLevelFieldName . '/' : '') . $key;
                if (is_array($subStructure)) {
                    foreach ($this->calculateFieldPaths($subStructure) as $subFieldPath) {
                        $fieldPaths[] = $fieldPath . '/' . $subFieldPath;
                    }
                } else {
                    $fieldPaths[] = $fieldPath;
                }
            }
        }
        return $fieldPaths;
    }
}
