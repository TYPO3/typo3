<?php

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

namespace TYPO3\CMS\Extbase\Mvc\Controller;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Event\Mvc\BeforeActionCallEvent;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\Exception\RequiredArgumentMissingException;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentNameException;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentTypeException;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchActionException;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\View\GenericViewResolver;
use TYPO3\CMS\Extbase\Mvc\View\JsonView;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Mvc\View\ViewResolverInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Property\Exception\TargetNotFoundException;
use TYPO3\CMS\Extbase\Property\PropertyMapper;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;
use TYPO3\CMS\Extbase\Security\Cryptography\HashService;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;
use TYPO3\CMS\Extbase\Validation\ValidatorResolver;
use TYPO3\CMS\Fluid\View\TemplateView;
use TYPO3Fluid\Fluid\View\AbstractTemplateView;

/**
 * A multi action controller. This is by far the most common base class for Controllers.
 */
abstract class ActionController implements ControllerInterface
{
    /**
     * @var ResponseFactoryInterface
     */
    protected $responseFactory;

    /**
     * @var StreamFactoryInterface
     */
    protected $streamFactory;

    /**
     * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    protected $reflectionService;

    /**
     * @var HashService
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    protected $hashService;

    /**
     * @var ViewResolverInterface
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    private $viewResolver;

    /**
     * The current view, as resolved by resolveView()
     *
     * @var ViewInterface
     * @todo v12: Change signature to TYPO3Fluid\Fluid\View\ViewInterface when extbase ViewInterface is dropped.
     */
    protected $view;

    /**
     * The default view object to use if none of the resolved views can render
     * a response for the current request.
     *
     * @var string
     */
    protected $defaultViewObjectName = TemplateView::class;

    /**
     * Name of the action method
     *
     * @var string
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
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
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * The current request.
     *
     * @var Request
     * @todo v12: Change @var to RequestInterface, when RequestInterface extends ServerRequestInterface
     */
    protected $request;

    /**
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    protected $signalSlotDispatcher;

    /**
     * @var ObjectManagerInterface
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     * @deprecated since v11, will be removed in v12
     */
    protected $objectManager;

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder
     */
    protected $uriBuilder;

    /**
     * Contains the settings of the current extension
     *
     * @var array
     */
    protected $settings;

    /**
     * @var \TYPO3\CMS\Extbase\Validation\ValidatorResolver
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    protected $validatorResolver;

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Controller\Arguments Arguments passed to the controller
     */
    protected $arguments;

    /**
     * @var ControllerContext
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     * @deprecated since v11, will be removed with v12.
     */
    protected $controllerContext;

    /**
     * @var ConfigurationManagerInterface
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    protected $configurationManager;

    /**
     * @var PropertyMapper
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    private $propertyMapper;

    /**
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    private FlashMessageService $internalFlashMessageService;

    /**
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    private ExtensionService $internalExtensionService;

    final public function injectResponseFactory(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    final public function injectStreamFactory(StreamFactoryInterface $streamFactory)
    {
        $this->streamFactory = $streamFactory;
    }

    /**
     * @param ConfigurationManagerInterface $configurationManager
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager)
    {
        $this->configurationManager = $configurationManager;
        $this->settings = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS);
    }

    /**
     * Injects the object manager
     *
     * @param ObjectManagerInterface $objectManager
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     * @deprecated since v11, will be removed in v12
     */
    public function injectObjectManager(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
        // @todo: Move elsewhere
        $this->arguments = GeneralUtility::makeInstance(Arguments::class);
    }

