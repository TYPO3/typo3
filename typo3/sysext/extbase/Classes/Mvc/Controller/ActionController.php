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
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\View\FluidViewAdapter;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Event\Mvc\BeforeActionCallEvent;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\Exception\RequiredArgumentMissingException;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentNameException;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentTypeException;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchActionException;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\View\GenericViewResolver;
use TYPO3\CMS\Extbase\Mvc\View\JsonView;
use TYPO3\CMS\Extbase\Mvc\View\ViewResolverInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Property\Exception\TargetNotFoundException;
use TYPO3\CMS\Extbase\Property\PropertyMapper;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;
use TYPO3\CMS\Extbase\Security\HashScope;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;
use TYPO3\CMS\Extbase\Validation\ValidatorResolver;
use TYPO3\CMS\Fluid\View\TemplatePaths;
use TYPO3Fluid\Fluid\View\AbstractTemplateView;
use TYPO3Fluid\Fluid\View\ViewInterface as FluidStandaloneViewInterface;

/**
 * A multi action controller. This is by far the most common base class for Controllers.
 */
abstract class ActionController implements ControllerInterface
{
    protected ResponseFactoryInterface $responseFactory;
    protected StreamFactoryInterface $streamFactory;
    protected HashService $hashService;

    /**
     * @internal
     */
    protected ReflectionService $reflectionService;

    /**
     * @internal
     */
    private ViewResolverInterface $viewResolver;

    /**
     * The current view, as resolved by resolveView()
     * @todo Use "protected ViewInterface $view;" in v14.
     * @var FluidStandaloneViewInterface|ViewInterface $view
     */
    protected $view;

    /**
     * The default view class to use. Keep this 'null' for default fluid
     * view, or set to 'JsonView::class' or some inheriting class.
     *
     * @var class-string|null
     */
    protected ?string $defaultViewObjectName = null;

    /**
     * Name of the action method
     * @var non-empty-string
     * @internal
     */
    protected string $actionMethodName = 'indexAction';

    /**
     * Name of the special error action method which is called in case of errors
     */
    protected string $errorMethodName = 'errorAction';

    protected MvcPropertyMappingConfigurationService $mvcPropertyMappingConfigurationService;
    protected EventDispatcherInterface $eventDispatcher;
    protected RequestInterface $request;
    protected UriBuilder $uriBuilder;

    /**
     * Contains the settings of the current extension
     */
    protected array $settings;

    /**
     * @internal
     */
    protected ValidatorResolver $validatorResolver;

    private ViewFactoryInterface $viewFactory;

    protected Arguments $arguments;

    /**
     * @internal
     */
    protected ConfigurationManagerInterface $configurationManager;

    /**
     * @internal
     */
    private PropertyMapper $propertyMapper;

    /**
     * @internal
     */
    private FlashMessageService $internalFlashMessageService;

    /**
     * @internal
     */
    private ExtensionService $internalExtensionService;

    final public function injectResponseFactory(ResponseFactoryInterface $responseFactory): void
    {
        $this->responseFactory = $responseFactory;
    }

    final public function injectStreamFactory(StreamFactoryInterface $streamFactory): void
    {
        $this->streamFactory = $streamFactory;
    }

