<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Extbase\Mvc\Web;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Module\ExtbaseModule;
use TYPO3\CMS\Core\Error\Http\PageNotFoundException;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Exception as MvcException;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidActionNameException;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentNameException;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerNameException;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Service\ExtensionService;

/**
 * Builds an extbase web request.
 *
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
#[Autoconfigure(public: true)]
readonly class RequestBuilder
{
    public function __construct(
        protected ConfigurationManagerInterface $configurationManager,
        protected ExtensionService $extensionService,
    ) {}

    /**
     * Decorate a PSR-7 request as extbase web Request with the extbase attribute.
     */
    public function build(ServerRequestInterface $mainRequest): RequestInterface
    {
        $configuration = [];
        // Parameters, which are not part of the request URL (e.g. due to "useArgumentsWithoutNamespace"), which however
        // need to be taken into account on building the extbase request. Usually those are "controller" and "action".
        $fallbackParameters = [];
        // To be used in TYPO3 Backend for Extbase modules that do not need the "namespaces" GET and POST parameters anymore.
        $useArgumentsWithoutNamespace = false;
        // Fetch requested module from the main request. This is only used for TYPO3 Backend Modules.
        $module = $mainRequest->getAttribute('module');
        if ($module instanceof ExtbaseModule) {
            $configuration = [
                'controllerConfiguration' => $module->getControllerActions(),
            ];
            $useArgumentsWithoutNamespace = true;
            // Ensure the "controller" and "action" information are added as fallback parameters.
            if ($routeOptions = $mainRequest->getAttribute('route')?->getOptions()) {
                $fallbackParameters['controller'] = $routeOptions['controller'] ?? null;
                $fallbackParameters['action'] = $routeOptions['action'];
            }
        }
        $defaultValues = $this->loadDefaultValues($configuration);
        $pluginNamespace = $this->extensionService->getPluginNamespace(
            $defaultValues->getExtensionName(),
            $defaultValues->getPluginName()
        );
        $queryArguments = $mainRequest->getAttribute('routing');
        if ($useArgumentsWithoutNamespace) {
            $parameters = $mainRequest->getQueryParams();
        } elseif ($queryArguments instanceof PageArguments) {
            $parameters = $queryArguments->get($pluginNamespace) ?? [];
        } else {
            $parameters = $mainRequest->getQueryParams()[$pluginNamespace] ?? [];
        }
        $parameters = is_array($parameters) ? $parameters : [];
        if ($fallbackParameters !== []) {
            // Enhance with fallback parameters, such as "controller" and "action"
            $parameters = array_replace_recursive($fallbackParameters, $parameters);
        }
        if ($mainRequest->getMethod() === 'POST') {
            if ($useArgumentsWithoutNamespace) {
                $postParameters = $mainRequest->getParsedBody();
            } else {
                $postParameters = $mainRequest->getParsedBody()[$pluginNamespace] ?? [];
            }
            $postParameters = is_array($postParameters) ? $postParameters : [];
            $parameters = array_replace_recursive($parameters, $postParameters);
        }

        $files = $mainRequest->getUploadedFiles();
        if (!$useArgumentsWithoutNamespace) {
            $files = $files[$pluginNamespace] ?? [];
            if ($files instanceof UploadedFile) {
                throw new InvalidArgumentNameException(
                    'Using only the plugin namespace as argument name is not allowed for uploaded files. Please use plugin_namespace[argument_name] instead.',
                    1722542546
                );
            }
        }

        // Merge UploadedFiles into request parameters, so that they
        // are available as arguments for property mapping.
        $parameters = array_merge_recursive($parameters, $files);

        $controllerClassName = $this->resolveControllerClassName($defaultValues, $parameters);
        $actionName = $this->resolveActionName($defaultValues, $controllerClassName, $parameters);

        $extbaseAttribute = new ExtbaseRequestParameters();
        $extbaseAttribute->setPluginName($defaultValues->getPluginName());
        $extbaseAttribute->setControllerExtensionName($defaultValues->getExtensionName());
        $extbaseAttribute->setControllerAliasToClassNameMapping($defaultValues->getControllerAliasToClassMapping());
        $extbaseAttribute->setControllerName($defaultValues->getControllerAliasForControllerClassName($controllerClassName));
        $extbaseAttribute->setControllerActionName($actionName);
        $extbaseAttribute->setUploadedFiles($files);

        if (isset($parameters['format']) && is_string($parameters['format']) && $parameters['format'] !== '') {
            $extbaseAttribute->setFormat(preg_replace('/[^a-zA-Z0-9]+/', '', $parameters['format']));
        } else {
            $extbaseAttribute->setFormat($defaultValues->getDefaultFormat());
        }
        foreach ($parameters as $argumentName => $argumentValue) {
            $extbaseAttribute->setArgument($argumentName, $argumentValue);
        }
        return new Request($mainRequest->withAttribute('extbase', $extbaseAttribute));
    }

    /**
     * @throws MvcException
     */
    protected function loadDefaultValues(array $configuration = []): RequestBuilderDefaultValues
    {
        // todo: See comment in \TYPO3\CMS\Extbase\Core\Bootstrap::initializeConfiguration for further explanation
        // todo: on why we shouldn't use the configuration manager here.
        $configuration = array_replace_recursive($this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK), $configuration);
        try {
            return RequestBuilderDefaultValues::fromConfiguration($configuration);
        } catch (\InvalidArgumentException $e) {
            throw MvcException::fromPrevious($e);
        }
    }

    /**
     * Returns the current ControllerName extracted from given $parameters.
     * If no controller is specified, the defaultControllerName will be returned.
     * If that's not available, an exception is thrown.
     *
     * @throws InvalidControllerNameException
     * @throws MvcException if the controller could not be resolved
     * @throws PageNotFoundException
     * @return class-string
     */
    protected function resolveControllerClassName(RequestBuilderDefaultValues $defaultValues, array $parameters): string
    {
        if (!isset($parameters['controller']) || $parameters['controller'] === '') {
            return $defaultValues->getDefaultControllerClassName();
        }
        $controllerClassName = $defaultValues->getControllerClassNameForAlias($parameters['controller']) ?? '';
        if ($defaultValues->getAllowedControllerActionsOfController($controllerClassName) === []) {
            $configuration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
            if (isset($configuration['mvc']['throwPageNotFoundExceptionIfActionCantBeResolved']) && (bool)$configuration['mvc']['throwPageNotFoundExceptionIfActionCantBeResolved']) {
                throw new PageNotFoundException('The requested resource was not found', 1313857897);
            }
            if (isset($configuration['mvc']['callDefaultActionIfActionCantBeResolved']) && (bool)$configuration['mvc']['callDefaultActionIfActionCantBeResolved']) {
                return $defaultValues->getDefaultControllerClassName();
            }
            throw new InvalidControllerNameException(
                'The controller "' . $parameters['controller'] . '" is not allowed by plugin "' . $defaultValues->getPluginName() . '". Please check for TYPO3\\CMS\\Extbase\\Utility\\ExtensionUtility::configurePlugin() in your ext_localconf.php.',
                1313855173
            );
        }
        return preg_replace('/[^a-zA-Z0-9\\\\]+/', '', $controllerClassName);
    }

    /**
     * Returns the current actionName extracted from given $parameters.
     * If no action is specified, the defaultActionName will be returned.
     * If that's not available or the specified action is not defined in the current plugin, an exception is thrown.
     *
     * @param class-string $controllerClassName
     * @throws InvalidActionNameException
     * @throws MvcException
     * @throws PageNotFoundException
     * @return non-empty-string
     */
    protected function resolveActionName(RequestBuilderDefaultValues $defaultValues, string $controllerClassName, array $parameters): string
    {
        $defaultActionName = $defaultValues->getDefaultActionName($controllerClassName);
        if (!isset($parameters['action']) || $parameters['action'] === '') {
            if ($defaultActionName === '') {
                throw new MvcException('The default action can not be determined for controller "' . $controllerClassName . '". Please check TYPO3\\CMS\\Extbase\\Utility\\ExtensionUtility::configurePlugin() in your ext_localconf.php.', 1295479651);
            }
            return $defaultActionName;
        }
        $actionName = $parameters['action'];
        $allowedActionNames = $defaultValues->getAllowedControllerActionsOfController($controllerClassName);
        if (!in_array($actionName, $allowedActionNames)) {
            $configuration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
            if (isset($configuration['mvc']['throwPageNotFoundExceptionIfActionCantBeResolved']) && (bool)$configuration['mvc']['throwPageNotFoundExceptionIfActionCantBeResolved']) {
                throw new PageNotFoundException('The requested resource was not found', 1313857898);
            }
            if (isset($configuration['mvc']['callDefaultActionIfActionCantBeResolved']) && (bool)$configuration['mvc']['callDefaultActionIfActionCantBeResolved']) {
                if ($defaultActionName === '') {
                    throw new MvcException('The default action can not be determined for controller "' . $controllerClassName . '". Please check TYPO3\\CMS\\Extbase\\Utility\\ExtensionUtility::configurePlugin() in your ext_localconf.php.', 1679048627);
                }
                return $defaultActionName;
            }
            throw new InvalidActionNameException('The action "' . $actionName . '" (controller "' . $controllerClassName . '") is not allowed by this plugin / module. Please check TYPO3\\CMS\\Extbase\\Utility\\ExtensionUtility::configurePlugin() in your ext_localconf.php / TYPO3\\CMS\\Extbase\\Utility\\ExtensionUtility::configureModule() in your ext_tables.php.', 1313855175);
        }
        return preg_replace('/[^a-zA-Z0-9]+/', '', $actionName);
    }
}
