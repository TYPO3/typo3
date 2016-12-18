<?php
declare(strict_types=1);
namespace TYPO3\CMS\Form\Domain\Runtime;

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

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Mvc\Controller\Arguments;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Mvc\Web\Request;
use TYPO3\CMS\Extbase\Mvc\Web\Response;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Property\Exception as PropertyException;
use TYPO3\CMS\Extbase\Reflection\PropertyReflection;
use TYPO3\CMS\Form\Domain\Exception\RenderingException;
use TYPO3\CMS\Form\Domain\Finishers\FinisherContext;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3\CMS\Form\Domain\Model\FormElements\Page;
use TYPO3\CMS\Form\Domain\Model\Renderable\RootRenderableInterface;
use TYPO3\CMS\Form\Domain\Renderer\RendererInterface;
use TYPO3\CMS\Form\Domain\Runtime\Exception\PropertyMappingException;
use TYPO3\CMS\Form\Mvc\Validation\EmptyValidator;
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
 * $form = $formDefinition->bind($request, $response);
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
 * and then trigger render() on this element.
 *
 * This makes it possible to declaratively define how a form should be rendered.
 *
 * Scope: frontend
 * **This class is NOT meant to be sub classed by developers.**
 * @api
 */
class FormRuntime implements RootRenderableInterface, \ArrayAccess
{
    const HONEYPOT_NAME_SESSION_IDENTIFIER = 'tx_form_honeypot_name_';

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \TYPO3\CMS\Form\Domain\Model\FormDefinition
     */
    protected $formDefinition;

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Web\Request
     */
    protected $request;

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Web\Response
     */
    protected $response;

    /**
     * @var \TYPO3\CMS\Form\Domain\Runtime\FormState
     */
    protected $formState;

    /**
     * The current page is the page which will be displayed to the user
     * during rendering.
     *
     * If $currentPage is NULL, the *last* page has been submitted and
     * finishing actions need to take place. You should use $this->isAfterLastPage()
     * instead of explicitely checking for NULL.
     *
     * @var \TYPO3\CMS\Form\Domain\Model\FormElements\Page
     */
    protected $currentPage = null;

    /**
     * Reference to the page which has been shown on the last request (i.e.
     * we have to handle the submitted data from lastDisplayedPage)
     *
     * @var \TYPO3\CMS\Form\Domain\Model\FormElements\Page
     */
    protected $lastDisplayedPage = null;

    /**
     * @var \TYPO3\CMS\Extbase\Security\Cryptography\HashService
     */
    protected $hashService;

    /**
     * @param \TYPO3\CMS\Extbase\Security\Cryptography\HashService $hashService
     * @return void
     * @internal
     */
    public function injectHashService(\TYPO3\CMS\Extbase\Security\Cryptography\HashService $hashService)
    {
        $this->hashService = $hashService;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
     * @internal
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param FormDefinition $formDefinition
     * @param Request $request
     * @param Response $response
     * @api
     */
    public function __construct(FormDefinition $formDefinition, Request $request, Response $response)
    {
        $this->formDefinition = $formDefinition;
        $arguments = $request->getArguments();
        $this->request = clone $request;
        $formIdentifier = $this->formDefinition->getIdentifier();
        if (isset($arguments[$formIdentifier])) {
            $this->request->setArguments($arguments[$formIdentifier]);
        }

        $this->response = $response;
    }

    /**
     * @return void
     * @internal
     */
    public function initializeObject()
    {
        $this->initializeFormStateFromRequest();
        $this->initializeCurrentPageFromRequest();
        $this->initializeHoneypotFromRequest();

        if (!$this->isFirstRequest() && $this->getRequest()->getMethod() === 'POST') {
            $this->processSubmittedFormValues();
        }

        $this->renderHoneypot();
    }

    /**
     * @return void
     */
    protected function initializeFormStateFromRequest()
    {
        $serializedFormStateWithHmac = $this->request->getInternalArgument('__state');
        if ($serializedFormStateWithHmac === null) {
            $this->formState = GeneralUtility::makeInstance(FormState::class);
        } else {
            $serializedFormState = $this->hashService->validateAndStripHmac($serializedFormStateWithHmac);
            $this->formState = unserialize(base64_decode($serializedFormState));
        }
    }

    /**
     * @return void
     */
    protected function initializeCurrentPageFromRequest()
    {
        if (!$this->formState->isFormSubmitted()) {
            $this->currentPage = $this->formDefinition->getPageByIndex(0);
            return;
        }
        $this->lastDisplayedPage = $this->formDefinition->getPageByIndex($this->formState->getLastDisplayedPageIndex());

        // We know now that lastDisplayedPage is filled
        $currentPageIndex = (int)$this->request->getInternalArgument('__currentPage');
        if ($currentPageIndex > $this->lastDisplayedPage->getIndex() + 1) {
            // We only allow jumps to following pages
            $currentPageIndex = $this->lastDisplayedPage->getIndex() + 1;
        }

        // We now know that the user did not try to skip a page
        if ($currentPageIndex === count($this->formDefinition->getPages())) {
            // Last Page
            $this->currentPage = null;
        } else {
            $this->currentPage = $this->formDefinition->getPageByIndex($currentPageIndex);
        }
    }

    /**
     * @return void
     */
    protected function initializeHoneypotFromRequest()
    {
        $renderingOptions = $this->formDefinition->getRenderingOptions();
        if (!isset($renderingOptions['honeypot']['enable']) || $renderingOptions['honeypot']['enable'] === false || TYPO3_MODE === 'BE') {
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
                $validator = $this->objectManager->get(EmptyValidator::class);
                $honeypotElement->addValidator($validator);
            }
        }
    }