    /**
     * @internal
     */
    public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager): void
    {
        $this->configurationManager = $configurationManager;
        $this->settings = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS);
        $this->arguments = GeneralUtility::makeInstance(Arguments::class);
    }

    /**
     * @internal
     */
    public function injectValidatorResolver(ValidatorResolver $validatorResolver): void
    {
        $this->validatorResolver = $validatorResolver;
    }

    /**
     * @internal
     */
    public function injectViewResolver(ViewResolverInterface $viewResolver): void
    {
        $this->viewResolver = $viewResolver;
    }

    final public function injectViewFactory(ViewFactoryInterface $viewFactory): void
    {
        $this->viewFactory = $viewFactory;
    }

    /**
     * @internal
     */
    public function injectReflectionService(ReflectionService $reflectionService): void
    {
        $this->reflectionService = $reflectionService;
    }

    /**
     * @internal
     */
    public function injectHashService(HashService $hashService): void
    {
        $this->hashService = $hashService;
    }

    public function injectMvcPropertyMappingConfigurationService(MvcPropertyMappingConfigurationService $mvcPropertyMappingConfigurationService): void
    {
        $this->mvcPropertyMappingConfigurationService = $mvcPropertyMappingConfigurationService;
    }

    public function injectEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @internal
     */
    public function injectPropertyMapper(PropertyMapper $propertyMapper): void
    {
        $this->propertyMapper = $propertyMapper;
    }

    /**
     * @internal
     */
    final public function injectInternalFlashMessageService(FlashMessageService $flashMessageService): void
    {
        $this->internalFlashMessageService = $flashMessageService;
    }

    /**
     * @internal
     */
    final public function injectInternalExtensionService(ExtensionService $extensionService): void
    {
        $this->internalExtensionService = $extensionService;
    }

    /**
     * Initializes the controller before invoking an action method.
     *
     * Override this method to solve tasks which all actions have in
     * common.
     */
    protected function initializeAction(): void {}

    /**
     * Implementation of the arguments initialization in the action controller:
     * Automatically registers arguments of the current action
     *
     * Don't override this method - use initializeAction() instead.
     *
     * @throws InvalidArgumentTypeException
     * @see initializeArguments()
     *
     * @internal
     */
    protected function initializeActionMethodArguments(): void
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
     * - Validators checking the data type from the param annotation
     * - Custom validators specified with validate annotations.
     * - Model-based validators (validate annotations in the model)
     * - Custom model validator classes
     *
     * @internal
     */
    protected function initializeActionMethodValidators(): void
    {
        if ($this->arguments->count() === 0) {
            return;
        }

        $classSchemaMethod = $this->reflectionService->getClassSchema(static::class)->getMethod($this->actionMethodName);

        /** @var Argument $argument */
        foreach ($this->arguments as $argument) {
            $classSchemaMethodParameter = $classSchemaMethod->getParameter($argument->getName());
            // At this point validation is skipped if there is an IgnoreValidation annotation.
            // @todo: IgnoreValidation annotations could be evaluated in the ClassSchema and result in
            //        no validators being applied to the method parameter.
            if ($classSchemaMethodParameter->ignoreValidation()) {
                continue;
            }
            /** @var ConjunctionValidator $validator */
            $validator = $this->validatorResolver->createValidator(ConjunctionValidator::class);
            foreach ($classSchemaMethodParameter->getValidators() as $validatorDefinition) {
                /** @var ValidatorInterface $validatorInstance */
                $validatorInstance = $this->validatorResolver->createValidator(
                    $validatorDefinition['className'],
                    $validatorDefinition['options'],
                    $this->request
                );
                $validator->addValidator(
                    $validatorInstance
                );
            }
            $baseValidatorConjunction = $this->validatorResolver->getBaseValidatorConjunction(
                $argument->getDataType(),
                $this->request
            );
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
     * @internal
     */
    public function initializeControllerArgumentsBaseValidators(): void
    {
        /** @var Argument $argument */
        foreach ($this->arguments as $argument) {
            $validator = $this->validatorResolver->getBaseValidatorConjunction(
                $argument->getDataType(),
                $this->request
            );
            if ($validator !== null) {
                $argument->setValidator($validator);
            }
        }
    }

    /**
     * Handles an incoming request and returns a response object
     *
     * @internal
     */
    public function processRequest(RequestInterface $request): ResponseInterface
    {
        /** @var Request $request */
        $this->request = $request;
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
        $this->view = $this->resolveView();
        if (method_exists($this, 'initializeView')) {
            // @todo: We may want to get rid of this and declare actions should actively create own
            //        views using ViewFactoryInterface instead. See comment on resolveView() below.
            //        Currently, this method is pretty much only helpful in 'xclass' scenarios,
            //        since actions can already do whatever happens here within their action body.
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
     * @internal
     */
    protected function renderAssetsForRequest(RequestInterface $request): void
    {
        if (!($this->view instanceof AbstractTemplateView) && !($this->view instanceof FluidViewAdapter)) {
            // @todo: Simplify to if (!($this->view instanceof FluidViewAdapter)) in v14.
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
     * @throws NoSuchActionException if the action specified in the request object does not exist (and if there's no default action either).
     *
     * @internal
     */
    protected function resolveActionMethodName(): string
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
     * @internal
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
        throw new \RuntimeException(
            sprintf(
                'Controller action %s did not return an instance of %s.',
                static::class . '::' . $this->actionMethodName,
                ResponseInterface::class
            ),
            1638554283
        );
    }

    /**
     * Prepares a view for the current action.
     *
     * @internal
     * @todo Set "protected function resolveView(): ViewInterface" in v14.
     * @todo We may want to decide in extbase to go away from the automatic view preparation via
     *       processRequest() and this method for actions. We could very well postulate actions
     *       should take care of creating "their" view on their own using a ViewFactoryInterface
     *       implementation, similar to what is done with request creation already (which needs
     *       further work, too), and have a helper in this class to easily create a standard view.
     *       This would dissolve the ugly $this->defaultViewObjectName property, which is more
     *       a burden than helpful since controllers then need to have an initializeFooAction()
     *       just to set this property when different actions want different views. Also, it does
     *       not allow actions to have no view prepared at all, for instance when they just want to
     *       create a json response by json_encode()'ing stuff. We should look at this in v14, when
     *       GenericViewResolver and ViewResolverInterface are gone, which renders property
     *       defaultViewObjectName even more useless.
     */
    protected function resolveView(): FluidStandaloneViewInterface|ViewInterface
    {
        if ($this->defaultViewObjectName !== null && is_a($this->defaultViewObjectName, JsonView::class, true)) {
            // @todo: JsonView is a very extbase specific thing. It comes with setVariablesToRender() and
            //        setConfiguration(). We don't let it run through a factory here, since consumers need
            //        to deal with these specialities anyways. Often, one would rather want to either have
            //        an own view prepared in a controller (or action), or have a custom factory that deals
            //        with stuff and returns a ViewInterface, or directly json_encode() data in an action.
            //        This is related to the comment above, too.
            $view = new JsonView();
            $view->assign('settings', $this->settings);
            return $view;
        }
        $configuration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        $extensionKey = $this->request->getControllerExtensionKey();
        $templateRootPaths = ['EXT:' . $extensionKey . '/Resources/Private/Templates/'];
        if (!empty($configuration['view']['templateRootPaths']) && is_array($configuration['view']['templateRootPaths'])) {
            $templateRootPaths = array_merge($templateRootPaths, ArrayUtility::sortArrayWithIntegerKeys($configuration['view']['templateRootPaths']));
        }
        $layoutRootPaths = ['EXT:' . $extensionKey . '/Resources/Private/Layouts/'];
        if (!empty($configuration['view']['layoutRootPaths']) && is_array($configuration['view']['layoutRootPaths'])) {
            $layoutRootPaths = array_merge($layoutRootPaths, ArrayUtility::sortArrayWithIntegerKeys($configuration['view']['layoutRootPaths']));
        }
        $partialRootPaths = ['EXT:' . $extensionKey . '/Resources/Private/Partials/'];
        if (!empty($configuration['view']['partialRootPaths']) && is_array($configuration['view']['partialRootPaths'])) {
            $partialRootPaths = array_merge($partialRootPaths, ArrayUtility::sortArrayWithIntegerKeys($configuration['view']['partialRootPaths']));
        }
        if ($this->defaultViewObjectName === null) {
            $viewFactoryData = new ViewFactoryData(
                templateRootPaths: $templateRootPaths,
                partialRootPaths: $partialRootPaths,
                layoutRootPaths: $layoutRootPaths,
                request: $this->request,
                format: $this->request->getFormat(),
            );
            $view = $this->viewFactory->create($viewFactoryData);
            if ($view instanceof FluidViewAdapter) {
                // This specific magic is tailored to Fluid. Ignore if we're not dealing with a fluid view here.
                $renderingContext = $view->getRenderingContext();
                $renderingContext->setControllerName($this->request->getControllerName());
                $renderingContext->setControllerAction($this->request->getControllerActionName());
            }
            $view->assign('settings', $this->settings);
            return $view;
        }
        // @deprecated Drop everything below in v14 and remove GenericViewResolver and ViewResolverInterface
        trigger_error(
            'The only allowed values for $this->defaultViewObjectName are null or extbase JsonView::class. Please'
            . ' create an own view in your action if that is not sufficient, or inject a different ViewFactoryInterface',
            E_USER_DEPRECATED
        );
        if ($this->viewResolver instanceof GenericViewResolver) {
            $this->viewResolver->setDefaultViewClass($this->defaultViewObjectName);
        }
        $view = $this->viewResolver->resolve($this->request->getControllerObjectName(), $this->request->getControllerActionName(), $this->request->getFormat());
        if ($view instanceof FluidViewAdapter || method_exists($view, 'getRenderingContext')) {
            // This specific magic is tailored to Fluid. Ignore if we're not dealing with a fluid view here.
            $renderingContext = $view->getRenderingContext();
            $renderingContext->setAttribute(ServerRequestInterface::class, $this->request);
            $renderingContext->setControllerName($this->request->getControllerName());
            $renderingContext->setControllerAction($this->request->getControllerActionName());
            /** @var TemplatePaths $templatePaths */
            $templatePaths = $renderingContext->getTemplatePaths();
            $templatePaths->setTemplateRootPaths($templateRootPaths);
            $templatePaths->setPartialRootPaths($partialRootPaths);
            $templatePaths->setLayoutRootPaths($layoutRootPaths);
            $templatePaths->setFormat($this->request->getFormat());
        }
        $view->assign('settings', $this->settings);
        return $view;
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
     */
    protected function errorAction(): ResponseInterface
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
     * @internal
     */
    protected function addErrorFlashMessage(): void
    {
        $errorFlashMessage = $this->getErrorFlashMessage();
        if ($errorFlashMessage !== false) {
            $this->addFlashMessage($errorFlashMessage, '', ContextualFeedbackSeverity::ERROR);
        }
    }

    /**
     * A template method for displaying custom error flash messages, or to
     * display no flash message at all on errors. Override this to customize
     * the flash message in your action controller.
     *
     * Returns either the flash message or "false" if no flash message should be set
     */
    protected function getErrorFlashMessage(): bool|string
    {
        return 'An error occurred while trying to call ' . static::class . '->' . $this->actionMethodName . '()';
    }

    /**
     * If information on the request before the current request was sent, this method forwards back
     * to the originating request. This effectively ends processing of the current request, so do not
     * call this method before you have finished the necessary business logic!
     *
     *
     * @internal
     */
    protected function forwardToReferringRequest(): ?ResponseInterface
    {
        /** @var ExtbaseRequestParameters $extbaseRequestParameters */
        $extbaseRequestParameters = $this->request->getAttribute('extbase');
        $referringRequestArguments = $extbaseRequestParameters->getInternalArgument('__referrer') ?? null;
        if (is_string($referringRequestArguments['@request'] ?? null)) {
            $referrerArray = json_decode(
                $this->hashService->validateAndStripHmac($referringRequestArguments['@request'], HashScope::ReferringRequest->prefix()),
                true
            );
            $arguments = [];
            if (is_string($referringRequestArguments['arguments'] ?? null)) {
                $arguments = unserialize(
                    base64_decode($this->hashService->validateAndStripHmac($referringRequestArguments['arguments'], HashScope::ReferringArguments->prefix()))
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
     * @internal
     */
    protected function getFlattenedValidationErrorMessage(): string
    {
        return 'Validation failed while trying to call ' . static::class . '->' . $this->actionMethodName . '().' . PHP_EOL;
    }

    /**
     * Creates a Message object and adds it to the FlashMessageQueue.
     *
     * @throws \InvalidArgumentException if the message body is no string
     * @see FlashMessage
     */
    public function addFlashMessage(
        string $messageBody,
        string $messageTitle = '',
        ContextualFeedbackSeverity $severity = ContextualFeedbackSeverity::OK,
        bool $storeInSession = true
    ): void {
        /* @var FlashMessage $flashMessage */
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            $messageBody,
            $messageTitle,
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
     * @internal
     */
    protected function getFlashMessageQueue(?string $identifier = null): FlashMessageQueue
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
     * Redirects the request to another action and / or controller.
     *
     * Redirect will be sent to the client which then performs another request to the new URI.
     *
     * @param string|null $actionName Name of the action to forward to
     * @param string|null $controllerName Unqualified object name of the controller to forward to. If not specified, the current controller is used.
     * @param string|null $extensionName Name of the extension containing the controller to forward to. If not specified, the current extension is assumed.
     * @param array|null $arguments Arguments to pass to the target action
     * @param int|null $pageUid Target page uid. If NULL, the current page uid is used
     * @param null $_ (optional) Unused
     * @param int $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other
     */
    protected function redirect(
        ?string $actionName,
        ?string $controllerName = null,
        ?string $extensionName = null,
        ?array $arguments = null,
        ?int $pageUid = null,
        $_ = null,
        int $statusCode = 303
    ): ResponseInterface {
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
        return $this->redirectToUri($uri, null, $statusCode);
    }

    /**
     * Redirects the web request to another uri.
     *
     * @param string|UriInterface $uri A string representation of a URI
     * @param null $_ (optional) Unused
     * @param int $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other"
     */
    protected function redirectToUri(string|UriInterface $uri, $_ = null, int $statusCode = 303): ResponseInterface
    {
        $uri = $this->addBaseUriIfNecessary((string)$uri);
        return new RedirectResponse($uri, $statusCode);
    }

    /**
     * Adds the base uri if not already in place.
     *
     * @internal
     */
    protected function addBaseUriIfNecessary(string $uri): string
    {
        return GeneralUtility::locationHeaderUrl($uri);
    }

    /**
     * Sends the specified HTTP status immediately and only stops to run back through the middleware stack.
     * Note: If any other plugin or content or hook is used within a frontend request, this is skipped by design.
     *
     * @param int $statusCode The HTTP status code
     * @param string $statusMessage A custom HTTP status message
     * @param string|null $content Body content which further explains the status
     * @throws PropagateResponseException
     */
    public function throwStatus(int $statusCode, string $statusMessage = '', ?string $content = null): never
    {
        if ($content === null) {
            $content = $statusCode . ' ' . $statusMessage;
        }
        $response = $this->responseFactory
            ->createResponse($statusCode, $statusMessage)
            ->withBody($this->streamFactory->createStream((string)$content));
        throw new PropagateResponseException($response, 1476045871);
    }

    /**
     * Maps arguments delivered by the request object to the local controller arguments.
     *
     * @throws Exception\RequiredArgumentMissingException
     *
     * @internal
     */
    protected function mapRequestArgumentsToControllerArguments(): void
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

    private function setArgumentValue(Argument $argument, mixed $rawValue): void
    {
        if ($rawValue === null) {
            $argument->setValue(null);
            return;
        }
        $dataType = $argument->getDataType();
        if ($rawValue instanceof $dataType) {
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
     */
    protected function htmlResponse(?string $html = null): ResponseInterface
    {
        return $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withBody($this->streamFactory->createStream(($html ?? $this->view->render())));
    }

    /**
     * Returns a response object with either the given json string or the current rendered
     * view as content. Mainly to be used for actions / controllers using the JsonView.
     */
    protected function jsonResponse(?string $json = null): ResponseInterface
    {
        return $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withBody($this->streamFactory->createStream(($json ?? $this->view->render())));
    }
}
