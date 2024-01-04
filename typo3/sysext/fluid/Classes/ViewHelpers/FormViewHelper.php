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

namespace TYPO3\CMS\Fluid\ViewHelpers;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\SecurityAspect;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Security\RequestToken;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfigurationService;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Security\Cryptography\HashService;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormViewHelper;
use TYPO3\CMS\Fluid\ViewHelpers\Form\CheckboxViewHelper;

/**
 * Form ViewHelper. Generates a :html:`<form>` Tag. Tailored for extbase plugins, uses extbase Request.
 *
 * Basic usage
 * ===========
 *
 * Use :html:`<f:form>` to output an HTML :html:`<form>` tag which is targeted
 * at the specified action, in the current controller and package.
 * It will submit the form data via a POST request. If you want to change this,
 * use :html:`method="get"` as an argument.
 *
 * Examples
 * ========
 *
 * A complex form with a specified encoding type
 * ---------------------------------------------
 *
 * Form with enctype set::
 *
 *    <f:form action=".." controller="..." package="..." enctype="multipart/form-data">...</f:form>
 *
 * A Form which should render a domain object
 * ------------------------------------------
 *
 * Binding a domain object to a form::
 *
 *    <f:form action="..." name="customer" object="{customer}">
 *       <f:form.hidden property="id" />
 *       <f:form.textarea property="name" />
 *    </f:form>
 *
 * This automatically inserts the value of ``{customer.name}`` inside the
 * textarea and adjusts the name of the textarea accordingly.
 */
