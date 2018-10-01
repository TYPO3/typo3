<?php
namespace TYPO3\CMS\Extbase\Mvc\Controller;

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

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Request as WebRequest;
use TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;
use TYPO3Fluid\Fluid\View\TemplateView;

/**
 * A multi action controller. This is by far the most common base class for Controllers.
 */
class ActionController extends AbstractController
{
    /**
     * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService
     */
    protected $reflectionService;

    /**
     * @var \TYPO3\CMS\Extbase\Service\CacheService
     */
    protected $cacheService;

    /**
     * The current view, as resolved by resolveView()
     *
     * @var ViewInterface
     */
    protected $view;

    /**
     * @var string
     */
    protected $namespacesViewObjectNamePattern = '@vendor\@extension\View\@controller\@action@format';

    /**
     * A list of formats and object names of the views which should render them.
     *
     * Example:
     *
     * array('html' => 'Tx_MyExtension_View_MyHtmlView', 'json' => 'F3...
     *
     * @var array
     */
    protected $viewFormatToObjectNameMap = [];

    /**
     * The default view object to use if none of the resolved views can render
     * a response for the current request.
     *
     * @var string
     */
    protected $defaultViewObjectName = \TYPO3\CMS\Fluid\View\TemplateView::class;

    /**
     * Name of the action method
     *
     * @var string
     */
    protected $actionMethodName = 'indexAction';

    /**
     * Name of the special error action method which is called in case of errors
     *
     * @var string
     */
    protected $errorMethodName = 'errorAction';

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfigurationService
     */
    protected $mvcPropertyMappingConfigurationService;

    /**
     * The current request.
     *
     * @var \TYPO3\CMS\Extbase\Mvc\Request
     */
    protected $request;

    /**
     * The response which will be returned by this action controller
     *
     * @var \TYPO3\CMS\Extbase\Mvc\Response
     */
    protected $response;

