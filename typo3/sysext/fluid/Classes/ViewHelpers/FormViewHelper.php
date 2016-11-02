<?php
namespace TYPO3\CMS\Fluid\ViewHelpers;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 *                                                                        *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
/**
 * Form view helper. Generates a <form> Tag.
 *
 * = Basic usage =
 *
 * Use <f:form> to output an HTML <form> tag which is targeted at the specified action, in the current controller and package.
 * It will submit the form data via a POST request. If you want to change this, use method="get" as an argument.
 * <code title="Example">
 * <f:form action="...">...</f:form>
 * </code>
 *
 * = A complex form with a specified encoding type =
 *
 * <code title="Form with enctype set">
 * <f:form action=".." controller="..." package="..." enctype="multipart/form-data">...</f:form>
 * </code>
 *
 * = A Form which should render a domain object =
 *
 * <code title="Binding a domain object to a form">
 * <f:form action="..." name="customer" object="{customer}">
 * <f:form.hidden property="id" />
 * <f:form.textbox property="name" />
 * </f:form>
 * </code>
 * This automatically inserts the value of {customer.name} inside the textbox and adjusts the name of the textbox accordingly.
 */
class FormViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'form';

    /**
     * @var \TYPO3\CMS\Extbase\Security\Cryptography\HashService
     */
    protected $hashService;

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfigurationService
     */
    protected $mvcPropertyMappingConfigurationService;

    /**
     * @var \TYPO3\CMS\Extbase\Service\ExtensionService
     */
    protected $extensionService;

    /**
     * We need the arguments of the formActionUri on requesthash calculation
     * therefore we will store them in here right after calling uriBuilder
     *
     * @var array
     */
    protected $formActionUriArguments;

    /**
     * @param \TYPO3\CMS\Extbase\Security\Cryptography\HashService $hashService
     */
    public function injectHashService(\TYPO3\CMS\Extbase\Security\Cryptography\HashService $hashService)
    {
        $this->hashService = $hashService;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfigurationService $mvcPropertyMappingConfigurationService
     */
    public function injectMvcPropertyMappingConfigurationService(\TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfigurationService $mvcPropertyMappingConfigurationService)
    {
        $this->mvcPropertyMappingConfigurationService = $mvcPropertyMappingConfigurationService;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Service\ExtensionService $extensionService
     */
    public function injectExtensionService(\TYPO3\CMS\Extbase\Service\ExtensionService $extensionService)
    {
        $this->extensionService = $extensionService;
    }

    /**
     * Initialize arguments.
     *
     * @return void
     */
    public function initializeArguments()
    {
        $this->registerTagAttribute('enctype', 'string', 'MIME type with which the form is submitted');
        $this->registerTagAttribute('method', 'string', 'Transfer type (GET or POST)');
        $this->registerTagAttribute('name', 'string', 'Name of form');
        $this->registerTagAttribute('onreset', 'string', 'JavaScript: On reset of the form');
        $this->registerTagAttribute('onsubmit', 'string', 'JavaScript: On submit of the form');
        $this->registerUniversalTagAttributes();
    }

    /**
     * Render the form.
     *
     * @param string $action Target action
     * @param array $arguments Arguments
     * @param string $controller Target controller
     * @param string $extensionName Target Extension Name (without "tx_" prefix and no underscores). If NULL the current extension name is used
     * @param string $pluginName Target plugin. If empty, the current plugin name is used
     * @param int $pageUid Target page uid
     * @param mixed $object Object to use for the form. Use in conjunction with the "property" attribute on the sub tags
     * @param int $pageType Target page type
     * @param bool $noCache set this to disable caching for the target page. You should not need this.
     * @param bool $noCacheHash set this to suppress the cHash query parameter created by TypoLink. You should not need this.
     * @param string $section The anchor to be added to the action URI (only active if $actionUri is not set)
     * @param string $format The requested format (e.g. ".html") of the target page (only active if $actionUri is not set)
     * @param array $additionalParams additional action URI query parameters that won't be prefixed like $arguments (overrule $arguments) (only active if $actionUri is not set)
     * @param bool $absolute If set, an absolute action URI is rendered (only active if $actionUri is not set)
     * @param bool $addQueryString If set, the current query parameters will be kept in the action URI (only active if $actionUri is not set)
     * @param array $argumentsToBeExcludedFromQueryString arguments to be removed from the action URI. Only active if $addQueryString = TRUE and $actionUri is not set
     * @param string $fieldNamePrefix Prefix that will be added to all field names within this form. If not set the prefix will be tx_yourExtension_plugin
     * @param string $actionUri can be used to overwrite the "action" attribute of the form tag
     * @param string $objectName name of the object that is bound to this form. If this argument is not specified, the name attribute of this form is used to determine the FormObjectName
     * @param string $hiddenFieldClassName
     * @param string $addQueryStringMethod Method to use when keeping query parameters (GET or POST, only active if $actionUri is not set)
     * @return string rendered form
     */
    public function render($action = null, array $arguments = [], $controller = null, $extensionName = null, $pluginName = null, $pageUid = null, $object = null, $pageType = 0, $noCache = false, $noCacheHash = false, $section = '', $format = '', array $additionalParams = [], $absolute = false, $addQueryString = false, array $argumentsToBeExcludedFromQueryString = [], $fieldNamePrefix = null, $actionUri = null, $objectName = null, $hiddenFieldClassName = null, $addQueryStringMethod = '')
    {
        $this->setFormActionUri();
        if (strtolower($this->arguments['method']) === 'get') {
            $this->tag->addAttribute('method', 'get');
        } else {
            $this->tag->addAttribute('method', 'post');
        }
        $this->addFormObjectNameToViewHelperVariableContainer();
        $this->addFormObjectToViewHelperVariableContainer();
        $this->addFieldNamePrefixToViewHelperVariableContainer();
        $this->addFormFieldNamesToViewHelperVariableContainer();
        $formContent = $this->renderChildren();

        if ($this->arguments['hiddenFieldClassName'] !== null) {
            $content = LF . '<div class="' . htmlspecialchars($this->arguments['hiddenFieldClassName']) . '">';
        } else {
            $content = LF . '<div>';
        }

        $content .= $this->renderHiddenIdentityField($this->arguments['object'], $this->getFormObjectName());
        $content .= $this->renderAdditionalIdentityFields();
        $content .= $this->renderHiddenReferrerFields();
        $content .= $this->renderHiddenSecuredReferrerField();

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
        $this->removeSecuredHiddenFieldsRenderedFromViewHelperVariableContainer();
        return $this->tag->render();
    }

    /**
     * Sets the "action" attribute of the form tag
     *
     * @return void
     */
    protected function setFormActionUri()
    {
        if ($this->hasArgument('actionUri')) {
            $formActionUri = $this->arguments['actionUri'];
        } else {
            $uriBuilder = $this->controllerContext->getUriBuilder();
            $pageUid = (int)$this->arguments['pageUid'] > 0 ? (int)$this->arguments['pageUid'] : null;
            $formActionUri = $uriBuilder
                ->reset()
                ->setTargetPageUid($pageUid)
                ->setTargetPageType($this->arguments['pageType'])
                ->setNoCache($this->arguments['noCache'])
                ->setUseCacheHash(!$this->arguments['noCacheHash'])
                ->setSection($this->arguments['section'])
                ->setCreateAbsoluteUri($this->arguments['absolute'])
                ->setArguments((array)$this->arguments['additionalParams'])
                ->setAddQueryString($this->arguments['addQueryString'])
                ->setAddQueryStringMethod($this->arguments['addQueryStringMethod'])
                ->setArgumentsToBeExcludedFromQueryString((array)$this->arguments['argumentsToBeExcludedFromQueryString'])
                ->setFormat($this->arguments['format'])
                ->uriFor(
                    $this->arguments['action'],
                    $this->arguments['arguments'],
                    $this->arguments['controller'],
                    $this->arguments['extensionName'],
                    $this->arguments['pluginName']
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
    protected function renderAdditionalIdentityFields()
    {
        if ($this->viewHelperVariableContainer->exists(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'additionalIdentityProperties')) {
            $additionalIdentityProperties = $this->viewHelperVariableContainer->get(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'additionalIdentityProperties');
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
    protected function renderHiddenReferrerFields()
    {
        $request = $this->controllerContext->getRequest();
        $extensionName = $request->getControllerExtensionName();
        $vendorName = $request->getControllerVendorName();
        $controllerName = $request->getControllerName();
        $actionName = $request->getControllerActionName();
        $result = LF;
        $result .= '<input type="hidden" name="' . $this->prefixFieldName('__referrer[@extension]') . '" value="' . $extensionName . '" />' . LF;
        if ($vendorName !== null) {
            $result .= '<input type="hidden" name="' . $this->prefixFieldName('__referrer[@vendor]') . '" value="' . $vendorName . '" />' . LF;
        }
        $result .= '<input type="hidden" name="' . $this->prefixFieldName('__referrer[@controller]') . '" value="' . $controllerName . '" />' . LF;
        $result .= '<input type="hidden" name="' . $this->prefixFieldName('__referrer[@action]') . '" value="' . $actionName . '" />' . LF;
        $result .= '<input type="hidden" name="' . $this->prefixFieldName('__referrer[arguments]') . '" value="' . htmlspecialchars($this->hashService->appendHmac(base64_encode(serialize($request->getArguments())))) . '" />' . LF;
        $result .= $this->renderHiddenSecuredReferrerField();

        return $result;
    }

    /**
     * Renders hidden form field for secured referrer information about the current controller and action.
     *
     * This method is called twice, to deal with subclasses of this class in a most compatible way
     *
     * @return string Hidden field with secured referrer information
     */
    protected function renderHiddenSecuredReferrerField()
    {
        if ($this->hasSecuredHiddenFieldsRendered()) {
            return '';
        }
        $request = $this->renderingContext->getControllerContext()->getRequest();
        $extensionName = $request->getControllerExtensionName();
        $vendorName = $request->getControllerVendorName();
        $controllerName = $request->getControllerName();
        $actionName = $request->getControllerActionName();
        $actionRequest = [
            '@extension' => $extensionName,
            '@controller' => $controllerName,
            '@action' => $actionName,
        ];
        if ($vendorName !== null) {
            $actionRequest['@vendor'] = $vendorName;
        }
        $result = '<input type="hidden" name="' . $this->prefixFieldName('__referrer[@request]') . '" value="' . htmlspecialchars($this->hashService->appendHmac(serialize($actionRequest))) . '" />' . LF;
        $this->addSecuredHiddenFieldsRenderedToViewHelperVariableContainer();
        return $result;
    }

    /**
     * Adds the form object name to the ViewHelperVariableContainer if "objectName" argument or "name" attribute is specified.
     *
     * @return void
     */
    protected function addFormObjectNameToViewHelperVariableContainer()
    {
        $formObjectName = $this->getFormObjectName();
        if ($formObjectName !== null) {
            $this->viewHelperVariableContainer->add(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formObjectName', $formObjectName);
        }
    }

    /**
     * Removes the form name from the ViewHelperVariableContainer.
     *
     * @return void
     */
    protected function removeFormObjectNameFromViewHelperVariableContainer()
    {
        $formObjectName = $this->getFormObjectName();
        if ($formObjectName !== null) {
            $this->viewHelperVariableContainer->remove(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formObjectName');
        }
    }

    /**
     * Returns the name of the object that is bound to this form.
     * If the "objectName" argument has been specified, this is returned. Otherwise the name attribute of this form.
     * If neither objectName nor name arguments have been set, NULL is returned.
     *
     * @return string specified Form name or NULL if neither $objectName nor $name arguments have been specified
     */
    protected function getFormObjectName()
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
     *
     * @return void
     */
    protected function addFormObjectToViewHelperVariableContainer()
    {
        if ($this->hasArgument('object')) {
            $this->viewHelperVariableContainer->add(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formObject', $this->arguments['object']);
            $this->viewHelperVariableContainer->add(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'additionalIdentityProperties', []);
        }
    }

    /**
     * Removes the form object from the ViewHelperVariableContainer.
     *
     * @return void
     */
    protected function removeFormObjectFromViewHelperVariableContainer()
    {
        if ($this->hasArgument('object')) {
            $this->viewHelperVariableContainer->remove(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formObject');
            $this->viewHelperVariableContainer->remove(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'additionalIdentityProperties');
        }
    }

    /**
     * Adds the field name prefix to the ViewHelperVariableContainer
     *
     * @return void
     */
    protected function addFieldNamePrefixToViewHelperVariableContainer()
    {
        $fieldNamePrefix = $this->getFieldNamePrefix();
        $this->viewHelperVariableContainer->add(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'fieldNamePrefix', $fieldNamePrefix);
    }

    /**
     * Get the field name prefix
     *
     * @return string
     */
    protected function getFieldNamePrefix()
    {
        if ($this->hasArgument('fieldNamePrefix')) {
            return $this->arguments['fieldNamePrefix'];
        } else {
            return $this->getDefaultFieldNamePrefix();
        }
    }

    /**
     * Removes field name prefix from the ViewHelperVariableContainer
     *
     * @return void
     */
    protected function removeFieldNamePrefixFromViewHelperVariableContainer()
    {
        $this->viewHelperVariableContainer->remove(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'fieldNamePrefix');
    }

    /**
     * Adds a container for form field names to the ViewHelperVariableContainer
     *
     * @return void
     */
    protected function addFormFieldNamesToViewHelperVariableContainer()
    {
        $this->viewHelperVariableContainer->add(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formFieldNames', []);
    }

    /**
     * Removes the container for form field names from the ViewHelperVariableContainer
     *
     * @return void
     */
    protected function removeFormFieldNamesFromViewHelperVariableContainer()
    {
        $this->viewHelperVariableContainer->remove(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formFieldNames');
        if ($this->viewHelperVariableContainer->exists(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'renderedHiddenFields')) {
            $this->viewHelperVariableContainer->remove(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'renderedHiddenFields');
        }
    }

    /**
     * Adds flag to indicate the secured hidden fields have been rendered to the ViewHelperVariableContainer
     */
    protected function addSecuredHiddenFieldsRenderedToViewHelperVariableContainer()
    {
        $this->viewHelperVariableContainer->add(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'securedHiddenFieldsRendered', true);
    }

    /**
     * Checks whether the secured hidden fields have been rendered
     *
     * @return bool
     */
    protected function hasSecuredHiddenFieldsRendered()
    {
        return $this->viewHelperVariableContainer->exists(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'securedHiddenFieldsRendered');
    }

    /**
     * Removes flag to indicate the secured hidden fields have been rendered from the ViewHelperVariableContainer
     */
    protected function removeSecuredHiddenFieldsRenderedFromViewHelperVariableContainer()
    {
        $this->viewHelperVariableContainer->remove(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'securedHiddenFieldsRendered');
    }

    /**
     * Render the request hash field
     *
     * @return string the hmac field
     */
    protected function renderRequestHashField()
    {
        $formFieldNames = $this->viewHelperVariableContainer->get(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formFieldNames');
        $this->postProcessUriArgumentsForRequesthash($this->formActionUriArguments, $formFieldNames);
        $requestHash = $this->requestHashService->generateRequestHash($formFieldNames, $this->getFieldNamePrefix());
        // in v4, we need to prefix __hmac as well to make it show up in the request object.
        return '<input type="hidden" name="' . $this->prefixFieldName('__hmac') . '" value="' . htmlspecialchars($requestHash) . '" />';
    }

    /**
     * Add the URI arguments after postprocessing to the request hash as well.
     */
    protected function postProcessUriArgumentsForRequestHash($arguments, &$results, $currentPrefix = '', $level = 0)
    {
        if (!count($arguments)) {
            return;
        }
        foreach ($arguments as $argumentName => $argumentValue) {
            if (is_array($argumentValue)) {
                $prefix = $level == 0 ? $argumentName : $currentPrefix . '[' . $argumentName . ']';
                $this->postProcessUriArgumentsForRequestHash($argumentValue, $results, $prefix, $level + 1);
            } else {
                $results[] = $level == 0 ? $argumentName : $currentPrefix . '[' . $argumentName . ']';
            }
        }
    }

    /**
     * Retrieves the default field name prefix for this form
     *
     * @return string default field name prefix
     */
    protected function getDefaultFieldNamePrefix()
    {
        $request = $this->controllerContext->getRequest();
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
        } else {
            return '';
        }
    }

    /**
     * Remove Checkbox field names from ViewHelper variable container, to start from scratch when a new form starts.
     */
    protected function removeCheckboxFieldNamesFromViewHelperVariableContainer()
    {
        if ($this->viewHelperVariableContainer->exists(\TYPO3\CMS\Fluid\ViewHelpers\Form\CheckboxViewHelper::class, 'checkboxFieldNames')) {
            $this->viewHelperVariableContainer->remove(\TYPO3\CMS\Fluid\ViewHelpers\Form\CheckboxViewHelper::class, 'checkboxFieldNames');
        }
    }

    /**
     * Render the request hash field
     *
     * @return string The hmac field
     */
    protected function renderTrustedPropertiesField()
    {
        $formFieldNames = $this->viewHelperVariableContainer->get(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formFieldNames');
        $requestHash = $this->mvcPropertyMappingConfigurationService->generateTrustedPropertiesToken($formFieldNames, $this->getFieldNamePrefix());
        return '<input type="hidden" name="' . $this->prefixFieldName('__trustedProperties') . '" value="' . htmlspecialchars($requestHash) . '" />';
    }
}
