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

/*
 * Inspired by and partially taken from the Neos.Form package (www.neos.io)
 */

namespace TYPO3\CMS\Form\Domain\Runtime;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Error\Http\BadRequestException;
use TYPO3\CMS\Core\ExpressionLanguage\Resolver;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Mvc\Controller\Arguments;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Property\Exception as PropertyException;
use TYPO3\CMS\Extbase\Security\Cryptography\HashService;
use TYPO3\CMS\Extbase\Security\Exception\InvalidArgumentForHashGenerationException;
use TYPO3\CMS\Extbase\Security\Exception\InvalidHashException;
use TYPO3\CMS\Form\Domain\Exception\RenderingException;
use TYPO3\CMS\Form\Domain\Finishers\FinisherContext;
use TYPO3\CMS\Form\Domain\Finishers\FinisherInterface;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3\CMS\Form\Domain\Model\FormElements\Page;
use TYPO3\CMS\Form\Domain\Model\Renderable\RootRenderableInterface;
use TYPO3\CMS\Form\Domain\Model\Renderable\VariableRenderableInterface;
use TYPO3\CMS\Form\Domain\Renderer\RendererInterface;
use TYPO3\CMS\Form\Domain\Runtime\Exception\PropertyMappingException;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime\FormSession;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime\Lifecycle\AfterFormStateInitializedInterface;
use TYPO3\CMS\Form\Exception as FormException;
use TYPO3\CMS\Form\Mvc\Validation\EmptyValidator;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * This class implements the *runtime logic* of a form, i.e. deciding which
 * page is shown currently, what the current values of the form are, trigger
 * validation and property mapping.
 *
 * You generally receive an instance of this class by calling {@link \TYPO3\CMS\Form\Domain\Model\FormDefinition::bind}.
 *
 * Rendering a Form
 * ================
 *
 * That's easy, just call render() on the FormRuntime:
 *
 * /---code php
 * $form = $formDefinition->bind($request);
 * $renderedForm = $form->render();
 * \---
 *
 * Accessing Form Values
 * =====================
 *
 * In order to get the values the user has entered into the form, you can access
 * this object like an array: If a form field with the identifier *firstName*
 * exists, you can do **$form['firstName']** to retrieve its current value.
 *
 * You can also set values in the same way.
 *
 * Rendering Internals
 * ===================
 *
 * The FormRuntime asks the FormDefinition about the configured Renderer
 * which should be used ({@link \TYPO3\CMS\Form\Domain\Model\FormDefinition::getRendererClassName}),
 * and then trigger render() on this Renderer.
 *
 * This makes it possible to declaratively define how a form should be rendered.
 *
 * Scope: frontend
 * **This class is NOT meant to be sub classed by developers.**
 *
 * @internal High cohesion to FormDefinition, may change any time
 * @todo: Declare final in v12
 */
class FormRuntime implements RootRenderableInterface, \ArrayAccess
{
    const HONEYPOT_NAME_SESSION_IDENTIFIER = 'tx_form_honeypot_name_';

    protected ContainerInterface $container;
    // @deprecated since v11, will be removed in v12
    protected ObjectManagerInterface $objectManager;
    protected ?FormDefinition $formDefinition = null;
    protected ?Request $request = null;
    protected ResponseInterface $response;
    protected HashService $hashService;
    protected ConfigurationManagerInterface $configurationManager;

    /**
     * @var FormState
     */
    protected $formState;

    /**
     * Individual unique random form session identifier valid
     * for current user session. This value is not persisted server-side.
     *
     * @var FormSession|null
     */
    protected $formSession;

    /**
     * The current page is the page which will be displayed to the user
     * during rendering.
     *
     * If $currentPage is NULL, the *last* page has been submitted and
     * finishing actions need to take place. You should use $this->isAfterLastPage()
     * instead of explicitly checking for NULL.
     *
     * @var Page|null
     */
    protected $currentPage;

    /**
     * Reference to the page which has been shown on the last request (i.e.
     * we have to handle the submitted data from lastDisplayedPage)
     *
     * @var Page
     */
    protected $lastDisplayedPage;

    /**
     * The current site language configuration.
     *
     * @var SiteLanguage
     */
    protected $currentSiteLanguage;

    /**
     * Reference to the current running finisher
     *
     * @var FinisherInterface
     */
    protected $currentFinisher;