    /**
     * @param \TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService
     */
    public function injectReflectionService(\TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService)
    {
        $this->reflectionService = $reflectionService;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Service\CacheService $cacheService
     */
    public function injectCacheService(\TYPO3\CMS\Extbase\Service\CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfigurationService $mvcPropertyMappingConfigurationService
     */
    public function injectMvcPropertyMappingConfigurationService(\TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfigurationService $mvcPropertyMappingConfigurationService)
    {
        $this->mvcPropertyMappingConfigurationService = $mvcPropertyMappingConfigurationService;
    }

    /**
     * Handles a request. The result output is returned by altering the given response.
     *
     * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request The request object
     * @param \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response The response, modified by this handler
     *
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function processRequest(\TYPO3\CMS\Extbase\Mvc\RequestInterface $request, \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response)
    {
        if (!$this->canProcessRequest($request)) {
            throw new \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException(static::class . ' does not support requests of type "' . get_class($request) . '". Supported types are: ' . implode(' ', $this->supportedRequestTypes), 1187701131);
        }

        if ($response instanceof \TYPO3\CMS\Extbase\Mvc\Web\Response && $request instanceof WebRequest) {
            $response->setRequest($request);
        }
        $this->request = $request;
        $this->request->setDispatched(true);
        $this->response = $response;
        $this->uriBuilder = $this->objectManager->get(\TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder::class);
        $this->uriBuilder->setRequest($request);
        $this->actionMethodName = $this->resolveActionMethodName();
        $this->initializeActionMethodArguments();
        $this->initializeActionMethodValidators();
        $this->mvcPropertyMappingConfigurationService->initializePropertyMappingConfigurationFromRequest($request, $this->arguments);
        $this->initializeAction();
        $actionInitializationMethodName = 'initialize' . ucfirst($this->actionMethodName);
        if (method_exists($this, $actionInitializationMethodName)) {
            call_user_func([$this, $actionInitializationMethodName]);
        }
        $this->mapRequestArgumentsToControllerArguments();
        $this->controllerContext = $this->buildControllerContext();
        $this->view = $this->resolveView();
        if ($this->view !== null) {
            $this->initializeView($this->view);
        }
        $this->callActionMethod();
        $this->renderAssetsForRequest($request);
    }

    /**
     * Method which initializes assets that should be attached to the response
     * for the given $request, which contains parameters that an override can
     * use to determine which assets to add via PageRenderer.
     *
     * This default implementation will attempt to render the sections "HeaderAssets"
     * and "FooterAssets" from the template that is being rendered, inserting the
     * rendered content into either page header or footer, as appropriate. Both
     * sections are optional and can be used one or both in combination.
     *
     * You can add assets with this method without worrying about duplicates, if
     * for example you do this in a plugin that gets used multiple time on a page.
     *
     * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request
     */
    protected function renderAssetsForRequest($request)
    {
        if (!$this->view instanceof TemplateView) {
            // Only TemplateView (from Fluid engine, so this includes all TYPO3 Views based
            // on TYPO3's AbstractTemplateView) supports renderSection(). The method is not
            // declared on ViewInterface - so we must assert a specific class. We silently skip
            // asset processing if the View doesn't match, so we don't risk breaking custom Views.
            return;
        }
        $pageRenderer = $this->objectManager->get(PageRenderer::class);
        $variables = ['request' => $request, 'arguments' => $this->arguments];
        $headerAssets = $this->view->renderSection('HeaderAssets', $variables, true);
        $footerAssets = $this->view->renderSection('FooterAssets', $variables, true);
        if (!empty(trim($headerAssets))) {
            $pageRenderer->addHeaderData($headerAssets);
        }
        if (!empty(trim($footerAssets))) {
            $pageRenderer->addFooterData($footerAssets);
        }
    }

    /**
     * Implementation of the arguments initilization in the action controller:
     * Automatically registers arguments of the current action
     *
     * Don't override this method - use initializeAction() instead.
     *
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentTypeException
     * @see initializeArguments()
     */
    protected function initializeActionMethodArguments()
    {
        $methodParameters = $this->reflectionService
                ->getClassSchema(static::class)
                ->getMethod($this->actionMethodName)['params'] ?? [];

        foreach ($methodParameters as $parameterName => $parameterInfo) {
            $dataType = null;
            if (isset($parameterInfo['type'])) {
                $dataType = $parameterInfo['type'];
            } elseif ($parameterInfo['array']) {
                $dataType = 'array';
            }
            if ($dataType === null) {
                throw new \TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentTypeException('The argument type for parameter $' . $parameterName . ' of method ' . static::class . '->' . $this->actionMethodName . '() could not be detected.', 1253175643);
            }
            $defaultValue = $parameterInfo['hasDefaultValue'] === true ? $parameterInfo['defaultValue'] : null;
            $this->arguments->addNewArgument($parameterName, $dataType, $parameterInfo['optional'] === false, $defaultValue);
        }
    }

    /**
     * Adds the needed validators to the Arguments:
     *
     * - Validators checking the data type from the @param annotation
     * - Custom validators specified with validate annotations.
     * - Model-based validators (validate annotations in the model)
     * - Custom model validator classes
     */
    protected function initializeActionMethodValidators()
    {
        if ($this->arguments->count() === 0) {
            return;
        }

        $classSchema = $this->reflectionService->getClassSchema(static::class);

        $ignoreValidationAnnotations = array_unique(array_flip(
            $classSchema->getMethod($this->actionMethodName)['tags']['ignorevalidation'] ?? []
        ));

        /** @var \TYPO3\CMS\Extbase\Mvc\Controller\Argument $argument */
        foreach ($this->arguments as $argument) {
            if (isset($ignoreValidationAnnotations[$argument->getName()])) {
                continue;
            }

            $validator = $this->objectManager->get(ConjunctionValidator::class);
            $validatorDefinitions = $classSchema->getMethod($this->actionMethodName)['params'][$argument->getName()]['validators'] ?? [];

            foreach ($validatorDefinitions as $validatorDefinition) {
                /** @var ValidatorInterface $validatorInstance */
                $validatorInstance = $this->objectManager->get(
                    $validatorDefinition['className'],
                    $validatorDefinition['options']
                );

                $validator->addValidator(
                    $validatorInstance
                );
            }

            $baseValidatorConjunction = $this->validatorResolver->getBaseValidatorConjunction($argument->getDataType());
            if ($baseValidatorConjunction->count() > 0) {
                $validator->addValidator($baseValidatorConjunction);
            }
            $argument->setValidator($validator);
        }
    }

    /**
     * Resolves and checks the current action method name
     *
     * @return string Method name of the current action
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchActionException if the action specified in the request object does not exist (and if there's no default action either).
     */
    protected function resolveActionMethodName()
    {
        $actionMethodName = $this->request->getControllerActionName() . 'Action';
        if (!method_exists($this, $actionMethodName)) {
            throw new \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchActionException('An action "' . $actionMethodName . '" does not exist in controller "' . static::class . '".', 1186669086);
        }
        return $actionMethodName;
    }

    /**
     * Calls the specified action method and passes the arguments.
     *
     * If the action returns a string, it is appended to the content in the
     * response object. If the action doesn't return anything and a valid
     * view exists, the view is rendered automatically.
     */
    protected function callActionMethod()
    {
        $preparedArguments = [];
        /** @var \TYPO3\CMS\Extbase\Mvc\Controller\Argument $argument */
        foreach ($this->arguments as $argument) {
            $preparedArguments[] = $argument->getValue();
        }
        $validationResult = $this->arguments->validate();
        if (!$validationResult->hasErrors()) {
            $this->emitBeforeCallActionMethodSignal($preparedArguments);
            $actionResult = $this->{$this->actionMethodName}(...$preparedArguments);
        } else {
            $actionResult = $this->{$this->errorMethodName}();
        }

        if ($actionResult === null && $this->view instanceof ViewInterface) {
            $this->response->appendContent($this->view->render());
        } elseif (is_string($actionResult) && $actionResult !== '') {
            $this->response->appendContent($actionResult);
        } elseif (is_object($actionResult) && method_exists($actionResult, '__toString')) {
            $this->response->appendContent((string)$actionResult);
        }
    }

    /**
     * Emits a signal before the current action is called
     *
     * @param array $preparedArguments
     */
    protected function emitBeforeCallActionMethodSignal(array $preparedArguments)
    {
        $this->signalSlotDispatcher->dispatch(__CLASS__, 'beforeCallActionMethod', [static::class, $this->actionMethodName, $preparedArguments]);
    }

    /**
     * Prepares a view for the current action.
     * By default, this method tries to locate a view with a name matching the current action.
     *
     * @return ViewInterface
     */
    protected function resolveView()
    {
        $viewObjectName = $this->resolveViewObjectName();
        if ($viewObjectName !== false) {
            /** @var ViewInterface $view */
            $view = $this->objectManager->get($viewObjectName);
            $this->setViewConfiguration($view);
            if ($view->canRender($this->controllerContext) === false) {
                unset($view);
            }
        }
        if (!isset($view) && $this->defaultViewObjectName != '') {
            /** @var ViewInterface $view */
            $view = $this->objectManager->get($this->defaultViewObjectName);
            $this->setViewConfiguration($view);
            if ($view->canRender($this->controllerContext) === false) {
                unset($view);
            }
        }
        if (!isset($view)) {
            $view = $this->objectManager->get(\TYPO3\CMS\Extbase\Mvc\View\NotFoundView::class);
            $view->assign('errorMessage', 'No template was found. View could not be resolved for action "'
                . $this->request->getControllerActionName() . '" in class "' . $this->request->getControllerObjectName() . '"');
        }
        $view->setControllerContext($this->controllerContext);
        if (method_exists($view, 'injectSettings')) {
            $view->injectSettings($this->settings);
        }
        $view->initializeView();
        // In TYPO3.Flow, solved through Object Lifecycle methods, we need to call it explicitly
        $view->assign('settings', $this->settings);
        // same with settings injection.
        return $view;
    }

    /**
     * @param ViewInterface $view
     */
    protected function setViewConfiguration(ViewInterface $view)
    {
        // Template Path Override
        $extbaseFrameworkConfiguration = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
        );

        // set TemplateRootPaths
        $viewFunctionName = 'setTemplateRootPaths';
        if (method_exists($view, $viewFunctionName)) {
            $setting = 'templateRootPaths';
            $parameter = $this->getViewProperty($extbaseFrameworkConfiguration, $setting);
            // no need to bother if there is nothing to set
            if ($parameter) {
                $view->$viewFunctionName($parameter);
            }
        }

        // set LayoutRootPaths
        $viewFunctionName = 'setLayoutRootPaths';
        if (method_exists($view, $viewFunctionName)) {
            $setting = 'layoutRootPaths';
            $parameter = $this->getViewProperty($extbaseFrameworkConfiguration, $setting);
            // no need to bother if there is nothing to set
            if ($parameter) {
                $view->$viewFunctionName($parameter);
            }
        }

        // set PartialRootPaths
        $viewFunctionName = 'setPartialRootPaths';
        if (method_exists($view, $viewFunctionName)) {
            $setting = 'partialRootPaths';
            $parameter = $this->getViewProperty($extbaseFrameworkConfiguration, $setting);
            // no need to bother if there is nothing to set
            if ($parameter) {
                $view->$viewFunctionName($parameter);
            }
        }
    }

    /**
     * Handles the path resolving for *rootPath(s)
     *
     * numerical arrays get ordered by key ascending
     *
     * @param array $extbaseFrameworkConfiguration
     * @param string $setting parameter name from TypoScript
     *
     * @return array
     */
    protected function getViewProperty($extbaseFrameworkConfiguration, $setting)
    {
        $values = [];
        if (
            !empty($extbaseFrameworkConfiguration['view'][$setting])
            && is_array($extbaseFrameworkConfiguration['view'][$setting])
        ) {
            $values = $extbaseFrameworkConfiguration['view'][$setting];
        }

        return $values;
    }

    /**
     * Determines the fully qualified view object name.
     *
     * @return mixed The fully qualified view object name or FALSE if no matching view could be found.
     */
    protected function resolveViewObjectName()
    {
        $vendorName = $this->request->getControllerVendorName();
        if ($vendorName === null) {
            return false;
        }

        $possibleViewName = str_replace(
            [
                '@vendor',
                '@extension',
                '@controller',
                '@action'
            ],
            [
                $vendorName,
                $this->request->getControllerExtensionName(),
                $this->request->getControllerName(),
                ucfirst($this->request->getControllerActionName())
            ],
            $this->namespacesViewObjectNamePattern
        );
        $format = $this->request->getFormat();
        $viewObjectName = str_replace('@format', ucfirst($format), $possibleViewName);
        if (class_exists($viewObjectName) === false) {
            $viewObjectName = str_replace('@format', '', $possibleViewName);
        }
        if (isset($this->viewFormatToObjectNameMap[$format]) && class_exists($viewObjectName) === false) {
            $viewObjectName = $this->viewFormatToObjectNameMap[$format];
        }
        return class_exists($viewObjectName) ? $viewObjectName : false;
    }

    /**
     * Initializes the view before invoking an action method.
     *
     * Override this method to solve assign variables common for all actions
     * or prepare the view in another way before the action is called.
     *
     * @param ViewInterface $view The view to be initialized
     */
    protected function initializeView(ViewInterface $view)
    {
    }

    /**
     * Initializes the controller before invoking an action method.
     *
     * Override this method to solve tasks which all actions have in
     * common.
     */
    protected function initializeAction()
    {
    }

    /**
     * A special action which is called if the originally intended action could
     * not be called, for example if the arguments were not valid.
     *
     * The default implementation sets a flash message, request errors and forwards back
     * to the originating action. This is suitable for most actions dealing with form input.
     *
     * We clear the page cache by default on an error as well, as we need to make sure the
     * data is re-evaluated when the user changes something.
     *
     * @return string
     */
    protected function errorAction()
    {
        $this->clearCacheOnError();
        $this->addErrorFlashMessage();
        $this->forwardToReferringRequest();

        return $this->getFlattenedValidationErrorMessage();
    }

    /**
     * Clear cache of current page on error. Needed because we want a re-evaluation of the data.
     * Better would be just do delete the cache for the error action, but that is not possible right now.
     */
    protected function clearCacheOnError()
    {
        $extbaseSettings = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        if (isset($extbaseSettings['persistence']['enableAutomaticCacheClearing']) && $extbaseSettings['persistence']['enableAutomaticCacheClearing'] === '1') {
            if (isset($GLOBALS['TSFE'])) {
                $pageUid = $GLOBALS['TSFE']->id;
                $this->cacheService->clearPageCache([$pageUid]);
            }
        }
    }

    /**
     * If an error occurred during this request, this adds a flash message describing the error to the flash
     * message container.
     */
    protected function addErrorFlashMessage()
    {
        $errorFlashMessage = $this->getErrorFlashMessage();
        if ($errorFlashMessage !== false) {
            $this->addFlashMessage($errorFlashMessage, '', FlashMessage::ERROR);
        }
    }

    /**
     * A template method for displaying custom error flash messages, or to
     * display no flash message at all on errors. Override this to customize
     * the flash message in your action controller.
     *
     * @return string The flash message or FALSE if no flash message should be set
     */
    protected function getErrorFlashMessage()
    {
        return 'An error occurred while trying to call ' . static::class . '->' . $this->actionMethodName . '()';
    }

    /**
     * If information on the request before the current request was sent, this method forwards back
     * to the originating request. This effectively ends processing of the current request, so do not
     * call this method before you have finished the necessary business logic!
     *
     * @throws StopActionException
     */
    protected function forwardToReferringRequest()
    {
        $referringRequest = $this->request->getReferringRequest();
        if ($referringRequest !== null) {
            $originalRequest = clone $this->request;
            $this->request->setOriginalRequest($originalRequest);
            $this->request->setOriginalRequestMappingResults($this->arguments->validate());
            $this->forward(
                $referringRequest->getControllerActionName(),
                $referringRequest->getControllerName(),
                $referringRequest->getControllerExtensionName(),
                $referringRequest->getArguments()
            );
        }
    }

    /**
     * Returns a string with a basic error message about validation failure.
     * We may add all validation error messages to a log file in the future,
     * but for security reasons (@see #54074) we do not return these here.
     *
     * @return string
     */
    protected function getFlattenedValidationErrorMessage()
    {
        $outputMessage = 'Validation failed while trying to call ' . static::class . '->' . $this->actionMethodName . '().' . PHP_EOL;
        return $outputMessage;
    }

    /**
     * Returns a map of action method names and their parameters.
     *
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
     *
     * @return array Array of method parameters by action name
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    public static function getActionMethodParameters($objectManager)
    {
        trigger_error(
            'Method ' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v10.0.',
            E_USER_DEPRECATED
        );

        $reflectionService = $objectManager->get(\TYPO3\CMS\Extbase\Reflection\ReflectionService::class);

        $result = [];

        $className = get_called_class();
        $methodNames = get_class_methods($className);
        foreach ($methodNames as $methodName) {
            if (strlen($methodName) > 6 && strpos($methodName, 'Action', strlen($methodName) - 6) !== false) {
                $result[$methodName] = $reflectionService->getMethodParameters($className, $methodName);
            }
        }

        return $result;
    }
}
