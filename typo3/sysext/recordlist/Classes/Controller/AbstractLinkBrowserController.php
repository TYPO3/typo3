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

namespace TYPO3\CMS\Recordlist\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Recordlist\LinkHandler\LinkHandlerInterface;

/**
 * Script class for the Link Browser window.
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
abstract class AbstractLinkBrowserController
{
    /**
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * @var array
     */
    protected $parameters;

    /**
     * URL of current request
     *
     * @var string
     */
    protected $thisScript = '';

    /**
     * @var array<string,array>
     */
    protected $linkHandlers = [];

    /**
     * All parts of the current link
     *
     * Comprised of url information and additional link parameters.
     *
     * @var array<string,mixed>
     */
    protected $currentLinkParts = [];

    /**
     * Link handler responsible for the current active link
     *
     * @var LinkHandlerInterface|null
     */
    protected $currentLinkHandler;

    /**
     * The ID of the currently active link handler
     *
     * @var string
     */
    protected $currentLinkHandlerId;

    /**
     * Link handler to be displayed
     *
     * @var LinkHandlerInterface $displayedLinkHandler
     */
    protected $displayedLinkHandler;

    /**
     * The ID of the displayed link handler
     *
     * This is read from the 'act' GET parameter
     *
     * @var string
     */
    protected $displayedLinkHandlerId = '';

    /**
     * List of available link attribute fields
     *
     * @var string[]
     */
    protected $linkAttributeFields = [];

    /**
     * Values of the link attributes
     *
     * @var string[]
     */
    protected $linkAttributeValues = [];

    /**
     * @var array
     */
    protected $hookObjects = [];

    protected DependencyOrderingService $dependencyOrderingService;
    protected PageRenderer $pageRenderer;
    protected UriBuilder $uriBuilder;
    protected LinkService $linkService;
    protected ModuleTemplateFactory $moduleTemplateFactory;

    public function __construct(
        DependencyOrderingService $dependencyOrderingService,
        PageRenderer $pageRenderer,
        UriBuilder $uriBuilder,
        LinkService $linkService,
        ModuleTemplateFactory $moduleTemplateFactory
    ) {
        $this->dependencyOrderingService = $dependencyOrderingService;
        $this->pageRenderer = $pageRenderer;
        $this->uriBuilder = $uriBuilder;
        $this->linkService = $linkService;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
        $this->initHookObjects();
        $this->init();
    }

    /**
     * Initialize the controller
     */
    protected function init()
    {
        $this->getLanguageService()->includeLLFile('EXT:recordlist/Resources/Private/Language/locallang_browse_links.xlf');
    }

    /**
     * Initialize hook objects implementing the interface
     *
     * @throws \UnexpectedValueException
     */
    protected function initHookObjects()
    {
        $hooks = $this->dependencyOrderingService->orderByDependencies(
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['LinkBrowser']['hooks'] ?? []
        );
        foreach ($hooks as $key => $hook) {
            $this->hookObjects[] = GeneralUtility::makeInstance($hook['handler']);
        }
    }

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);
        $this->moduleTemplate->getDocHeaderComponent()->disable();
        $view = $this->moduleTemplate->getView();
        $view->setTemplate('LinkBrowser');
        $view->getRequest()->setControllerExtensionName('recordlist');
        $view->setTemplateRootPaths(['EXT:recordlist/Resources/Private/Templates/LinkBrowser/']);
        $view->setPartialRootPaths(['EXT:recordlist/Resources/Private/Partials/LinkBrowser/']);
        $view->setLayoutRootPaths(['EXT:backend/Resources/Private/Layouts/', 'EXT:recordlist/Resources/Private/Layouts/']);
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_misc.xlf');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_core.xlf');

        $this->determineScriptUrl($request);
        $this->initVariables($request);
        $this->loadLinkHandlers();
        $this->initCurrentUrl();

        $menuData = $this->buildMenuArray();
        $renderLinkAttributeFields = $this->renderLinkAttributeFields();
        if (method_exists($this->displayedLinkHandler, 'setView')) {
            $this->displayedLinkHandler->setView($view);
        }
        $browserContent = $this->displayedLinkHandler->render($request);

        $this->initDocumentTemplate();
        $this->moduleTemplate->setTitle('Link Browser');

        if (!empty($this->currentLinkParts)) {
            $this->renderCurrentUrl();
        }

        $view->assign('menuItems', $menuData);
        $view->assign('linkAttributes', $renderLinkAttributeFields);
        $view->assign('contentOnly', $request->getQueryParams()['contentOnly'] ?? false);

        if ($request->getQueryParams()['contentOnly'] ?? false) {
            return new HtmlResponse($view->render());
        }
        if ($browserContent) {
            $view->assign('content', $browserContent);
        }
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Sets the script url depending on being a module or script request
     *
     * @param ServerRequestInterface $request
     *
     * @throws \TYPO3\CMS\Backend\Routing\Exception\ResourceNotFoundException
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    protected function determineScriptUrl(ServerRequestInterface $request)
    {
        if ($routePath = $request->getQueryParams()['route']) {
            $this->thisScript = (string)$this->uriBuilder->buildUriFromRoutePath($routePath);
        } else {
            /** @var NormalizedParams $normalizedParams */
            $normalizedParams = $request->getAttribute('normalizedParams');
            $this->thisScript = $normalizedParams->getScriptName();
        }
    }

    /**
     * @param ServerRequestInterface $request
     */
    protected function initVariables(ServerRequestInterface $request)
    {
        $queryParams = $request->getQueryParams();
        $this->displayedLinkHandlerId = $queryParams['act'] ?? '';
        $this->parameters = $queryParams['P'] ?? [];
        $this->linkAttributeValues = $queryParams['linkAttributes'] ?? [];
    }

    /**
     * @throws \UnexpectedValueException
     */
    protected function loadLinkHandlers()
    {
        $linkHandlers = $this->getLinkHandlers();
        if (empty($linkHandlers)) {
            throw new \UnexpectedValueException('No link handlers are configured. Check page TSconfig TCEMAIN.linkHandler.', 1442787911);
        }

        $lang = $this->getLanguageService();
        foreach ($linkHandlers as $identifier => $configuration) {
            $identifier = rtrim($identifier, '.');

            if (empty($configuration['handler'])) {
                throw new \UnexpectedValueException(sprintf('Missing handler for link handler "%1$s", check page TSconfig TCEMAIN.linkHandler.%1$s.handler', $identifier), 1494579849);
            }

            /** @var LinkHandlerInterface $handler */
            $handler = GeneralUtility::makeInstance($configuration['handler']);
            $handler->initialize(
                $this,
                $identifier,
                $configuration['configuration.'] ?? []
            );

            $label = !empty($configuration['label']) ? $lang->sL($configuration['label']) : '';
            $label = $label ?: $lang->sL('LLL:EXT:recordlist/Resources/Private/Language/locallang.xlf:error.linkHandlerTitleMissing');
            $this->linkHandlers[$identifier] = [
                'handlerInstance' => $handler,
                'label' => htmlspecialchars($label),
                'displayBefore' => isset($configuration['displayBefore']) ? GeneralUtility::trimExplode(',', $configuration['displayBefore']) : [],
                'displayAfter' => isset($configuration['displayAfter']) ? GeneralUtility::trimExplode(',', $configuration['displayAfter']) : [],
                'scanBefore' => isset($configuration['scanBefore']) ? GeneralUtility::trimExplode(',', $configuration['scanBefore']) : [],
                'scanAfter' => isset($configuration['scanAfter']) ? GeneralUtility::trimExplode(',', $configuration['scanAfter']) : [],
                'addParams' => $configuration['addParams'] ?? '',
            ];
        }
    }

    /**
     * Reads the configured link handlers from page TSconfig
     *
     * @return array<string, array<mixed>>
     */
    protected function getLinkHandlers()
    {
        $linkHandlers = (array)(BackendUtility::getPagesTSconfig($this->getCurrentPageId())['TCEMAIN.']['linkHandler.'] ?? []);
        foreach ($this->hookObjects as $hookObject) {
            if (method_exists($hookObject, 'modifyLinkHandlers')) {
                $linkHandlers = $hookObject->modifyLinkHandlers($linkHandlers, $this->currentLinkParts);
            }
        }

        return $linkHandlers;
    }

    /**
     * Initialize $this->currentLinkParts and $this->currentLinkHandler
     */
    protected function initCurrentUrl()
    {
        if (empty($this->currentLinkParts)) {
            return;
        }

        $orderedHandlers = $this->dependencyOrderingService->orderByDependencies($this->linkHandlers, 'scanBefore', 'scanAfter');

        // find responsible handler for current link
        foreach ($orderedHandlers as $key => $configuration) {
            /** @var LinkHandlerInterface $handler */
            $handler = $configuration['handlerInstance'];
            if ($handler->canHandleLink($this->currentLinkParts)) {
                $this->currentLinkHandler = $handler;
                $this->currentLinkHandlerId = $key;
                break;
            }
        }
        // reset the link if we have no handler for it
        if (!$this->currentLinkHandler) {
            $this->currentLinkParts = [];
        }

        // overwrite any preexisting
        foreach ($this->currentLinkParts as $key => $part) {
            if ($key !== 'url') {
                $this->linkAttributeValues[$key] = $part;
            }
        }
    }

    /**
     * Initialize body tag parameters, but can be used for other parts as well
     */
    protected function initDocumentTemplate()
    {
        $bodyTag = $this->moduleTemplate->getBodyTag();
        $bodyTag = str_replace('>', ' ' . GeneralUtility::implodeAttributes($this->getBodyTagAttributes(), true, true) . '>', $bodyTag);
        $this->moduleTemplate->setBodyTag($bodyTag);
    }

    /**
     * Add the currently set URL to the view
     */
    protected function renderCurrentUrl()
    {
        $this->moduleTemplate->getView()->assign('currentUrl', $this->currentLinkHandler->formatCurrentUrl());
    }

    /**
     * Returns an array definition of the top menu
     *
     * @return mixed[][]
     */
    protected function buildMenuArray()
    {
        $allowedItems = $this->getAllowedItems();
        if ($this->displayedLinkHandlerId && !in_array($this->displayedLinkHandlerId, $allowedItems, true)) {
            $this->displayedLinkHandlerId = '';
        }

        $allowedHandlers = array_flip($allowedItems);
        $menuDef = [];
        foreach ($this->linkHandlers as $identifier => $configuration) {
            if (!isset($allowedHandlers[$identifier])) {
                continue;
            }

            /** @var LinkHandlerInterface $handlerInstance */
            $handlerInstance = $configuration['handlerInstance'];
            $isActive = $this->displayedLinkHandlerId === $identifier || !$this->displayedLinkHandlerId && $handlerInstance === $this->currentLinkHandler;
            if ($isActive) {
                $this->displayedLinkHandler = $handlerInstance;
                if (!$this->displayedLinkHandlerId) {
                    $this->displayedLinkHandlerId = $this->currentLinkHandlerId;
                }
            }

            $menuDef[$identifier] = [
                'isActive' => $isActive,
                'label' => $configuration['label'],
                'url' => $this->thisScript . HttpUtility::buildQueryString($this->getUrlParameters(['act' => $identifier]), '&'),
                'addParams' => $configuration['addParams'] ?? '',
                'before' => $configuration['displayBefore'],
                'after' => $configuration['displayAfter'],
            ];
        }

        $menuDef = $this->dependencyOrderingService->orderByDependencies($menuDef);

        // if there is no active tab
        if (!$this->displayedLinkHandler) {
            // empty the current link
            $this->currentLinkParts = [];
            $this->currentLinkHandler = null;
            // select first tab
            $this->displayedLinkHandlerId = (string)array_key_first($menuDef);
            $this->displayedLinkHandler = $this->linkHandlers[$this->displayedLinkHandlerId]['handlerInstance'];
            $menuDef[$this->displayedLinkHandlerId]['isActive'] = true;
        }

        return $menuDef;
    }

    /**
     * Get the allowed items or tabs
     *
     * @return string[]
     */
    protected function getAllowedItems()
    {
        $allowedItems = array_keys($this->linkHandlers);

        foreach ($this->hookObjects as $hookObject) {
            if (method_exists($hookObject, 'modifyAllowedItems')) {
                $allowedItems = $hookObject->modifyAllowedItems($allowedItems, $this->currentLinkParts);
            }
        }

        // Initializing the action value, possibly removing blinded values etc:
        $blindLinkOptions = isset($this->parameters['params']['blindLinkOptions'])
            ? GeneralUtility::trimExplode(',', $this->parameters['params']['blindLinkOptions'])
            : [];
        $allowedItems = array_diff($allowedItems, $blindLinkOptions);

        return $allowedItems;
    }

    /**
     * Get the allowed link attributes
     *
     * @return string[]
     */
    protected function getAllowedLinkAttributes()
    {
        $allowedLinkAttributes = $this->displayedLinkHandler->getLinkAttributes();

        // Removing link fields if configured
        $blindLinkFields = isset($this->parameters['params']['blindLinkFields'])
            ? GeneralUtility::trimExplode(',', $this->parameters['params']['blindLinkFields'], true)
            : [];
        $allowedLinkAttributes = array_diff($allowedLinkAttributes, $blindLinkFields);

        return $allowedLinkAttributes;
    }

    /**
     * Renders the link attributes for the selected link handler
     *
     * @return string
     */
    protected function renderLinkAttributeFields()
    {
        $fieldRenderingDefinitions = $this->getLinkAttributeFieldDefinitions();

        $fieldRenderingDefinitions = $this->displayedLinkHandler->modifyLinkAttributes($fieldRenderingDefinitions);

        $this->linkAttributeFields = $this->getAllowedLinkAttributes();

        $content = '';
        foreach ($this->linkAttributeFields as $attribute) {
            $content .= $fieldRenderingDefinitions[$attribute] ?? '';
        }

        // add update button if appropriate
        if (!empty($this->currentLinkParts) && $this->displayedLinkHandler === $this->currentLinkHandler && $this->currentLinkHandler->isUpdateSupported()) {
            $this->moduleTemplate->getView()->assign('showUpdateParametersButton', true);
        }
        return $content;
    }

    /**
     * Create an array of link attribute field rendering definitions
     *
     * @return string[]
     */
    protected function getLinkAttributeFieldDefinitions()
    {
        $lang = $this->getLanguageService();

        $fieldRenderingDefinitions = [];
        $fieldRenderingDefinitions['target'] = '
            <!-- Selecting target for link: -->
            <form action="" name="ltargetform" id="ltargetform" class="t3js-dummyform form-horizontal">
                <div class="row mb-3" id="typo3-linkTarget">
                    <label class="col-sm-3 col-form-label">' . htmlspecialchars($lang->getLL('target')) . '</label>
                    <div class="col-sm-4">
                        <input type="text" name="ltarget" class="t3js-linkTarget form-control"
                            value="' . htmlspecialchars($this->linkAttributeValues['target'] ?? '') . '" />
                    </div>
                    <div class="col-sm-5">
                        <select name="ltarget_type" class="t3js-targetPreselect form-select">
                            <option value=""></option>
                            <option value="_top">' . htmlspecialchars($lang->getLL('top')) . '</option>
                            <option value="_blank">' . htmlspecialchars($lang->getLL('newWindow')) . '</option>
                        </select>
                    </div>
                </div>
            </form>';

        $fieldRenderingDefinitions['title'] = '
            <!-- Selecting title for link: -->
            <form action="" name="ltitleform" id="ltitleform" class="t3js-dummyform form-horizontal">
                <div class="row mb-3" id="typo3-linkTitle">
                    <label class="col-sm-3 col-form-label">' . htmlspecialchars($lang->getLL('title')) . '</label>
                    <div class="col-sm-9">
                        <input type="text" name="ltitle" class="form-control"
                            value="' . htmlspecialchars($this->linkAttributeValues['title'] ?? '') . '" />
                    </div>
                </div>
            </form>';

        $fieldRenderingDefinitions['class'] = '
            <!-- Selecting class for link: -->
            <form action="" name="lclassform" id="lclassform" class="t3js-dummyform form-horizontal">
                <div class="row mb-3" id="typo3-linkClass">
                    <label class="col-sm-3 col-form-label">' . htmlspecialchars($lang->getLL('class')) . '</label>
                    <div class="col-sm-9">
                        <input type="text" name="lclass" class="form-control"
                            value="' . htmlspecialchars($this->linkAttributeValues['class'] ?? '') . '" />
                    </div>
                </div>
            </form>';

        $fieldRenderingDefinitions['params'] = '
            <!-- Selecting params for link: -->
            <form action="" name="lparamsform" id="lparamsform" class="t3js-dummyform form-horizontal">
                <div class="row mb-3" id="typo3-linkParams">
                    <label class="col-sm-3 col-form-label">' . htmlspecialchars($lang->getLL('params')) . '</label>
                    <div class="col-sm-9">
                        <input type="text" name="lparams" class="form-control"
                            value="' . htmlspecialchars($this->linkAttributeValues['params'] ?? '') . '" />
                    </div>
                </div>
            </form>';

        return $fieldRenderingDefinitions;
    }

    /**
     * @param array|null $overrides
     * @return array Array of parameters which have to be added to URLs
     */
    public function getUrlParameters(array $overrides = null)
    {
        return [
            'act' => $overrides['act'] ?? $this->displayedLinkHandlerId,
            'P' => $overrides['P'] ?? $this->parameters,
        ];
    }

    /**
     * Get attributes for the body tag
     *
     * @return string[] Array of body-tag attributes
     */
    protected function getBodyTagAttributes()
    {
        $attributes = $this->displayedLinkHandler->getBodyTagAttributes();
        return array_merge(
            $attributes,
            [
                'data-url-parameters' => json_encode($this->getUrlParameters()) ?: '',
                'data-parameters' => json_encode($this->parameters) ?: '',
                'data-link-attribute-fields' => json_encode($this->linkAttributeFields) ?: '',
            ]
        );
    }

    /**
     * Return the ID of current page
     *
     * @return int
     */
    abstract protected function getCurrentPageId();

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Retrieve the configuration
     *
     * @return array
     */
    public function getConfiguration()
    {
        return [];
    }

    /**
     * @return string
     */
    protected function getDisplayedLinkHandlerId()
    {
        return $this->displayedLinkHandlerId;
    }

    /**
     * @return string
     */
    public function getScriptUrl()
    {
        return $this->thisScript;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