    /**
     * Only TRUE if there is a post request and if the requested arguments
     * are meant for the loaded form definition.
     * The component that integrates this FormRuntime must ensure that
     * the content is rendered uncached in the case of a POST request.
     */
    protected bool $canProcessFormSubmission = false;

    public function __construct(
        ContainerInterface $container,
        ObjectManagerInterface $objectManager,
        ConfigurationManagerInterface $configurationManager,
        HashService $hashService
    ) {
        $this->container = $container;
        // @deprecated since v11, will be removed in v12
        $this->objectManager = $objectManager;
        $this->configurationManager = $configurationManager;
        $this->hashService = $hashService;
        $this->response = new Response();
    }

    public function setFormDefinition(FormDefinition $formDefinition)
    {
        $this->formDefinition = $formDefinition;
    }

    public function setRequest(Request $request)
    {
        $this->request = clone $request;
    }

    public function initialize()
    {
        $arguments = $this->request->getArguments();
        $formIdentifier = $this->formDefinition->getIdentifier();
        // There are arguments which are intended for the formDefinition
        // currently being rendered
        // (in the case of multiple forms being rendered on the same page)
        if (isset($arguments[$formIdentifier])) {
            $this->request->setArguments($arguments[$formIdentifier]);
            // There is a post request
            if ($this->isPostRequest()) {
                $this->canProcessFormSubmission = true;
            }
        }

        $this->initializeCurrentSiteLanguage();
        $this->initializeFormSessionFromRequest();
        $this->initializeFormStateFromRequest();
        $this->triggerAfterFormStateInitialized();
        $this->processVariants();
        $this->initializeCurrentPageFromRequest();
        $this->initializeHoneypotFromRequest();

        // Only validate and set form values within the form state
        // if the current request is not the very first request
        // and the current request can be processed (POST request).
        if (!$this->isFirstRequest() && $this->canProcessFormSubmission()) {
            $this->processSubmittedFormValues();
        }

        $this->renderHoneypot();
    }

    /**
     * @todo `FormRuntime::$formSession` is still vulnerable to session fixation unless a real cookie-based process is used
     */
    protected function initializeFormSessionFromRequest(): void
    {
        // Initialize the form session only if the current request can be processed
        // (POST request) to ensure unique sessions for each form submitter.
        if (!$this->canProcessFormSubmission()) {
            return;
        }

        $sessionIdentifierFromRequest = $this->request->getInternalArgument('__session');
        $this->formSession = GeneralUtility::makeInstance(FormSession::class, $sessionIdentifierFromRequest);
    }

    /**
     * Initializes the current state of the form, based on the request
     * @throws BadRequestException
     */
    protected function initializeFormStateFromRequest()
    {
        // Only try to reconstitute the form state if the current request
        // is not the very first request and if the current request can
        // be processed (POST request).
        $serializedFormStateWithHmac = $this->request->getInternalArgument('__state');
        if ($serializedFormStateWithHmac === null || !$this->canProcessFormSubmission()) {
            $this->formState = GeneralUtility::makeInstance(FormState::class);
        } else {
            try {
                $serializedFormState = $this->hashService->validateAndStripHmac($serializedFormStateWithHmac);
            } catch (InvalidHashException | InvalidArgumentForHashGenerationException $e) {
                throw new BadRequestException('The HMAC of the form state could not be validated.', 1581862823);
            }
            $this->formState = unserialize(base64_decode($serializedFormState));
        }
    }