    /**
     * @return void
     */
    protected function renderHoneypot()
    {
        $renderingOptions = $this->formDefinition->getRenderingOptions();
        if (!isset($renderingOptions['honeypot']['enable']) || $renderingOptions['honeypot']['enable'] === false || TYPO3_MODE === 'BE') {
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
            $randomElementNumber = mt_rand(0, ($elementsCount - 1));
            $honeypotName = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, mt_rand(5, 26));

            $referenceElement = $this->currentPage->getElements()[$randomElementNumber];
            $honeypotElement = $this->currentPage->createElement($honeypotName, $renderingOptions['honeypot']['formElementToUse']);
            $validator = $this->objectManager->get(EmptyValidator::class);

            $honeypotElement->addValidator($validator);
            if (mt_rand(0, 1) === 1) {
                $this->currentPage->moveElementAfter($honeypotElement, $referenceElement);
            } else {
                $this->currentPage->moveElementBefore($honeypotElement, $referenceElement);
            }
            $this->setHoneypotNameInSession($this->currentPage, $honeypotName);
        }
    }

    /**
     * @param Page $page
     * return null|string
     */
    protected function getHoneypotNameFromSession(Page $page)
    {
        if ($this->getTypoScriptFrontendController()->loginUser) {
            $honeypotNameFromSession = $this->getTypoScriptFrontendController()->fe_user->getKey(
                'user',
                self::HONEYPOT_NAME_SESSION_IDENTIFIER . $this->getIdentifier() . $page->getIdentifier()
            );
        } else {
            $honeypotNameFromSession = $this->getTypoScriptFrontendController()->fe_user->getKey(
                'ses',
                self::HONEYPOT_NAME_SESSION_IDENTIFIER . $this->getIdentifier() . $page->getIdentifier()
            );
        }
        return $honeypotNameFromSession;
    }

    /**
     * @param Page $page
     * @param string $honeypotName
     * @return void
     */
    protected function setHoneypotNameInSession(Page $page, string $honeypotName)
    {
        if ($this->getTypoScriptFrontendController()->loginUser) {
            $this->getTypoScriptFrontendController()->fe_user->setKey(
                'user',
                self::HONEYPOT_NAME_SESSION_IDENTIFIER . $this->getIdentifier() . $page->getIdentifier(),
                $honeypotName
            );
        } else {
            $this->getTypoScriptFrontendController()->fe_user->setKey(
                'ses',
                self::HONEYPOT_NAME_SESSION_IDENTIFIER . $this->getIdentifier() . $page->getIdentifier(),
                $honeypotName
            );
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
     * @return void
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
        $result = $this->objectManager->get(Result::class);
        $requestArguments = $this->request->getArguments();

        $propertyPathsForWhichPropertyMappingShouldHappen = [];
        $registerPropertyPaths = function ($propertyPath) use (&$propertyPathsForWhichPropertyMappingShouldHappen) {
            $propertyPathParts = explode('.', $propertyPath);
            $accumulatedPropertyPathParts = [];
            foreach ($propertyPathParts as $propertyPathPart) {
                $accumulatedPropertyPathParts[] = $propertyPathPart;
                $temporaryPropertyPath = implode('.', $accumulatedPropertyPathParts);
                $propertyPathsForWhichPropertyMappingShouldHappen[$temporaryPropertyPath] = $temporaryPropertyPath;
            }
        };
        foreach ($page->getElementsRecursively() as $element) {
            $value = ArrayUtility::getValueByPath($requestArguments, $element->getIdentifier());
            $element->onSubmit($this, $value, $requestArguments);

            $this->formState->setFormValue($element->getIdentifier(), $value);
            $registerPropertyPaths($element->getIdentifier());
        }

        // The more parts the path has, the more early it is processed
        usort($propertyPathsForWhichPropertyMappingShouldHappen, function ($a, $b) {
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
                $result->forProperty($propertyPath)->merge($processingRule->getProcessingMessages());
                $this->formState->setFormValue($propertyPath, $value);
            }
        }

        return $result;
    }

    /**
     * Override the current page taken from the request, rendering the page with index $pageIndex instead.
     *
     * This is typically not needed in production code, but it is very helpful when displaying
     * some kind of "preview" of the form.
     *
     * @param int $pageIndex
     * @return void
     * @api
     */
    public function overrideCurrentPage(int $pageIndex)
    {
        $this->currentPage = $this->formDefinition->getPageByIndex($pageIndex);
    }

    /**
     * Render this form.
     *
     * @return null|string rendered form
     * @throws RenderingException
     * @api
     */
    public function render()
    {
        if ($this->isAfterLastPage()) {
            $this->invokeFinishers();
            return $this->response->getContent();
        }

        $this->formState->setLastDisplayedPageIndex($this->currentPage->getIndex());

        if ($this->formDefinition->getRendererClassName() === null) {
            throw new RenderingException(sprintf('The form definition "%s" does not have a rendererClassName set.', $this->formDefinition->getIdentifier()), 1326095912);
        }
        $rendererClassName = $this->formDefinition->getRendererClassName();
        $renderer = $this->objectManager->get($rendererClassName);
        if (!($renderer instanceof RendererInterface)) {
            throw new RenderingException(sprintf('The renderer "%s" des not implement RendererInterface', $rendererClassName), 1326096024);
        }

        $controllerContext = $this->getControllerContext();

        $renderer->setControllerContext($controllerContext);
        $renderer->setFormRuntime($this);
        return $renderer->render($this);
    }

    /**
     * Executes all finishers of this form
     *
     * @return void
     */
    protected function invokeFinishers()
    {
        $finisherContext = $this->objectManager->get(FinisherContext::class,
            $this,
            $this->getControllerContext()
        );
        foreach ($this->formDefinition->getFinishers() as $finisher) {
            $finisher->execute($finisherContext);
            if ($finisherContext->isCancelled()) {
                break;
            }
        }
    }

    /**
     * @return string The identifier of underlying form
     * @api
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
     * @api
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
     * @return Response the response this object is bound to
     * @api
     */
    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * Returns the currently selected page
     *
     * @return Page
     * @api
     */
    public function getCurrentPage(): Page
    {
        return $this->currentPage;
    }

    /**
     * Returns the previous page of the currently selected one or NULL if there is no previous page
     *
     * @return null|Page
     * @api
     */
    public function getPreviousPage()
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
     * @return null|Page
     * @api
     */
    public function getNextPage()
    {
        $nextPageIndex = $this->currentPage->getIndex() + 1;
        if ($this->formDefinition->hasPageWithIndex($nextPageIndex)) {
            return $this->formDefinition->getPageByIndex($nextPageIndex);
        }
        return null;
    }

    /**
     * @return ControllerContext
     */
    protected function getControllerContext(): ControllerContext
    {
        $uriBuilder = $this->objectManager->get(UriBuilder::class);
        $uriBuilder->setRequest($this->request);
        $controllerContext = $this->objectManager->get(ControllerContext::class);
        $controllerContext->setRequest($this->request);
        $controllerContext->setResponse($this->response);
        $controllerContext->setArguments($this->objectManager->get(Arguments::class, []));
        $controllerContext->setUriBuilder($uriBuilder);
        return $controllerContext;
    }

    /**
     * Abstract "type" of this Renderable. Is used during the rendering process
     * to determine the template file or the View PHP class being used to render
     * the particular element.
     *
     * @return string
     * @api
     */
    public function getType(): string
    {
        return $this->formDefinition->getType();
    }

    /**
     * @param string $identifier
     * @return bool
     * @internal
     */
    public function offsetExists($identifier)
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
            $propertyReflection = new PropertyReflection($this, $identifier);
            return $propertyReflection->isPublic();
        }

        return false;
    }

    /**
     * @param string $identifier
     * @return mixed
     * @internal
     */
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
     * @return void
     * @internal
     */
    public function offsetSet($identifier, $value)
    {
        $this->formState->setFormValue($identifier, $value);
    }

    /**
     * @param string $identifier
     * @return void
     * @internal
     */
    public function offsetUnset($identifier)
    {
        $this->formState->setFormValue($identifier, null);
    }

    /**
     * Returns the value of the specified element
     *
     * @param string $identifier
     * @return mixed
     * @api
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
     * @return array<Page> The Form's pages in the correct order
     * @api
     */
    public function getPages(): array
    {
        return $this->formDefinition->getPages();
    }

    /**
     * @return FormState
     * @internal
     */
    public function getFormState(): FormState
    {
        return $this->formState;
    }

    /**
     * Get all rendering options
     *
     * @return array associative array of rendering options
     * @api
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
     * @api
     */
    public function getRendererClassName(): string
    {
        return $this->formDefinition->getRendererClassName();
    }

    /**
     * Get the label which shall be displayed next to the form element
     *
     * @return string
     * @api
     */
    public function getLabel(): string
    {
        return $this->formDefinition->getLabel();
    }

    /**
     * Get the underlying form definition from the runtime
     *
     * @return FormDefinition
     * @api
     */
    public function getFormDefinition(): FormDefinition
    {
        return $this->formDefinition;
    }

    /**
     * This is a callback that is invoked by the Renderer before the corresponding element is rendered.
     * Use this to access previously submitted values and/or modify the $formRuntime before an element
     * is outputted to the browser.
     *
     * @param FormRuntime $formRuntime
     * @return void
     * @api
     */
    public function beforeRendering(FormRuntime $formRuntime)
    {
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}