class FormViewHelper extends AbstractFormViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'form';

    protected HashService $hashService;
    protected MvcPropertyMappingConfigurationService $mvcPropertyMappingConfigurationService;
    protected ExtensionService $extensionService;
    protected ConfigurationManagerInterface $configurationManager;

    /**
     * We need the arguments of the formActionUri on request hash calculation
     * therefore we will store them in here right after calling uriBuilder
     */
    protected array $formActionUriArguments = [];

    public function injectHashService(HashService $hashService): void
    {
        $this->hashService = $hashService;
    }

    public function injectMvcPropertyMappingConfigurationService(MvcPropertyMappingConfigurationService $mvcPropertyMappingConfigurationService): void
    {
        $this->mvcPropertyMappingConfigurationService = $mvcPropertyMappingConfigurationService;
    }

    public function injectExtensionService(ExtensionService $extensionService): void
    {
        $this->extensionService = $extensionService;
    }

    public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager): void
    {
        $this->configurationManager = $configurationManager;
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('action', 'string', 'Target action');
        $this->registerArgument('arguments', 'array', 'Arguments', false, []);
        $this->registerArgument('controller', 'string', 'Target controller');
        $this->registerArgument('extensionName', 'string', 'Target Extension Name (without `tx_` prefix and no underscores). If NULL the current extension name is used');
        $this->registerArgument('pluginName', 'string', 'Target plugin. If empty, the current plugin name is used');
        $this->registerArgument('pageUid', 'int', 'Target page uid');
        $this->registerArgument('object', 'mixed', 'Object to use for the form. Use in conjunction with the "property" attribute on the sub tags');
        $this->registerArgument('pageType', 'int', 'Target page type', false, 0);
        $this->registerArgument('noCache', 'bool', 'set this to disable caching for the target page. You should not need this.', false, false);
        $this->registerArgument('section', 'string', 'The anchor to be added to the action URI (only active if $actionUri is not set)', false, '');
        $this->registerArgument('format', 'string', 'The requested format (e.g. ".html") of the target page (only active if $actionUri is not set)', false, '');
        $this->registerArgument('additionalParams', 'array', 'additional action URI query parameters that won\'t be prefixed like $arguments (overrule $arguments) (only active if $actionUri is not set)', false, []);
        $this->registerArgument('absolute', 'bool', 'If set, an absolute action URI is rendered (only active if $actionUri is not set)', false, false);
        $this->registerArgument('addQueryString', 'string', 'If set, the current query parameters will be kept in the URL. If set to "untrusted", then ALL query parameters will be added. Be aware, that this might lead to problems when the generated link is cached.', false, false);
        $this->registerArgument('argumentsToBeExcludedFromQueryString', 'array', 'arguments to be removed from the action URI. Only active if $addQueryString = TRUE and $actionUri is not set', false, []);
        $this->registerArgument('fieldNamePrefix', 'string', 'Prefix that will be added to all field names within this form. If not set the prefix will be tx_yourExtension_plugin');
        $this->registerArgument('actionUri', 'string', 'can be used to overwrite the "action" attribute of the form tag');
        $this->registerArgument('objectName', 'string', 'name of the object that is bound to this form. If this argument is not specified, the name attribute of this form is used to determine the FormObjectName');
        $this->registerArgument('hiddenFieldClassName', 'string', 'hiddenFieldClassName');
        $this->registerArgument('requestToken', 'mixed', 'whether to add that request token to the form');
        $this->registerArgument('signingType', 'string', 'which signing type to be used on the request token (falls back to "nonce")');
        $this->registerTagAttribute('enctype', 'string', 'MIME type with which the form is submitted');
        $this->registerTagAttribute('method', 'string', 'Transfer type (get or post)', false, 'post');
        $this->registerTagAttribute('name', 'string', 'Name of form');
        $this->registerTagAttribute('onreset', 'string', 'JavaScript: On reset of the form');
        $this->registerTagAttribute('onsubmit', 'string', 'JavaScript: On submit of the form');
        $this->registerTagAttribute('target', 'string', 'Target attribute of the form');
        $this->registerTagAttribute('novalidate', 'bool', 'Indicate that the form is not to be validated on submit.');
        $this->registerUniversalTagAttributes();
    }

    public function render(): string
    {
        /** @var RenderingContext $renderingContext */
        $renderingContext = $this->renderingContext;
        $request = $renderingContext->getRequest();
        if (!$request instanceof RequestInterface) {
            throw new \RuntimeException(
                'ViewHelper f:form can be used only in extbase context and needs a request implementing extbase RequestInterface.',
                1639821904
            );
        }

        $this->setFormActionUri();

        // Force 'method="get"' or 'method="post"', defaulting to "post".
        if (isset($this->arguments['method']) && strtolower($this->arguments['method']) === 'get') {
            $this->tag->addAttribute('method', 'get');
        } else {
            $this->tag->addAttribute('method', 'post');
        }

        if (isset($this->arguments['novalidate']) && $this->arguments['novalidate'] === true) {
            $this->tag->addAttribute('novalidate', 'novalidate');
        }

        $this->addFormObjectNameToViewHelperVariableContainer();
        $this->addFormObjectToViewHelperVariableContainer();
        $this->addFieldNamePrefixToViewHelperVariableContainer();
        $this->addFormFieldNamesToViewHelperVariableContainer();

        $formContent = $this->renderChildren();

        if (isset($this->arguments['hiddenFieldClassName']) && $this->arguments['hiddenFieldClassName'] !== null) {
            $content = LF . '<div class="' . htmlspecialchars($this->arguments['hiddenFieldClassName']) . '">';
        } else {
            $content = LF . '<div>';
        }

        $content .= $this->renderHiddenIdentityField($this->arguments['object'] ?? null, $this->getFormObjectName());
        $content .= $this->renderAdditionalIdentityFields();
        $content .= $this->renderHiddenReferrerFields();
        $content .= $this->renderRequestTokenHiddenField();

        // Render the trusted list of all properties after everything else has been rendered
        $content .= $this->renderTrustedPropertiesField();

        $content .= LF . '</div>' . LF;
        $content .= $formContent;
        $this->tag->setContent($content);
        $this->removeFieldNamePrefixFromViewHelperVariableContainer();
        $this->removeFormObjectFromViewHelperVariableContainer();
        $this->removeFormObjectNameFromViewHelperVariableContainer();
        $this->removeFormFieldNamesFromViewHelperVariableContainer();
        $this->removeCheckboxFieldNamesFromViewHelperVariableContainer();
        return $this->tag->render();
    }

    /**
     * Sets the "action" attribute of the form tag
     */
    protected function setFormActionUri(): void
    {
        if ($this->hasArgument('actionUri')) {
            $formActionUri = $this->arguments['actionUri'];
        } else {
            /** @var RenderingContext $renderingContext */
            $renderingContext = $this->renderingContext;
            /** @var RequestInterface $request */
            $request = $renderingContext->getRequest();
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $uriBuilder
                ->reset()
                ->setRequest($request)
                ->setTargetPageType((int)($this->arguments['pageType'] ?? 0))
                ->setNoCache((bool)($this->arguments['noCache'] ?? false))
                ->setSection($this->arguments['section'] ?? '')
                ->setCreateAbsoluteUri((bool)($this->arguments['absolute'] ?? false))
                ->setArguments(isset($this->arguments['additionalParams']) ? (array)$this->arguments['additionalParams'] : [])
                ->setAddQueryString($this->arguments['addQueryString'] ?? false)
                ->setArgumentsToBeExcludedFromQueryString(isset($this->arguments['argumentsToBeExcludedFromQueryString']) ? (array)$this->arguments['argumentsToBeExcludedFromQueryString'] : [])
                ->setFormat($this->arguments['format'] ?? '')
            ;

            $pageUid = (int)($this->arguments['pageUid'] ?? 0);
            if ($pageUid > 0) {
                $uriBuilder->setTargetPageUid($pageUid);
            }

            $formActionUri = $uriBuilder->uriFor(
                $this->arguments['action'] ?? null,
                $this->arguments['arguments'] ?? [],
                $this->arguments['controller'] ?? null,
                $this->arguments['extensionName'] ?? null,
                $this->arguments['pluginName'] ?? null
            );
            $this->formActionUriArguments = $uriBuilder->getArguments();
        }
        $this->tag->addAttribute('action', $formActionUri);
    }

    /**
     * Render additional identity fields which were registered by form elements.
     * This happens if a form field is defined like property="bla.blubb" - then we might need an identity property for the sub-object "bla".
     *
     * @return string HTML-string for the additional identity properties
     */
    protected function renderAdditionalIdentityFields(): string
    {
        $viewHelperVariableContainer = $this->renderingContext->getViewHelperVariableContainer();
        if ($viewHelperVariableContainer->exists(FormViewHelper::class, 'additionalIdentityProperties')) {
            $additionalIdentityProperties = $viewHelperVariableContainer->get(FormViewHelper::class, 'additionalIdentityProperties');
            $output = '';
            foreach ($additionalIdentityProperties as $identity) {
                $output .= LF . $identity;
            }
            return $output;
        }
        return '';
    }

    /**
     * Renders hidden form fields for referrer information about
     * the current controller and action.
     *
     * @return string Hidden fields with referrer information
     * @todo filter out referrer information that is equal to the target (e.g. same packageKey)
     */
    protected function renderHiddenReferrerFields(): string
    {
        /** @var RenderingContext $renderingContext */
        $renderingContext = $this->renderingContext;
        /** @var RequestInterface $request */
        $request = $renderingContext->getRequest();
        $extensionName = $request->getControllerExtensionName();
        $controllerName = $request->getControllerName();
        $actionName = $request->getControllerActionName();
        $actionRequest = [
            '@extension' => $extensionName,
            '@controller' => $controllerName,
            '@action' => $actionName,
        ];

        $result = LF;
        $result .= '<input type="hidden" name="' . htmlspecialchars($this->prefixFieldName('__referrer[@extension]')) . '" value="' . htmlspecialchars((string)$extensionName) . '" />' . LF;
        $result .= '<input type="hidden" name="' . htmlspecialchars($this->prefixFieldName('__referrer[@controller]')) . '" value="' . htmlspecialchars((string)$controllerName) . '" />' . LF;
        $result .= '<input type="hidden" name="' . htmlspecialchars($this->prefixFieldName('__referrer[@action]')) . '" value="' . htmlspecialchars((string)$actionName) . '" />' . LF;
        $result .= '<input type="hidden" name="' . htmlspecialchars($this->prefixFieldName('__referrer[arguments]')) . '" value="' . htmlspecialchars($this->hashService->appendHmac(base64_encode(serialize($request->getArguments())))) . '" />' . LF;
        $result .= '<input type="hidden" name="' . htmlspecialchars($this->prefixFieldName('__referrer[@request]')) . '" value="' . htmlspecialchars($this->hashService->appendHmac(json_encode($actionRequest))) . '" />' . LF;

        return $result;
    }

    /**
     * Adds the form object name to the ViewHelperVariableContainer if "objectName" argument or "name" attribute is specified.
     */
    protected function addFormObjectNameToViewHelperVariableContainer(): void
    {
        $formObjectName = $this->getFormObjectName();
        if ($formObjectName !== null) {
            $this->renderingContext->getViewHelperVariableContainer()->add(FormViewHelper::class, 'formObjectName', $formObjectName);
        }
    }

    /**
     * Removes the form name from the ViewHelperVariableContainer.
     */
    protected function removeFormObjectNameFromViewHelperVariableContainer(): void
    {
        $formObjectName = $this->getFormObjectName();
        if ($formObjectName !== null) {
            $this->renderingContext->getViewHelperVariableContainer()->remove(FormViewHelper::class, 'formObjectName');
        }
    }

    /**
     * Returns the name of the object that is bound to this form.
     * If the "objectName" argument has been specified, this is returned. Otherwise the name attribute of this form.
     * If neither objectName nor name arguments have been set, NULL is returned.
     *
     * @return string specified Form name or NULL if neither $objectName nor $name arguments have been specified
     */
    protected function getFormObjectName(): ?string
    {
        $formObjectName = null;
        if ($this->hasArgument('objectName')) {
            $formObjectName = $this->arguments['objectName'];
        } elseif ($this->hasArgument('name')) {
            $formObjectName = $this->arguments['name'];
        }
        return $formObjectName;
    }

    /**
     * Adds the object that is bound to this form to the ViewHelperVariableContainer if the formObject attribute is specified.
     */
    protected function addFormObjectToViewHelperVariableContainer(): void
    {
        if ($this->hasArgument('object')) {
            $viewHelperVariableContainer = $this->renderingContext->getViewHelperVariableContainer();
            $viewHelperVariableContainer->add(FormViewHelper::class, 'formObject', $this->arguments['object']);
            $viewHelperVariableContainer->add(FormViewHelper::class, 'additionalIdentityProperties', []);
        }
    }

    /**
     * Removes the form object from the ViewHelperVariableContainer.
     */
    protected function removeFormObjectFromViewHelperVariableContainer(): void
    {
        if ($this->hasArgument('object')) {
            $viewHelperVariableContainer = $this->renderingContext->getViewHelperVariableContainer();
            $viewHelperVariableContainer->remove(FormViewHelper::class, 'formObject');
            $viewHelperVariableContainer->remove(FormViewHelper::class, 'additionalIdentityProperties');
        }
    }

    /**
     * Adds the field name prefix to the ViewHelperVariableContainer.
     */
    protected function addFieldNamePrefixToViewHelperVariableContainer(): void
    {
        $fieldNamePrefix = $this->getFieldNamePrefix();
        $this->renderingContext->getViewHelperVariableContainer()->add(FormViewHelper::class, 'fieldNamePrefix', $fieldNamePrefix);
    }

    protected function getFieldNamePrefix(): string
    {
        if ($this->hasArgument('fieldNamePrefix')) {
            return $this->arguments['fieldNamePrefix'];
        }
        return $this->getDefaultFieldNamePrefix();
    }

    /**
     * Removes field name prefix from the ViewHelperVariableContainer.
     */
    protected function removeFieldNamePrefixFromViewHelperVariableContainer(): void
    {
        $this->renderingContext->getViewHelperVariableContainer()->remove(FormViewHelper::class, 'fieldNamePrefix');
    }

    /**
     * Adds a container for form field names to the ViewHelperVariableContainer.
     */
    protected function addFormFieldNamesToViewHelperVariableContainer(): void
    {
        $this->renderingContext->getViewHelperVariableContainer()->add(FormViewHelper::class, 'formFieldNames', []);
    }

    /**
     * Removes the container for form field names from the ViewHelperVariableContainer.
     */
    protected function removeFormFieldNamesFromViewHelperVariableContainer(): void
    {
        $viewHelperVariableContainer = $this->renderingContext->getViewHelperVariableContainer();
        $viewHelperVariableContainer->remove(FormViewHelper::class, 'formFieldNames');
        if ($viewHelperVariableContainer->exists(FormViewHelper::class, 'renderedHiddenFields')) {
            $viewHelperVariableContainer->remove(FormViewHelper::class, 'renderedHiddenFields');
        }
    }

    /**
     * Retrieves the default field name prefix for this form
     */
    protected function getDefaultFieldNamePrefix(): string
    {
        /** @var RenderingContext $renderingContext */
        $renderingContext = $this->renderingContext;
        /** @var RequestInterface $request */
        $request = $renderingContext->getRequest();
        // New Backend URLs does not have a prefix anymore
        // @deprecated since TYPO3 v12, will be removed in TYPO3 v13. Remove together with other extbase feature toggle related code.
        //             Remove "!$this->configurationManager->isFeatureEnabled('enableNamespacedArgumentsForBackend')" from if()
        if (!$this->configurationManager->isFeatureEnabled('enableNamespacedArgumentsForBackend')
            && $request instanceof ServerRequestInterface
            && $request->getAttribute('applicationType')
            && ApplicationType::fromRequest($request)->isBackend()
        ) {
            return '';
        }
        if ($this->hasArgument('extensionName')) {
            $extensionName = $this->arguments['extensionName'];
        } else {
            $extensionName = $request->getControllerExtensionName();
        }
        if ($this->hasArgument('pluginName')) {
            $pluginName = $this->arguments['pluginName'];
        } else {
            $pluginName = $request->getPluginName();
        }
        if ($extensionName !== null && $pluginName != null) {
            return $this->extensionService->getPluginNamespace($extensionName, $pluginName);
        }
        return '';
    }

    /**
     * Remove Checkbox field names from ViewHelper variable container, to start from scratch when a new form starts.
     */
    protected function removeCheckboxFieldNamesFromViewHelperVariableContainer(): void
    {
        $viewHelperVariableContainer = $this->renderingContext->getViewHelperVariableContainer();
        if ($viewHelperVariableContainer->exists(CheckboxViewHelper::class, 'checkboxFieldNames')) {
            $viewHelperVariableContainer->remove(CheckboxViewHelper::class, 'checkboxFieldNames');
        }
    }

    /**
     * Render the request hash field
     */
    protected function renderTrustedPropertiesField(): string
    {
        $formFieldNames = $this->renderingContext->getViewHelperVariableContainer()->get(FormViewHelper::class, 'formFieldNames');
        $requestHash = $this->mvcPropertyMappingConfigurationService->generateTrustedPropertiesToken($formFieldNames, $this->getFieldNamePrefix());
        return '<input type="hidden" name="' . htmlspecialchars($this->prefixFieldName('__trustedProperties')) . '" value="' . htmlspecialchars($requestHash) . '" />';
    }

    protected function renderRequestTokenHiddenField(): string
    {
        $requestToken = $this->arguments['requestToken'] ?? null;
        $signingType = $this->arguments['signingType'] ?? null;

        $isTrulyRequestToken = is_int($requestToken) && $requestToken === 1
            || is_string($requestToken) && strtolower($requestToken) === 'true';
        $formAction = $this->tag->getAttribute('action');

        // basically "request token, yes" - uses form-action URI as scope
        if ($isTrulyRequestToken || $requestToken === '@nonce') {
            $requestToken = RequestToken::create($formAction);
        } elseif (is_string($requestToken) && $requestToken !== '') {
            // basically "request token with 'my-scope'" - uses 'my-scope'
            $requestToken = RequestToken::create($requestToken);
        }
        if (!$requestToken instanceof RequestToken) {
            return '';
        }
        if (strtolower((string)($this->arguments['method'] ?? '')) === 'get') {
            throw new \LogicException('Cannot apply request token for forms sent via HTTP GET', 1651775963);
        }

        $context = GeneralUtility::makeInstance(Context::class);
        $securityAspect = SecurityAspect::provideIn($context);
        // @todo currently defaults to 'nonce', there might be a better strategy in the future
        $signingType = $signingType ?: 'nonce';
        $signingProvider = $securityAspect->getSigningSecretResolver()->findByType($signingType);
        if ($signingProvider === null) {
            throw new \LogicException(sprintf('Cannot find request token signing type "%s"', $signingType), 1664260307);
        }

        $signingSecret = $signingProvider->provideSigningSecret();
        $requestToken = $requestToken->withMergedParams(['request' => ['uri' => $formAction]]);

        $attrs = [
            'type' => 'hidden',
            'name' => RequestToken::PARAM_NAME,
            'value' => $requestToken->toHashSignedJwt($signingSecret),
        ];
        return '<input ' . GeneralUtility::implodeAttributes($attrs, true) . '/>';
    }
}