    protected function triggerAfterFormStateInitialized(): void
    {
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['afterFormStateInitialized'] ?? [] as $className) {
            $hookObj = GeneralUtility::makeInstance($className);
            if ($hookObj instanceof AfterFormStateInitializedInterface) {
                $hookObj->afterFormStateInitialized($this);
            }
        }
    }

    /**
     * Initializes the current page data based on the current request, also modifiable by a hook
     */
    protected function initializeCurrentPageFromRequest()
    {
        // If there was no previous form submissions or if the current request
        // can't be processed (no POST request) then display the first
        // form step
        if (!$this->formState->isFormSubmitted() || !$this->canProcessFormSubmission()) {
            $this->currentPage = $this->formDefinition->getPageByIndex(0);

            if (!$this->currentPage->isEnabled()) {
                throw new FormException('Disabling the first page is not allowed', 1527186844);
            }

            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['afterInitializeCurrentPage'] ?? [] as $className) {
                $hookObj = GeneralUtility::makeInstance($className);
                if (method_exists($hookObj, 'afterInitializeCurrentPage')) {
                    $this->currentPage = $hookObj->afterInitializeCurrentPage(
                        $this,
                        $this->currentPage,
                        null,
                        $this->request->getArguments()
                    );
                }
            }
            return;
        }

        $this->lastDisplayedPage = $this->formDefinition->getPageByIndex($this->formState->getLastDisplayedPageIndex());
        $currentPageIndex = (int)$this->request->getInternalArgument('__currentPage');

        if ($this->userWentBackToPreviousStep()) {
            if ($currentPageIndex < $this->lastDisplayedPage->getIndex()) {
                $currentPageIndex = $this->lastDisplayedPage->getIndex();
            }
        } else {
            if ($currentPageIndex > $this->lastDisplayedPage->getIndex() + 1) {
                $currentPageIndex = $this->lastDisplayedPage->getIndex() + 1;
            }
        }

        if ($currentPageIndex >= count($this->formDefinition->getPages())) {
            // Last Page
            $this->currentPage = null;
        } else {
            $this->currentPage = $this->formDefinition->getPageByIndex($currentPageIndex);

            if (!$this->currentPage->isEnabled()) {
                if ($currentPageIndex === 0) {
                    throw new FormException('Disabling the first page is not allowed', 1527186845);
                }

                if ($this->userWentBackToPreviousStep()) {
                    $this->currentPage = $this->getPreviousEnabledPage();
                } else {
                    $this->currentPage = $this->getNextEnabledPage();
                }
            }
        }

        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['afterInitializeCurrentPage'] ?? [] as $className) {
            $hookObj = GeneralUtility::makeInstance($className);
            if (method_exists($hookObj, 'afterInitializeCurrentPage')) {
                $this->currentPage = $hookObj->afterInitializeCurrentPage(
                    $this,
                    $this->currentPage,
                    $this->lastDisplayedPage,
                    $this->request->getArguments()
                );
            }
        }
    }

    /**
     * Checks if the honey pot is active, and adds a validator if so.
     */
    protected function initializeHoneypotFromRequest()
    {
        $renderingOptions = $this->formDefinition->getRenderingOptions();
        if (!isset($renderingOptions['honeypot']['enable'])
            || $renderingOptions['honeypot']['enable'] === false
            || (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
                && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend())
        ) {
            return;
        }

        ArrayUtility::assertAllArrayKeysAreValid($renderingOptions['honeypot'], ['enable', 'formElementToUse']);

        if (!$this->isFirstRequest()) {
            $elementsCount = count($this->lastDisplayedPage->getElements());
            if ($elementsCount === 0) {
                return;
            }

            $honeypotNameFromSession = $this->getHoneypotNameFromSession($this->lastDisplayedPage);
            if ($honeypotNameFromSession) {
                $honeypotElement = $this->lastDisplayedPage->createElement($honeypotNameFromSession, $renderingOptions['honeypot']['formElementToUse']);
                $validator = GeneralUtility::makeInstance(EmptyValidator::class);
                $honeypotElement->addValidator($validator);
            }
        }
    }

    /**
     * Renders a hidden field if the honey pot is active.
     */
    protected function renderHoneypot()
    {
        $renderingOptions = $this->formDefinition->getRenderingOptions();
        if (!isset($renderingOptions['honeypot']['enable'])
            || $this->currentPage === null
            || $renderingOptions['honeypot']['enable'] === false
            || (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
                && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend())
        ) {
            return;
        }

        ArrayUtility::assertAllArrayKeysAreValid($renderingOptions['honeypot'], ['enable', 'formElementToUse']);

        if (!$this->isAfterLastPage()) {
            $elementsCount = count($this->currentPage->getElements());
            if ($elementsCount === 0) {
                return;
            }

            if (!$this->isFirstRequest()) {
                $honeypotNameFromSession = $this->getHoneypotNameFromSession($this->lastDisplayedPage);
                if ($honeypotNameFromSession) {
                    $honeypotElement = $this->formDefinition->getElementByIdentifier($honeypotNameFromSession);
                    if ($honeypotElement instanceof FormElementInterface) {
                        $this->lastDisplayedPage->removeElement($honeypotElement);
                    }
                }
            }

            $elementsCount = count($this->currentPage->getElements());
            $randomElementNumber = random_int(0, $elementsCount - 1);
            $honeypotName = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, random_int(5, 26));

            $referenceElement = $this->currentPage->getElements()[$randomElementNumber];
            $honeypotElement = $this->currentPage->createElement($honeypotName, $renderingOptions['honeypot']['formElementToUse']);
            $validator = GeneralUtility::makeInstance(EmptyValidator::class);

            $honeypotElement->addValidator($validator);
            if (random_int(0, 1) === 1) {
                $this->currentPage->moveElementAfter($honeypotElement, $referenceElement);
            } else {
                $this->currentPage->moveElementBefore($honeypotElement, $referenceElement);
            }
            $this->setHoneypotNameInSession($this->currentPage, $honeypotName);
        }
    }

    /**
     * @param Page $page
     * @return string|null
     */
    protected function getHoneypotNameFromSession(Page $page)
    {
        if ($this->isFrontendUserAuthenticated()) {
            $honeypotNameFromSession = $this->getFrontendUser()->getKey(
                'user',
                self::HONEYPOT_NAME_SESSION_IDENTIFIER . $this->getIdentifier() . $page->getIdentifier()
            );
        } else {
            $honeypotNameFromSession = $this->getFrontendUser()->getKey(
                'ses',
                self::HONEYPOT_NAME_SESSION_IDENTIFIER . $this->getIdentifier() . $page->getIdentifier()
            );
        }
        return $honeypotNameFromSession;
    }

    /**
     * @param Page $page
     * @param string $honeypotName
     */
    protected function setHoneypotNameInSession(Page $page, string $honeypotName)
    {
        if ($this->isFrontendUserAuthenticated()) {
            $this->getFrontendUser()->setKey(
                'user',
                self::HONEYPOT_NAME_SESSION_IDENTIFIER . $this->getIdentifier() . $page->getIdentifier(),
                $honeypotName
            );
        } else {
            $this->getFrontendUser()->setKey(
                'ses',
                self::HONEYPOT_NAME_SESSION_IDENTIFIER . $this->getIdentifier() . $page->getIdentifier(),
                $honeypotName
            );
        }
    }

    /**
     * Necessary to know if honeypot information should be stored in the user session info, or in the anonymous session
     *
     * @return bool true when a frontend user is logged, otherwise false
     */
    protected function isFrontendUserAuthenticated(): bool
    {
        return (bool)GeneralUtility::makeInstance(Context::class)
            ->getPropertyFromAspect('frontend.user', 'isLoggedIn', false);
    }

    protected function processVariants()
    {
        $conditionResolver = $this->getConditionResolver();

        $renderables = array_merge([$this->formDefinition], $this->formDefinition->getRenderablesRecursively());
        foreach ($renderables as $renderable) {
            if ($renderable instanceof VariableRenderableInterface) {
                $variants = $renderable->getVariants();
                foreach ($variants as $variant) {
                    if ($variant->conditionMatches($conditionResolver)) {
                        $variant->apply();
                    }
                }
            }
        }
    }

    /**
     * Returns TRUE if the last page of the form has been submitted, otherwise FALSE
     *
     * @return bool
     */
    protected function isAfterLastPage(): bool
    {
        return $this->currentPage === null;
    }

    /**
     * Returns TRUE if no previous page is stored in the FormState, otherwise FALSE
     *
     * @return bool
     */
    protected function isFirstRequest(): bool
    {
        return $this->lastDisplayedPage === null;
    }

    /**
     * @return bool
     */
    protected function isPostRequest(): bool
    {
        return $this->getRequest()->getMethod() === 'POST';
    }

    /**
     * Runs through all validations
     */
    protected function processSubmittedFormValues()
    {
        $result = $this->mapAndValidatePage($this->lastDisplayedPage);
        if ($result->hasErrors() && !$this->userWentBackToPreviousStep()) {
            $this->currentPage = $this->lastDisplayedPage;
            $this->request->setOriginalRequestMappingResults($result);
        }
    }

    /**
     * returns TRUE if the user went back to any previous step in the form.
     *
     * @return bool
     */
    protected function userWentBackToPreviousStep(): bool
    {
        return !$this->isAfterLastPage() && !$this->isFirstRequest() && $this->currentPage->getIndex() < $this->lastDisplayedPage->getIndex();
    }

    /**
     * @param Page $page
     * @return Result
     * @throws PropertyMappingException
     */
    protected function mapAndValidatePage(Page $page): Result
    {
        $result = GeneralUtility::makeInstance(Result::class);
        $requestArguments = $this->request->getArguments();

        $propertyPathsForWhichPropertyMappingShouldHappen = [];
        $registerPropertyPaths = static function ($propertyPath) use (&$propertyPathsForWhichPropertyMappingShouldHappen) {
            $propertyPathParts = explode('.', $propertyPath);
            $accumulatedPropertyPathParts = [];
            foreach ($propertyPathParts as $propertyPathPart) {
                $accumulatedPropertyPathParts[] = $propertyPathPart;
                $temporaryPropertyPath = implode('.', $accumulatedPropertyPathParts);
                $propertyPathsForWhichPropertyMappingShouldHappen[$temporaryPropertyPath] = $temporaryPropertyPath;
            }
        };

        $value = null;

        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['afterSubmit'] ?? [] as $className) {
            $hookObj = GeneralUtility::makeInstance($className);
            if (method_exists($hookObj, 'afterSubmit')) {
                $value = $hookObj->afterSubmit(
                    $this,
                    $page,
                    $value,
                    $requestArguments
                );
            }
        }

        foreach ($page->getElementsRecursively() as $element) {
            if (!$element->isEnabled()) {
                continue;
            }

            try {
                $value = ArrayUtility::getValueByPath($requestArguments, $element->getIdentifier(), '.');
            } catch (MissingArrayPathException $exception) {
                $value = null;
            }

            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['afterSubmit'] ?? [] as $className) {
                $hookObj = GeneralUtility::makeInstance($className);
                if (method_exists($hookObj, 'afterSubmit')) {
                    $value = $hookObj->afterSubmit(
                        $this,
                        $element,
                        $value,
                        $requestArguments
                    );
                }
            }

            $this->formState->setFormValue($element->getIdentifier(), $value);
            $registerPropertyPaths($element->getIdentifier());
        }

        // The more parts the path has, the more early it is processed
        usort($propertyPathsForWhichPropertyMappingShouldHappen, static function ($a, $b) {
            return substr_count($b, '.') - substr_count($a, '.');
        });

        $processingRules = $this->formDefinition->getProcessingRules();

        foreach ($propertyPathsForWhichPropertyMappingShouldHappen as $propertyPath) {
            if (isset($processingRules[$propertyPath])) {
                $processingRule = $processingRules[$propertyPath];
                $value = $this->formState->getFormValue($propertyPath);
                try {
                    $value = $processingRule->process($value);
                } catch (PropertyException $exception) {
                    throw new PropertyMappingException(
                        'Failed to process FormValue at "' . $propertyPath . '" from "' . gettype($value) . '" to "' . $processingRule->getDataType() . '"',
                        1480024933,
                        $exception
                    );
                }
                $result->forProperty($this->getIdentifier() . '.' . $propertyPath)->merge($processingRule->getProcessingMessages());
                $this->formState->setFormValue($propertyPath, $value);
            }
        }

        return $result;
    }

    /**
     * Override the current page taken from the request, rendering the page with index $pageIndex instead.
     *
     * This is typically not needed in production code, but it is very helpful when displaying
     * some kind of "preview" of the form (e.g. form editor).
     *
     * @param int $pageIndex
     */
    public function overrideCurrentPage(int $pageIndex)
    {
        $this->currentPage = $this->formDefinition->getPageByIndex($pageIndex);
    }

    /**
     * Render this form.
     *
     * @return string|null rendered form
     * @throws RenderingException
     */
    public function render()
    {
        if ($this->isAfterLastPage()) {
            return $this->invokeFinishers();
        }
        $this->processVariants();

        $this->formState->setLastDisplayedPageIndex($this->currentPage->getIndex());

        if ($this->formDefinition->getRendererClassName() === '') {
            throw new RenderingException(sprintf('The form definition "%s" does not have a rendererClassName set.', $this->formDefinition->getIdentifier()), 1326095912);
        }
        $rendererClassName = $this->formDefinition->getRendererClassName();
        if ($this->container->has($rendererClassName)) {
            $renderer = $this->container->get($rendererClassName);
        } else {
            // @deprecated since v11, will be removed in v12.
            $renderer = $this->objectManager->get($rendererClassName);
        }
        if (!($renderer instanceof RendererInterface)) {
            throw new RenderingException(sprintf('The renderer "%s" des not implement RendererInterface', $rendererClassName), 1326096024);
        }

        // @deprecated since v11, will be removed with v12.
        $controllerContext = $this->getControllerContext();
        $renderer->setControllerContext($controllerContext);

        $renderer->setFormRuntime($this);
        return $renderer->render();
    }

    /**
     * Executes all finishers of this form
     *
     * @return string
     */
    protected function invokeFinishers(): string
    {
        $finisherContext = GeneralUtility::makeInstance(
            FinisherContext::class,
            $this,
            $this->getControllerContext(), // @deprecated since v11, will be removed with v12.
            $this->request
        );

        $output = '';
        $this->response->getBody()->rewind();
        $originalContent = $this->response->getBody()->getContents();
        $this->response->getBody()->write('');
        foreach ($this->formDefinition->getFinishers() as $finisher) {
            $this->currentFinisher = $finisher;
            $this->processVariants();

            $finisherOutput = $finisher->execute($finisherContext);
            if (is_string($finisherOutput) && !empty($finisherOutput)) {
                $output .= $finisherOutput;
            } else {
                $this->response->getBody()->rewind();
                $output .= $this->response->getBody()->getContents();
                $this->response->getBody()->write('');
            }

            if ($finisherContext->isCancelled()) {
                break;
            }
        }
        $this->response->getBody()->rewind();
        $this->response->getBody()->write($originalContent);

        return $output;
    }

    /**
     * @return string The identifier of underlying form
     */
    public function getIdentifier(): string
    {
        return $this->formDefinition->getIdentifier();
    }

    /**
     * Get the request this object is bound to.
     *
     * This is mostly relevant inside Finishers, where you f.e. want to redirect
     * the user to another page.
     *
     * @return Request the request this object is bound to
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Get the response this object is bound to.
     *
     * This is mostly relevant inside Finishers, where you f.e. want to set response
     * headers or output content.
     *
     * @return ResponseInterface the response this object is bound to
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * Only process values if there is a post request and if the requested
     * arguments are meant for the loaded form definition.
     * Is this not the case, all possible submitted values will be discarded
     * and the first form step will be shown with an empty form state.
     *
     * @return bool
     * @internal
     */
    public function canProcessFormSubmission(): bool
    {
        return $this->canProcessFormSubmission;
    }

    /**
     * @return FormSession|null
     * @internal
     */
    public function getFormSession(): ?FormSession
    {
        return $this->formSession;
    }

    /**
     * Returns the currently selected page
     *
     * @return Page|null
     */
    public function getCurrentPage(): ?Page
    {
        return $this->currentPage;
    }

    /**
     * Returns the previous page of the currently selected one or NULL if there is no previous page
     *
     * @return Page|null
     */
    public function getPreviousPage(): ?Page
    {
        $previousPageIndex = $this->currentPage->getIndex() - 1;
        if ($this->formDefinition->hasPageWithIndex($previousPageIndex)) {
            return $this->formDefinition->getPageByIndex($previousPageIndex);
        }
        return null;
    }

    /**
     * Returns the next page of the currently selected one or NULL if there is no next page
     *
     * @return Page|null
     */
    public function getNextPage(): ?Page
    {
        $nextPageIndex = $this->currentPage->getIndex() + 1;
        if ($this->formDefinition->hasPageWithIndex($nextPageIndex)) {
            return $this->formDefinition->getPageByIndex($nextPageIndex);
        }
        return null;
    }

    /**
     * Returns the previous enabled page of the currently selected one
     * or NULL if there is no previous page
     *
     * @return Page|null
     */
    public function getPreviousEnabledPage(): ?Page
    {
        $previousPage = null;
        $previousPageIndex = $this->currentPage->getIndex() - 1;
        while ($previousPageIndex >= 0) {
            if ($this->formDefinition->hasPageWithIndex($previousPageIndex)) {
                $previousPage = $this->formDefinition->getPageByIndex($previousPageIndex);

                if ($previousPage->isEnabled()) {
                    break;
                }

                $previousPage = null;
                $previousPageIndex--;
            } else {
                $previousPage = null;
                break;
            }
        }

        return $previousPage;
    }

    /**
     * Returns the next enabled page of the currently selected one or
     * NULL if there is no next page
     *
     * @return Page|null
     */
    public function getNextEnabledPage(): ?Page
    {
        $nextPage = null;
        $pageCount = count($this->formDefinition->getPages());
        $nextPageIndex = $this->currentPage->getIndex() + 1;

        while ($nextPageIndex < $pageCount) {
            if ($this->formDefinition->hasPageWithIndex($nextPageIndex)) {
                $nextPage = $this->formDefinition->getPageByIndex($nextPageIndex);
                $renderingOptions = $nextPage->getRenderingOptions();
                if (
                    !isset($renderingOptions['enabled'])
                    || (bool)$renderingOptions['enabled']
                ) {
                    break;
                }
                $nextPage = null;
                $nextPageIndex++;
            } else {
                $nextPage = null;
                break;
            }
        }

        return $nextPage;
    }

    /**
     * @return ControllerContext
     * @deprecated since v11, will be removed with v12.
     */
    protected function getControllerContext(): ControllerContext
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $uriBuilder->setRequest($this->request);
        $controllerContext = GeneralUtility::makeInstance(ControllerContext::class);
        $controllerContext->setRequest($this->request);
        $controllerContext->setArguments(GeneralUtility::makeInstance(Arguments::class));
        $controllerContext->setUriBuilder($uriBuilder);
        return $controllerContext;
    }

    /**
     * Abstract "type" of this Renderable. Is used during the rendering process
     * to determine the template file or the View PHP class being used to render
     * the particular element.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->formDefinition->getType();
    }

    /**
     * @param string $identifier
     * @return bool
     * @internal
     * @todo Set $identifier to mixed type in v12.
     */
    public function offsetExists($identifier): bool
    {
        if ($this->getElementValue($identifier) !== null) {
            return true;
        }

        if (is_callable([$this, 'get' . ucfirst($identifier)])) {
            return true;
        }
        if (is_callable([$this, 'has' . ucfirst($identifier)])) {
            return true;
        }
        if (is_callable([$this, 'is' . ucfirst($identifier)])) {
            return true;
        }
        if (property_exists($this, $identifier)) {
            $propertyReflection = new \ReflectionProperty($this, $identifier);
            return $propertyReflection->isPublic();
        }

        return false;
    }

    /**
     * @param string $identifier
     * @return mixed
     * @internal
     * @todo Set $identifier to mixed type in v12.
     * @todo Set return type to ?mixed in v12.
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($identifier)
    {
        if ($this->getElementValue($identifier) !== null) {
            return $this->getElementValue($identifier);
        }
        $getterMethodName = 'get' . ucfirst($identifier);
        if (is_callable([$this, $getterMethodName])) {
            return $this->{$getterMethodName}();
        }
        return null;
    }

    /**
     * @param string $identifier
     * @param mixed $value
     * @internal
     * @todo Set $identifier and $value to mixed type in v12.
     */
    public function offsetSet($identifier, $value): void
    {
        $this->formState->setFormValue($identifier, $value);
    }

    /**
     * @param string $identifier
     * @internal
     * @todo Set $identifier to mixed type in v12.
     */
    public function offsetUnset($identifier): void
    {
        $this->formState->setFormValue($identifier, null);
    }

    /**
     * Returns the value of the specified element
     *
     * @param string $identifier
     * @return mixed
     */
    public function getElementValue(string $identifier)
    {
        $formValue = $this->formState->getFormValue($identifier);
        if ($formValue !== null) {
            return $formValue;
        }
        return $this->formDefinition->getElementDefaultValueByIdentifier($identifier);
    }

    /**
     * @return array|Page[] The Form's pages in the correct order
     */
    public function getPages(): array
    {
        return $this->formDefinition->getPages();
    }

    /**
     * @return FormState|null
     * @internal
     */
    public function getFormState(): ?FormState
    {
        return $this->formState;
    }

    /**
     * Get all rendering options
     *
     * @return array associative array of rendering options
     */
    public function getRenderingOptions(): array
    {
        return $this->formDefinition->getRenderingOptions();
    }

    /**
     * Get the renderer class name to be used to display this renderable;
     * must implement RendererInterface
     *
     * @return string the renderer class name
     */
    public function getRendererClassName(): string
    {
        return $this->formDefinition->getRendererClassName();
    }

    /**
     * Get the label which shall be displayed next to the form element
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->formDefinition->getLabel();
    }

    /**
     * Get the template name of the renderable
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return $this->formDefinition->getTemplateName();
    }

    /**
     * Get the underlying form definition from the runtime
     *
     * @return FormDefinition
     */
    public function getFormDefinition(): FormDefinition
    {
        return $this->formDefinition;
    }

    /**
     * Get the current site language configuration.
     *
     * @return SiteLanguage
     */
    public function getCurrentSiteLanguage(): ?SiteLanguage
    {
        return $this->currentSiteLanguage;
    }

    /**
     * Override the the current site language configuration.
     *
     * This is typically not needed in production code, but it is very
     * helpful when displaying some kind of "preview" of the form (e.g. form editor).
     *
     * @param SiteLanguage $currentSiteLanguage
     */
    public function setCurrentSiteLanguage(SiteLanguage $currentSiteLanguage): void
    {
        $this->currentSiteLanguage = $currentSiteLanguage;
    }

    /**
     * Initialize the SiteLanguage object.
     * This is mainly used by the condition matcher.
     */
    protected function initializeCurrentSiteLanguage(): void
    {
        if (
            $GLOBALS['TYPO3_REQUEST'] instanceof ServerRequestInterface
            && $GLOBALS['TYPO3_REQUEST']->getAttribute('language') instanceof SiteLanguage
        ) {
            $this->currentSiteLanguage = $GLOBALS['TYPO3_REQUEST']->getAttribute('language');
        } else {
            $pageId = 0;
            $languageId = (int)GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('language', 'id', 0);

            if ($this->getTypoScriptFrontendController() !== null) {
                $pageId = $this->getTypoScriptFrontendController()->id;
            }

            $fakeSiteConfiguration = [
                'languages' => [
                    [
                        'languageId' => $languageId,
                        'title' => 'Dummy',
                        'navigationTitle' => '',
                        'typo3Language' => '',
                        'flag' => '',
                        'locale' => '',
                        'iso-639-1' => '',
                        'hreflang' => '',
                        'direction' => '',
                    ],
                ],
            ];

            $this->currentSiteLanguage = GeneralUtility::makeInstance(Site::class, 'form-dummy', $pageId, $fakeSiteConfiguration)
                ->getLanguageById($languageId);
        }
    }

    /**
     * Reference to the current running finisher
     *
     * @return FinisherInterface|null
     */
    public function getCurrentFinisher(): ?FinisherInterface
    {
        return $this->currentFinisher;
    }

    /**
     * @return Resolver
     */
    protected function getConditionResolver(): Resolver
    {
        $formValues = array_replace_recursive(
            $this->getFormState()->getFormValues(),
            $this->getRequest()->getArguments()
        );
        $page = $this->getCurrentPage() ?? $this->getFormDefinition()->getPageByIndex(0);

        $finisherIdentifier = '';
        if ($this->getCurrentFinisher() !== null) {
            if (method_exists($this->getCurrentFinisher(), 'getFinisherIdentifier')) {
                $finisherIdentifier = $this->getCurrentFinisher()->getFinisherIdentifier();
            } else {
                $finisherIdentifier = (new \ReflectionClass($this->getCurrentFinisher()))->getShortName();
                $finisherIdentifier = preg_replace('/Finisher$/', '', $finisherIdentifier);
            }
        }

        $contentObjectData = [];
        if ($this->configurationManager->getContentObject() instanceof ContentObjectRenderer) {
            $contentObjectData = $this->configurationManager->getContentObject()->data;
        }

        return GeneralUtility::makeInstance(
            Resolver::class,
            'form',
            [
                'formRuntime' => $this,
                'formValues' => $formValues,
                'stepIdentifier' => $page->getIdentifier(),
                'stepType' => $page->getType(),
                'finisherIdentifier' => $finisherIdentifier,
                'contentObject' => $contentObjectData,
            ],
            $GLOBALS['TYPO3_REQUEST'] ?? GeneralUtility::makeInstance(ServerRequest::class)
        );
    }

    /**
     * @return FrontendUserAuthentication
     */
    protected function getFrontendUser(): FrontendUserAuthentication
    {
        return $this->getTypoScriptFrontendController()->fe_user;
    }

    /**
     * @return TypoScriptFrontendController|null
     */
    protected function getTypoScriptFrontendController(): ?TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'] ?? null;
    }
}
