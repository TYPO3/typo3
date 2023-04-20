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

namespace TYPO3\CMS\Backend\Controller;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Controller\Event\ModifyAllowedItemsEvent;
use TYPO3\CMS\Backend\Controller\Event\ModifyLinkHandlersEvent;
use TYPO3\CMS\Backend\LinkHandler\LinkHandlerInterface;
use TYPO3\CMS\Backend\LinkHandler\LinkHandlerVariableProviderInterface;
use TYPO3\CMS\Backend\LinkHandler\LinkHandlerViewProviderInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\PageRendererBackendSetupTrait;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\View\ViewInterface;

/**
 * Script class for the Link Browser window.
 *
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
abstract class AbstractLinkBrowserController
{
    use PageRendererBackendSetupTrait;

    /**
     * URL of current request
     */
    protected string $thisScript = '';

    /**
     * @var array<string, array>
     */
    protected array $linkHandlers = [];

    /**
     * All parts of the current link.
     * Comprised of url information and additional link parameters.
     *
     * @var array<string, mixed>
     */
    protected array $currentLinkParts = [];

    /**
     * Link handler responsible for the current active link
     */
    protected ?LinkHandlerInterface $currentLinkHandler = null;

    /**
     * The ID of the currently active link handler
     */
    protected string $currentLinkHandlerId;

    /**
     * Link handler to be displayed
     */
    protected ?LinkHandlerInterface $displayedLinkHandler = null;

    /**
     * The ID of the displayed link handler
     * This is read from the 'act' GET parameter
     */
    protected string $displayedLinkHandlerId = '';

    /**
     * List of available link attribute fields
     *
     * @var string[]
     */
    protected array $linkAttributeFields = [];

    /**
     * Values of the link attributes
     *
     * @var string[]
     */
    protected array $linkAttributeValues = [];

    protected array $parameters;

    protected DependencyOrderingService $dependencyOrderingService;
    protected PageRenderer $pageRenderer;
    protected UriBuilder $uriBuilder;
    protected ExtensionConfiguration $extensionConfiguration;
    protected BackendViewFactory $backendViewFactory;
    protected EventDispatcherInterface $eventDispatcher;

    public function injectDependencyOrderingService(DependencyOrderingService $dependencyOrderingService): void
    {
        $this->dependencyOrderingService = $dependencyOrderingService;
    }

    public function injectPageRenderer(PageRenderer $pageRenderer): void
    {
        $this->pageRenderer = $pageRenderer;
    }

    public function injectUriBuilder(UriBuilder $uriBuilder): void
    {
        $this->uriBuilder = $uriBuilder;
    }

    public function injectExtensionConfiguration(ExtensionConfiguration $extensionConfiguration): void
    {
        $this->extensionConfiguration = $extensionConfiguration;
    }

    public function injectBackendViewFactory(BackendViewFactory $backendViewFactory): void
    {
        $this->backendViewFactory = $backendViewFactory;
    }

    public function injectEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    abstract public function getConfiguration(): array;

    abstract protected function initDocumentTemplate(): void;

    abstract protected function getCurrentPageId(): int;

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->setUpBasicPageRendererForBackend($this->pageRenderer, $this->extensionConfiguration, $request, $this->getLanguageService());
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_misc.xlf');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_core.xlf');

        $this->determineScriptUrl($request);
        $this->initVariables($request);
        $this->loadLinkHandlers();
        $this->initCurrentUrl();

        $menuData = $this->buildMenuArray();
        if ($this->displayedLinkHandler instanceof LinkHandlerViewProviderInterface) {
            $view = $this->displayedLinkHandler->createView($this->backendViewFactory, $request);
        } else {
            $view = $this->backendViewFactory->create($request, ['typo3/cms-backend']);
        }
        if ($this->displayedLinkHandler instanceof LinkHandlerVariableProviderInterface) {
            $this->displayedLinkHandler->initializeVariables($request);
        }
        $renderLinkAttributeFields = $this->renderLinkAttributeFields($view);
        if (!empty($this->currentLinkParts)) {
            $this->renderCurrentUrl($view);
        }
        if (method_exists($this->displayedLinkHandler, 'setView')) {
            $this->displayedLinkHandler->setView($view);
        }
        $view->assignMultiple([
            'initialNavigationWidth' => $this->getBackendUser()->uc['selector']['navigation']['width'] ?? 250,
            'menuItems' => $menuData,
            'linkAttributes' => $renderLinkAttributeFields,
            'contentOnly' => $request->getQueryParams()['contentOnly'] ?? false,
        ]);
        $content = $this->displayedLinkHandler->render($request);
        if (empty($content)) {
            // @todo: b/w compat layer for link handler that don't render full view but return empty
            //        string instead. This case is unfortunate and should be removed if it gives
            //        headaches at some point. If so, above  method_exists($this->displayedLinkHandler, 'setView')
            //        should be removed and setView() method should be made mandatory, or the entire
            //        construct should be refactored a bit.
            $content = $view->render();
        }
        $this->initDocumentTemplate();
        $this->pageRenderer->setTitle('Link Browser');
        if ($request->getQueryParams()['contentOnly'] ?? false) {
            return new HtmlResponse($content);
        }
        $this->pageRenderer->setBodyContent('<body ' . GeneralUtility::implodeAttributes($this->getBodyTagAttributes(), true, true) . '>' . $content);
        return $this->pageRenderer->renderResponse();
    }

    /**
     * @return array{act: string, P: array} Array of parameters which have to be added to URLs
     */
    public function getUrlParameters(array $overrides = null): array
    {
        return [
            'act' => $overrides['act'] ?? $this->displayedLinkHandlerId,
            'P' => $overrides['P'] ?? $this->parameters,
        ];
    }

    public function getScriptUrl(): string
    {
        return $this->thisScript;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Sets the script url depending on being a module or script request.
     */
    protected function determineScriptUrl(ServerRequestInterface $request): void
    {
        if ($route = $request->getAttribute('route')) {
            $this->thisScript = (string)$this->uriBuilder->buildUriFromRoute($route->getOption('_identifier'));
        } else {
            $normalizedParams = $request->getAttribute('normalizedParams');
            $this->thisScript = $normalizedParams->getScriptName();
        }
    }

    protected function initVariables(ServerRequestInterface $request): void
    {
        $queryParams = $request->getQueryParams();
        $this->displayedLinkHandlerId = $queryParams['act'] ?? '';
        $this->parameters = $queryParams['P'] ?? [];
        $this->linkAttributeValues = $queryParams['linkAttributes'] ?? [];
    }

    /**
     * @throws \UnexpectedValueException
     */
    protected function loadLinkHandlers(): void
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
            $label = $label ?: $lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:error.linkHandlerTitleMissing');
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
     * @return array<string, array>
     */
    protected function getLinkHandlers(): array
    {
        $linkHandlers = (array)(BackendUtility::getPagesTSconfig($this->getCurrentPageId())['TCEMAIN.']['linkHandler.'] ?? []);
        return $this->eventDispatcher
            ->dispatch(new ModifyLinkHandlersEvent($linkHandlers, $this->currentLinkParts))
            ->getLinkHandlers();
    }

    /**
     * Initialize $this->currentLinkParts and $this->currentLinkHandler
     */
    protected function initCurrentUrl(): void
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
     * Add the currently set URL to the view
     */
    protected function renderCurrentUrl(ViewInterface $view): void
    {
        $view->assign('currentUrl', $this->currentLinkHandler->formatCurrentUrl());
    }

    /**
     * Returns an array definition of the top menu
     *
     * @return array[]
     */
    protected function buildMenuArray(): array
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
            $isActive = $this->displayedLinkHandlerId === $identifier || (!$this->displayedLinkHandlerId && $handlerInstance === $this->currentLinkHandler);
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
     * @return string[]
     */
    protected function getAllowedItems(): array
    {
        $allowedItems = $this->eventDispatcher
            ->dispatch(new ModifyAllowedItemsEvent(array_keys($this->linkHandlers), $this->currentLinkParts))
            ->getAllowedItems();

        if (isset($this->parameters['params']['allowedTypes'])) {
            $allowedItems = array_intersect($allowedItems, GeneralUtility::trimExplode(',', $this->parameters['params']['allowedTypes'], true));
        } elseif (isset($this->parameters['params']['blindLinkOptions'])) {
            // @todo Deprecate this option
            $allowedItems = array_diff($allowedItems, GeneralUtility::trimExplode(',', $this->parameters['params']['blindLinkOptions'], true));
        }

        return $allowedItems;
    }

    /**
     * @return string[]
     */
    protected function getAllowedLinkAttributes(): array
    {
        $allowedLinkAttributes = $this->displayedLinkHandler->getLinkAttributes();

        if (isset($this->parameters['params']['allowedOptions'])) {
            $allowedLinkAttributes = array_intersect($allowedLinkAttributes, GeneralUtility::trimExplode(',', $this->parameters['params']['allowedOptions'], true));
        } elseif (isset($this->parameters['params']['blindLinkFields'])) {
            // @todo Deprecate this option
            $allowedLinkAttributes = array_diff($allowedLinkAttributes, GeneralUtility::trimExplode(',', $this->parameters['params']['blindLinkFields'], true));
        }

        return $allowedLinkAttributes;
    }

    /**
     * Renders the link attributes for the selected link handler
     */
    protected function renderLinkAttributeFields(ViewInterface $view): string
    {
        $fieldRenderingDefinitions = $this->getLinkAttributeFieldDefinitions();
        $fieldRenderingDefinitions = $this->displayedLinkHandler->modifyLinkAttributes($fieldRenderingDefinitions);
        $this->linkAttributeFields = $this->getAllowedLinkAttributes();
        $content = '';
        foreach ($this->linkAttributeFields as $attribute) {
            $content .= $fieldRenderingDefinitions[$attribute] ?? '';
        }
        $view->assign('allowedLinkAttributes', array_combine($this->linkAttributeFields, $this->linkAttributeFields));

        // add update button if appropriate
        if (!empty($this->currentLinkParts) && $this->displayedLinkHandler === $this->currentLinkHandler && $this->currentLinkHandler->isUpdateSupported()) {
            $view->assign('showUpdateParametersButton', true);
        }
        return $content;
    }

    /**
     * Create an array of link attribute field rendering definitions
     *
     * @return string[]
     */
    protected function getLinkAttributeFieldDefinitions(): array
    {
        $lang = $this->getLanguageService();

        $fieldRenderingDefinitions = [];
        $fieldRenderingDefinitions['target'] = '
            <!-- Selecting target for link: -->
            <div class="element-browser-form-group">
                <label for="ltarget" class="form-label">' . htmlspecialchars($lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_browse_links.xlf:target')) . '</label>
                <span class="input-group">
                    <input id="ltarget" type="text" name="ltarget" class="t3js-linkTarget form-control"
                        value="' . htmlspecialchars($this->linkAttributeValues['target'] ?? '') . '" />
                    <select name="ltarget_type" class="t3js-targetPreselect form-select">
                        <option value=""></option>
                        <option value="_top">' . htmlspecialchars($lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_browse_links.xlf:top')) . '</option>
                        <option value="_blank">' . htmlspecialchars($lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_browse_links.xlf:newWindow')) . '</option>
                    </select>
                </span>
            </div>';

        $fieldRenderingDefinitions['title'] = '
            <!-- Selecting title for link: -->
            <div class="element-browser-form-group">
                <label for="ltitle" class="form-label">' . htmlspecialchars($lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_browse_links.xlf:title')) . '</label>
                <input id="ltitle" type="text" name="ltitle" class="form-control"
                    value="' . htmlspecialchars($this->linkAttributeValues['title'] ?? '') . '" />
            </div>';

        $fieldRenderingDefinitions['class'] = '
            <!-- Selecting class for link: -->
            <div class="element-browser-form-group">
                <label for="lclass" class="form-label">
                    ' . htmlspecialchars($lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_browse_links.xlf:class')) . '
                </label>
                <input id="lclass" type="text" name="lclass" class="form-control"
                    value="' . htmlspecialchars($this->linkAttributeValues['class'] ?? '') . '" />
            </div>';

        $fieldRenderingDefinitions['params'] = '
            <!-- Selecting params for link: -->
            <div class="element-browser-form-group">
                <label for="lparams" class="form-label">' . htmlspecialchars($lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_browse_links.xlf:params')) . '</label>
                <input id="lparams" type="text" name="lparams" class="form-control"
                    value="' . htmlspecialchars($this->linkAttributeValues['params'] ?? '') . '" />
            </div>';

        return $fieldRenderingDefinitions;
    }

    /**
     * @return string[] Array of body-tag attributes
     */
    protected function getBodyTagAttributes(): array
    {
        $attributes = $this->displayedLinkHandler->getBodyTagAttributes();
        return array_merge(
            $attributes,
            [
                'data-linkbrowser-parameters' => json_encode($this->parameters) ?: '',
                'data-linkbrowser-attribute-fields' => json_encode(array_values($this->linkAttributeFields)) ?: '',
            ]
        );
    }

    protected function getDisplayedLinkHandlerId(): string
    {
        return $this->displayedLinkHandlerId;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