    /**
     * @param \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     * @deprecated since v11, will be removed in v12
     */
    public function injectSignalSlotDispatcher(Dispatcher $signalSlotDispatcher)
    {
        $this->signalSlotDispatcher = $signalSlotDispatcher;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Validation\ValidatorResolver $validatorResolver
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function injectValidatorResolver(ValidatorResolver $validatorResolver)
    {
        $this->validatorResolver = $validatorResolver;
    }

    /**
     * @param ViewResolverInterface $viewResolver
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function injectViewResolver(ViewResolverInterface $viewResolver)
    {
        $this->viewResolver = $viewResolver;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function injectReflectionService(ReflectionService $reflectionService)
    {
        $this->reflectionService = $reflectionService;
    }

    /**
     * @param HashService $hashService
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function injectHashService(HashService $hashService)
    {
        $this->hashService = $hashService;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfigurationService $mvcPropertyMappingConfigurationService
     */
    public function injectMvcPropertyMappingConfigurationService(MvcPropertyMappingConfigurationService $mvcPropertyMappingConfigurationService)
    {
        $this->mvcPropertyMappingConfigurationService = $mvcPropertyMappingConfigurationService;
    }

    public function injectEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param PropertyMapper $propertyMapper
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function injectPropertyMapper(PropertyMapper $propertyMapper): void
    {
        $this->propertyMapper = $propertyMapper;
    }

    /**
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    final public function injectInternalFlashMessageService(FlashMessageService $flashMessageService): void
    {
        $this->internalFlashMessageService = $flashMessageService;
    }

    /**
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    final public function injectInternalExtensionService(ExtensionService $extensionService): void
    {
        $this->internalExtensionService = $extensionService;
    }

    /**
     * Initializes the view before invoking an action method.
     *
     * Override this method to solve assign variables common for all actions
     * or prepare the view in another way before the action is called.
     *
     * @param ViewInterface $view The view to be initialized
     * @deprecated since v11, will be removed in v12: Drop method along with extbase ViewInterface.
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
     * Implementation of the arguments initialization in the action controller:
     * Automatically registers arguments of the current action
     *
     * Don't override this method - use initializeAction() instead.
     *
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentTypeException
     * @see initializeArguments()
     *
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    protected function initializeActionMethodArguments()
    {
        $methodParameters = $this->reflectionService
            ->getClassSchema(static::class)
            ->getMethod($this->actionMethodName)->getParameters();

        foreach ($methodParameters as $parameterName => $parameter) {
            $dataType = null;
            if ($parameter->getType() !== null) {
                $dataType = $parameter->getType();
            } elseif ($parameter->isArray()) {
                $dataType = 'array';
            }
            if ($dataType === null) {
                throw new InvalidArgumentTypeException('The argument type for parameter $' . $parameterName . ' of method ' . static::class . '->' . $this->actionMethodName . '() could not be detected.', 1253175643);
            }
            $defaultValue = $parameter->hasDefaultValue() ? $parameter->getDefaultValue() : null;
            $this->arguments->addNewArgument($parameterName, $dataType, !$parameter->isOptional(), $defaultValue);
        }
    }

    /**
     * Adds the needed validators to the Arguments:
     *
     * - Validators checking the data type from the @param annotation
     * - Custom validators specified with validate annotations.
     * - Model-based validators (validate annotations in the model)
     * - Custom model validator classes
     *
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    protected function initializeActionMethodValidators()
    {
        if ($this->arguments->count() === 0) {
            return;
        }

        $classSchemaMethod = $this->reflectionService->getClassSchema(static::class)
            ->getMethod($this->actionMethodName);

        /** @var Argument $argument */
        foreach ($this->arguments as $argument) {
            $classSchemaMethodParameter = $classSchemaMethod->getParameter($argument->getName());
            // At this point validation is skipped if there is an IgnoreValidation annotation.
            // @todo: IgnoreValidation annotations could be evaluated in the ClassSchema and result in
            //        no validators being applied to the method parameter.
            if ($classSchemaMethodParameter->ignoreValidation()) {
                continue;
            }

            // @todo: It's quite odd that an instance of ConjunctionValidator is created directly here.
            //        \TYPO3\CMS\Extbase\Validation\ValidatorResolver::getBaseValidatorConjunction could/should be used
            //        here, to benefit of the built in 1st level cache of the ValidatorResolver.
            $validator = GeneralUtility::makeInstance(ConjunctionValidator::class);

            foreach ($classSchemaMethodParameter->getValidators() as $validatorDefinition) {
                if (method_exists($validatorDefinition['className'], 'setOptions')) {
                    /** @var ValidatorInterface $validatorInstance */
                    $validatorInstance = GeneralUtility::makeInstance($validatorDefinition['className']);
                    $validatorInstance->setOptions($validatorDefinition['options']);
                } else {
                    // @deprecated since v11: v12 ValidatorInterface requires setOptions() to be implemented and skips the above test.
                    /** @var ValidatorInterface $validatorInstance */
                    $validatorInstance = GeneralUtility::makeInstance(
                        $validatorDefinition['className'],
                        $validatorDefinition['options']
                    );
                }
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
     * Collects the base validators which were defined for the data type of each
     * controller argument and adds them to the argument's validator chain.
     *
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function initializeControllerArgumentsBaseValidators()
    {
        /** @var Argument $argument */
        foreach ($this->arguments as $argument) {
            $validator = $this->validatorResolver->getBaseValidatorConjunction($argument->getDataType());
            if ($validator !== null) {
                $argument->setValidator($validator);
            }
        }
    }

    /**
     * Handles an incoming request and returns a response object
     *
     * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request The request object
     * @return ResponseInterface
     *
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function processRequest(RequestInterface $request): ResponseInterface
    {
        /** @var Request $request */
        $this->request = $request;
        // @deprecated since v11, will be removed in v12.
        $this->request->setDispatched(true);
        $this->uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $this->uriBuilder->setRequest($request);
        $this->actionMethodName = $this->resolveActionMethodName();
        $this->initializeActionMethodArguments();
        $this->initializeActionMethodValidators();
        $this->mvcPropertyMappingConfigurationService->initializePropertyMappingConfigurationFromRequest($request, $this->arguments);
        $this->initializeAction();
        $actionInitializationMethodName = 'initialize' . ucfirst($this->actionMethodName);
        /** @var callable $callable */
        $callable = [$this, $actionInitializationMethodName];
        if (is_callable($callable)) {
            $callable();
        }
        $this->mapRequestArgumentsToControllerArguments();
        // @deprecated since v11, will be removed with v12.
        $this->controllerContext = $this->buildControllerContext();
        $this->view = $this->resolveView();
        if ($this->view !== null && method_exists($this, 'initializeView')) {
            $this->initializeView($this->view);
        }
        $response = $this->callActionMethod($request);
        $this->renderAssetsForRequest($request);

        return $response;
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
     *
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    protected function renderAssetsForRequest($request)
    {
        if (!$this->view instanceof AbstractTemplateView) {
            // Only AbstractTemplateView (from Fluid engine, so this includes all TYPO3 Views based
            // on TYPO3's AbstractTemplateView) supports renderSection(). The method is not
            // declared on ViewInterface - so we must assert a specific class. We silently skip
            // asset processing if the View doesn't match, so we don't risk breaking custom Views.
            return;
        }
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
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
     * Resolves and checks the current action method name
     *
     * @return string Method name of the current action
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchActionException if the action specified in the request object does not exist (and if there's no default action either).
     *
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    protected function resolveActionMethodName()
    {
        $actionMethodName = $this->request->getControllerActionName() . 'Action';
        if (!method_exists($this, $actionMethodName)) {
            throw new NoSuchActionException('An action "' . $actionMethodName . '" does not exist in controller "' . static::class . '".', 1186669086);
        }
        return $actionMethodName;
    }

    /**
     * Calls the specified action method and passes the arguments.
     *
     * If the action returns a string, it is appended to the content in the
     * response object. If the action doesn't return anything and a valid
     * view exists, the view is rendered automatically.
     *
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    protected function callActionMethod(RequestInterface $request): ResponseInterface
    {
        // incoming request is not needed yet but can be passed into the action in the future like in symfony
        // todo: support this via method-reflection

        $preparedArguments = [];
        /** @var Argument $argument */
        foreach ($this->arguments as $argument) {
            $preparedArguments[] = $argument->getValue();
        }
        $validationResult = $this->arguments->validate();
        if (!$validationResult->hasErrors()) {
            $this->eventDispatcher->dispatch(new BeforeActionCallEvent(static::class, $this->actionMethodName, $preparedArguments));
            $actionResult = $this->{$this->actionMethodName}(...$preparedArguments);
        } else {
            $actionResult = $this->{$this->errorMethodName}();
        }

        if ($actionResult instanceof ResponseInterface) {
            return $actionResult;
        }

        trigger_error(
            sprintf(
                'Controller action %s does not return an instance of %s which is deprecated.',
                static::class . '::' . $this->actionMethodName,
                ResponseInterface::class
            ),
            E_USER_DEPRECATED
        );

        $response = new Response();
        $body = new Stream('php://temp', 'rw');
        if ($actionResult === null && $this->view instanceof ViewInterface) {
            if ($this->view instanceof JsonView) {
                // This is just a fallback solution until v12, when Extbase requires PSR-7 responses to be
                // returned in their controller actions. The header, added below, may gets overwritten in
                // the Extbase bootstrap, depending on the context (FE/BE) and TypoScript configuration.
                $response = $response->withHeader('Content-Type', 'application/json; charset=utf-8');
            }
            $body->write($this->view->render());
        } elseif (is_string($actionResult) && $actionResult !== '') {
            $body->write($actionResult);
        } elseif (is_object($actionResult) && method_exists($actionResult, '__toString')) {
            $body->write((string)$actionResult);
        }

        $body->rewind();
        return $response->withBody($body);
    }

    /**
     * Prepares a view for the current action.
     * By default, this method tries to locate a view with a name matching the current action.
     *
     * @return ViewInterface
     * @todo v12: Change signature to TYPO3Fluid\Fluid\View\ViewInterface when extbase ViewInterface is dropped.
     *
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    protected function resolveView()
    {
        if ($this->viewResolver instanceof GenericViewResolver) {
            /*
             * This setter is not part of the ViewResolverInterface as it's only necessary to set
             * the default view class from this point when using the generic view resolver which
             * must respect the possibly overridden property defaultViewObjectName.
             */
            $this->viewResolver->setDefaultViewClass($this->defaultViewObjectName);
        }

        $view = $this->viewResolver->resolve(
            $this->request->getControllerObjectName(),
            $this->request->getControllerActionName(),
            $this->request->getFormat()
        );

        $this->setViewConfiguration($view);
        // @deprecated since v11, will be removed with v12.
        $view->setControllerContext($this->controllerContext);
        if (method_exists($view, 'injectSettings')) {
            $view->injectSettings($this->settings);
        }
        if (method_exists($view, 'initializeView')) {
            // @deprecated since v11, will be removed with v12. Drop together with removal of extbase ViewInterface.
            $view->initializeView();
        }
        // In TYPO3.Flow, solved through Object Lifecycle methods, we need to call it explicitly
        $view->assign('settings', $this->settings);
        // same with settings injection.
        return $view;
    }

    /**
     * @param ViewInterface $view
     *
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     * @todo v12: Change signature to TYPO3Fluid\Fluid\View\ViewInterface when extbase ViewInterface is dropped.
     */
    protected function setViewConfiguration(ViewInterface $view)
    {
        // Template Path Override
        $extbaseFrameworkConfiguration = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
        );

        if (method_exists($view, 'setTemplateRootPaths')) {
            $setting = 'templateRootPaths';
            $parameter = $this->getViewProperty($extbaseFrameworkConfiguration, $setting);
            // no need to bother if there is nothing to set
            if ($parameter) {
                $view->setTemplateRootPaths($parameter);
            }
        }

        if (method_exists($view, 'setLayoutRootPaths')) {
            $setting = 'layoutRootPaths';
            $parameter = $this->getViewProperty($extbaseFrameworkConfiguration, $setting);
            // no need to bother if there is nothing to set
            if ($parameter) {
                $view->setLayoutRootPaths($parameter);
            }
        }

        if (method_exists($view, 'setPartialRootPaths')) {
            $setting = 'partialRootPaths';
            $parameter = $this->getViewProperty($extbaseFrameworkConfiguration, $setting);
            // no need to bother if there is nothing to set
            if ($parameter) {
                $view->setPartialRootPaths($parameter);
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
     *
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
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
     * A special action which is called if the originally intended action could
     * not be called, for example if the arguments were not valid.
     *
     * The default implementation sets a flash message, request errors and forwards back
     * to the originating action. This is suitable for most actions dealing with form input.
     *
     * We clear the page cache by default on an error as well, as we need to make sure the
     * data is re-evaluated when the user changes something.
     *
     * @return ResponseInterface
     */
    protected function errorAction()
    {
        $this->addErrorFlashMessage();
        if (($response = $this->forwardToReferringRequest()) !== null) {
            return $response->withStatus(400);
        }

        $response = $this->htmlResponse($this->getFlattenedValidationErrorMessage());
        return $response->withStatus(400);
    }

    /**
     * If an error occurred during this request, this adds a flash message describing the error to the flash
     * message container.
     *
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
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
     * @return string|bool The flash message or FALSE if no flash message should be set
     *
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
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
     * @return ResponseInterface|null
     *
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    protected function forwardToReferringRequest(): ?ResponseInterface
    {
        $referringRequestArguments = $this->request->getInternalArguments()['__referrer'] ?? null;
        if (is_string($referringRequestArguments['@request'] ?? null)) {
            $referrerArray = json_decode(
                $this->hashService->validateAndStripHmac($referringRequestArguments['@request']),
                true
            );
            $arguments = [];
            if (is_string($referringRequestArguments['arguments'] ?? null)) {
                $arguments = unserialize(
                    base64_decode($this->hashService->validateAndStripHmac($referringRequestArguments['arguments']))
                );
            }
            $replacedArguments = array_replace_recursive($arguments, $referrerArray);
            $nonExtbaseBaseArguments = [];
            foreach ($replacedArguments as $argumentName => $argumentValue) {
                if (!is_string($argumentName) || $argumentName === '') {
                    throw new InvalidArgumentNameException('Invalid argument name.', 1623940985);
                }
                if (str_starts_with($argumentName, '__')
                    || in_array($argumentName, ['@extension', '@subpackage', '@controller', '@action', '@format'], true)
                ) {
                    // Don't handle internalArguments here, not needed for forwardResponse()
                    continue;
                }
                $nonExtbaseBaseArguments[$argumentName] = $argumentValue;
            }
            return (new ForwardResponse((string)($replacedArguments['@action'] ?? 'index')))
                ->withControllerName((string)($replacedArguments['@controller'] ?? 'Standard'))
                ->withExtensionName((string)($replacedArguments['@extension'] ?? ''))
                ->withArguments($nonExtbaseBaseArguments)
                ->withArgumentsValidationResult($this->arguments->validate());
        }

        return null;
    }

    /**
     * Returns a string with a basic error message about validation failure.
     * We may add all validation error messages to a log file in the future,
     * but for security reasons (@see #54074) we do not return these here.
     *
     * @return string
     *
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    protected function getFlattenedValidationErrorMessage()
    {
        $outputMessage = 'Validation failed while trying to call ' . static::class . '->' . $this->actionMethodName . '().' . PHP_EOL;
        return $outputMessage;
    }

    /**
     * @return ControllerContext
     * @deprecated since v11, will be removed with v12.
     */
    public function getControllerContext()
    {
        return $this->controllerContext;
    }

    /**
     * Creates a Message object and adds it to the FlashMessageQueue.
     *
     * @param string $messageBody The message
     * @param string $messageTitle Optional message title
     * @param int $severity Optional severity, must be one of \TYPO3\CMS\Core\Messaging\FlashMessage constants
     * @param bool $storeInSession Optional, defines whether the message should be stored in the session (default) or not
     * @throws \InvalidArgumentException if the message body is no string
     * @see \TYPO3\CMS\Core\Messaging\FlashMessage
     */
    public function addFlashMessage($messageBody, $messageTitle = '', $severity = AbstractMessage::OK, $storeInSession = true)
    {
        if (!is_string($messageBody)) {
            throw new \InvalidArgumentException('The message body must be of type string, "' . gettype($messageBody) . '" given.', 1243258395);
        }
        /* @var \TYPO3\CMS\Core\Messaging\FlashMessage $flashMessage */
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            (string)$messageBody,
            (string)$messageTitle,
            $severity,
            $storeInSession
        );

        $this->getFlashMessageQueue()->enqueue($flashMessage);
    }

    /**
     * todo: As soon as the incoming request contains the compiled plugin namespace, extbase will offer a trait to
     *       create a flash message identifier from the current request. Users then should inject the flash message
     *       service themselves if needed.
     *
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    protected function getFlashMessageQueue(string $identifier = null): FlashMessageQueue
    {
        if ($identifier === null) {
            $pluginNamespace = $this->internalExtensionService->getPluginNamespace(
                $this->request->getControllerExtensionName(),
                $this->request->getPluginName()
            );
            $identifier = 'extbase.flashmessages.' . $pluginNamespace;
        }

        return $this->internalFlashMessageService->getMessageQueueByIdentifier($identifier);
    }

    /**
     * Initialize the controller context
     *
     * @return ControllerContext ControllerContext to be passed to the view
     *
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     * @deprecated since v11, will be removed with v12.
     */
    protected function buildControllerContext()
    {
        $controllerContext = GeneralUtility::makeInstance(ControllerContext::class);
        $controllerContext->setRequest($this->request);
        if ($this->arguments !== null) {
            $controllerContext->setArguments($this->arguments);
        }
        $controllerContext->setUriBuilder($this->uriBuilder);

        return $controllerContext;
    }

    /**
     * Forwards the request to another action and / or controller.
     *
     * Request is directly transferred to the other action / controller
     * without the need for a new request.
     *
     * @param string $actionName Name of the action to forward to
     * @param string|null $controllerName Unqualified object name of the controller to forward to. If not specified, the current controller is used.
     * @param string|null $extensionName Name of the extension containing the controller to forward to. If not specified, the current extension is assumed.
     * @param array|null $arguments Arguments to pass to the target action
     * @throws StopActionException
     * @see redirect()
     * @deprecated since TYPO3 11.0, will be removed in 12.0
     */
    public function forward($actionName, $controllerName = null, $extensionName = null, array $arguments = null)
    {
        trigger_error(
            sprintf('Method %s is deprecated. To forward to another action, return a %s instead.', __METHOD__, ForwardResponse::class),
            E_USER_DEPRECATED
        );

        $this->request->setDispatched(false);
        $this->request->setControllerActionName($actionName);

        if ($controllerName !== null) {
            $this->request->setControllerName($controllerName);
        }

        if ($extensionName !== null) {
            $this->request->setControllerExtensionName($extensionName);
        }

        if ($arguments !== null) {
            $this->request->setArguments($arguments);
        }
        throw new StopActionException('forward', 1476045801);
    }

    /**
     * Redirects the request to another action and / or controller.
     *
     * Redirect will be sent to the client which then performs another request to the new URI.
     *
     * @param string $actionName Name of the action to forward to
     * @param string|null $controllerName Unqualified object name of the controller to forward to. If not specified, the current controller is used.
     * @param string|null $extensionName Name of the extension containing the controller to forward to. If not specified, the current extension is assumed.
     * @param array|null $arguments Arguments to pass to the target action
     * @param int|null $pageUid Target page uid. If NULL, the current page uid is used
     * @param null $_ (optional) Unused
     * @param int $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other
     * @throws StopActionException deprecated since TYPO3 11.0, method will RETURN a Core\Http\RedirectResponse instead of throwing in v12
     * @todo: ': ResponseInterface' (without ?) in v12 as method return type together with redirectToUri() cleanup
     */
    protected function redirect($actionName, $controllerName = null, $extensionName = null, array $arguments = null, $pageUid = null, $_ = null, $statusCode = 303): void
    {
        if ($controllerName === null) {
            $controllerName = $this->request->getControllerName();
        }
        $this->uriBuilder->reset()->setCreateAbsoluteUri(true);
        if (MathUtility::canBeInterpretedAsInteger($pageUid)) {
            $this->uriBuilder->setTargetPageUid((int)$pageUid);
        }
        if (GeneralUtility::getIndpEnv('TYPO3_SSL')) {
            $this->uriBuilder->setAbsoluteUriScheme('https');
        }
        $uri = $this->uriBuilder->uriFor($actionName, $arguments, $controllerName, $extensionName);
        $this->redirectToUri($uri, null, $statusCode);
    }

    /**
     * Redirects the web request to another uri.
     *
     * @param mixed $uri A string representation of a URI
     * @param null $_ (optional) Unused
     * @param int $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other"
     * @throws StopActionException deprecated since TYPO3 11.0, will be removed in 12.0
     * @todo: ': ResponseInterface' (without ?) in v12 as method return type together with redirectToUri() cleanup
     */
    protected function redirectToUri($uri, $_ = null, $statusCode = 303): void
    {
        $uri = $this->addBaseUriIfNecessary($uri);
        $response = new RedirectResponse($uri, $statusCode);
        // @deprecated since v11, will be removed in v12. RETURN the response instead. See Dispatcher class, too.
        throw new StopActionException('redirectToUri', 1476045828, null, $response);
    }

    /**
     * Adds the base uri if not already in place.
     *
     * @param string $uri The URI
     * @return string
     *
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    protected function addBaseUriIfNecessary($uri)
    {
        return GeneralUtility::locationHeaderUrl((string)$uri);
    }

    /**
     * Sends the specified HTTP status immediately and only stops to run back through the middleware stack.
     * Note: If any other plugin or content or hook is used within a frontend request, this is skipped by design.
     *
     * @param int $statusCode The HTTP status code
     * @param string $statusMessage A custom HTTP status message
     * @param string $content Body content which further explains the status
     * @throws PropagateResponseException
     */
    public function throwStatus($statusCode, $statusMessage = null, $content = null)
    {
        if ($content === null) {
            $content = $statusCode . ' ' . $statusMessage;
        }
        $response = $this->responseFactory
            ->createResponse((int)$statusCode, (string)$statusMessage)
            ->withBody($this->streamFactory->createStream((string)$content));
        throw new PropagateResponseException($response, 1476045871);
    }

    /**
     * Maps arguments delivered by the request object to the local controller arguments.
     *
     * @throws Exception\RequiredArgumentMissingException
     *
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    protected function mapRequestArgumentsToControllerArguments()
    {
        /** @var Argument $argument */
        foreach ($this->arguments as $argument) {
            $argumentName = $argument->getName();
            if ($this->request->hasArgument($argumentName)) {
                $this->setArgumentValue($argument, $this->request->getArgument($argumentName));
            } elseif ($argument->isRequired()) {
                throw new RequiredArgumentMissingException('Required argument "' . $argumentName . '" is not set for ' . $this->request->getControllerObjectName() . '->' . $this->request->getControllerActionName() . '.', 1298012500);
            }
        }
    }

    /**
     * @param Argument $argument
     * @param mixed $rawValue
     */
    private function setArgumentValue(Argument $argument, $rawValue): void
    {
        if ($rawValue === null) {
            $argument->setValue(null);
            return;
        }
        $dataType = $argument->getDataType();
        if (is_object($rawValue) && $rawValue instanceof $dataType) {
            $argument->setValue($rawValue);
            return;
        }
        $this->propertyMapper->resetMessages();
        try {
            $argument->setValue(
                $this->propertyMapper->convert(
                    $rawValue,
                    $dataType,
                    $argument->getPropertyMappingConfiguration()
                )
            );
        } catch (TargetNotFoundException $e) {
            // for optional arguments no exception is thrown.
            if ($argument->isRequired()) {
                throw $e;
            }
        }
        $argument->getValidationResults()->merge($this->propertyMapper->getMessages());
    }

    /**
     * Returns a response object with either the given html string or the current rendered view as content.
     *
     * @param string|null $html
     * @return ResponseInterface
     */
    protected function htmlResponse(string $html = null): ResponseInterface
    {
        return $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withBody($this->streamFactory->createStream($html ?? $this->view->render()));
    }

    /**
     * Returns a response object with either the given json string or the current rendered
     * view as content. Mainly to be used for actions / controllers using the JsonView.
     *
     * @param string|null $json
     * @return ResponseInterface
     */
    protected function jsonResponse(string $json = null): ResponseInterface
    {
        return $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withBody($this->streamFactory->createStream($json ?? $this->view->render()));
    }
}
