<?php
namespace TYPO3\CMS\Fluid\Core\Widget;

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

use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * This object stores the WidgetContext for the currently active widgets
 * of the current user, to make sure the WidgetContext is available in
 * Widget AJAX requests.
 *
 * @internal This class is only used internally by the widget framework.
 */
class AjaxWidgetContextHolder implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * An array $ajaxWidgetIdentifier => $widgetContext
     * which stores the widget context.
     *
     * @var WidgetContext[]
     */
    protected $widgetContexts = [];

    /**
     * @var string
     */
    protected $widgetContextsStorageKey = 'TYPO3\\CMS\\Fluid\\Core\\Widget\\AjaxWidgetContextHolder_widgetContexts';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->loadWidgetContexts();
    }

    /**
     * Loads the windget contexts from the TYPO3 user session
     */
    protected function loadWidgetContexts()
    {
        if (isset($GLOBALS['TSFE']) && $GLOBALS['TSFE'] instanceof TypoScriptFrontendController) {
            $this->widgetContexts = $this->buildWidgetContextsFromArray(
                json_decode($GLOBALS['TSFE']->fe_user->getKey('ses', $this->widgetContextsStorageKey ?? null), true) ?? []
            );
        } else {
            $this->widgetContexts = $this->buildWidgetContextsFromArray(
                json_decode($GLOBALS['BE_USER']->uc[$this->widgetContextsStorageKey] ?? '', true) ?? []
            );
            $GLOBALS['BE_USER']->writeUC();
        }
    }

    /**
     * Get the widget context for the given $ajaxWidgetId.
     *
     * @param string $ajaxWidgetId
     * @return WidgetContext
     */
    public function get($ajaxWidgetId)
    {
        if (!isset($this->widgetContexts[$ajaxWidgetId])) {
            throw new \TYPO3\CMS\Fluid\Core\Widget\Exception\WidgetContextNotFoundException('No widget context was found for the Ajax Widget Identifier "' . $ajaxWidgetId . '". This only happens if AJAX URIs are called without including the widget on a page.', 1284793775);
        }
        return $this->widgetContexts[$ajaxWidgetId];
    }

    /**
     * Stores the WidgetContext inside the Context, and sets the
     * AjaxWidgetIdentifier inside the Widget Context correctly.
     *
     * @param WidgetContext $widgetContext
     */
    public function store(WidgetContext $widgetContext)
    {
        $ajaxWidgetId = md5(uniqid(mt_rand(), true));
        $widgetContext->setAjaxWidgetIdentifier($ajaxWidgetId);
        $this->widgetContexts[$ajaxWidgetId] = $widgetContext;
        $this->storeWidgetContexts();
    }

    /**
     * Persists the widget contexts in the TYPO3 user session
     */
    protected function storeWidgetContexts()
    {
        if (isset($GLOBALS['TSFE']) && $GLOBALS['TSFE'] instanceof TypoScriptFrontendController) {
            $GLOBALS['TSFE']->fe_user->setKey('ses', $this->widgetContextsStorageKey, json_encode($this->widgetContexts));
            $GLOBALS['TSFE']->fe_user->storeSessionData();
        } else {
            $GLOBALS['BE_USER']->uc[$this->widgetContextsStorageKey] = json_encode($this->widgetContexts);
            $GLOBALS['BE_USER']->writeUc();
        }
    }

    /**
     * Builds WidgetContext instances from JSON representation,
     * this is basically required for AJAX widgets only.
     *
     * @param array $data
     * @return WidgetContext[]
     */
    protected function buildWidgetContextsFromArray(array $data): array
    {
        $widgetContexts = [];
        foreach ($data as $widgetId => $widgetContextData) {
            $widgetContexts[$widgetId] = WidgetContext::fromArray($widgetContextData);
        }
        return $widgetContexts;
    }
}
