<?php
namespace TYPO3\CMS\Compatibility6\Hooks\TypoScriptFrontendController;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Html\HtmlParser;

/**
 * Class that hooks into TypoScriptFrontendController to do XHTML cleaning and prefixLocalAnchors functionality
 */
class ContentPostProcHook
{
    /**
     * @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected $pObj;

    /**
     * XHTML-clean the code, if flag config.xhtml_cleaning is set
     * to "all", same goes for config.prefixLocalAnchors
     *
     * @param array $parameters
     * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $parentObject
     */
    public function contentPostProcAll(&$parameters, $parentObject)
    {
        $this->pObj = $parentObject;
        // Fix local anchors in links, if flag set
        if ($this->doLocalAnchorFix() === 'all') {
            $GLOBALS['TT']->push('Local anchor fix, all', '');
            $this->prefixLocalAnchorsWithScript();
            $GLOBALS['TT']->pull();
        }
        // XHTML-clean the code, if flag set
        if ($this->doXHTML_cleaning() === 'all') {
            $GLOBALS['TT']->push('XHTML clean, all', '');
            $XHTML_clean = GeneralUtility::makeInstance(HtmlParser::class);
            $this->pObj->content = $XHTML_clean->XHTML_clean($this->pObj->content);
            $GLOBALS['TT']->pull();
        }
    }

    /**
     * XHTML-clean the code, if flag config.xhtml_cleaning is set
     * to "cached", same goes for config.prefixLocalAnchors
     *
     * @param array $parameters
     * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $parentObject
     */
    public function contentPostProcCached(&$parameters, $parentObject)
    {
        $this->pObj = $parentObject;
        // Fix local anchors in links, if flag set
        if ($this->doLocalAnchorFix() === 'cached') {
            $GLOBALS['TT']->push('Local anchor fix, cached', '');
            $this->prefixLocalAnchorsWithScript();
            $GLOBALS['TT']->pull();
        }
        // XHTML-clean the code, if flag set
        if ($this->doXHTML_cleaning() === 'cached') {
            $GLOBALS['TT']->push('XHTML clean, cached', '');
            $XHTML_clean = GeneralUtility::makeInstance(HtmlParser::class);
            $this->pObj->content = $XHTML_clean->XHTML_clean($this->pObj->content);
            $GLOBALS['TT']->pull();
        }
    }

    /**
     * XHTML-clean the code, if flag config.xhtml_cleaning is set
     * to "output", same goes for config.prefixLocalAnchors
     *
     * @param array $parameters
     * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $parentObject
     */
    public function contentPostProcOutput(&$parameters, $parentObject)
    {
        $this->pObj = $parentObject;
        // Fix local anchors in links, if flag set
        if ($this->doLocalAnchorFix() === 'output') {
            $GLOBALS['TT']->push('Local anchor fix, output', '');
            $this->prefixLocalAnchorsWithScript();
            $GLOBALS['TT']->pull();
        }
        // XHTML-clean the code, if flag set
        if ($this->doXHTML_cleaning() === 'output') {
            $GLOBALS['TT']->push('XHTML clean, output', '');
            $XHTML_clean = GeneralUtility::makeInstance(HtmlParser::class);
            $this->pObj->content = $XHTML_clean->XHTML_clean($this->pObj->content);
            $GLOBALS['TT']->pull();
        }
    }

    /**
     * Returns the mode of XHTML cleaning
     *
     * @return string Keyword: "all", "cached", "none" or "output"
     */
    protected function doXHTML_cleaning()
    {
        if ($this->pObj->config['config']['xmlprologue'] === 'none') {
            return 'none';
        }
        return $this->pObj->config['config']['xhtml_cleaning'];
    }


    /**
     * Returns the mode of Local Anchor prefixing
     *
     * @return string Keyword: "all", "cached" or "output"
     */
    public function doLocalAnchorFix()
    {
        return isset($this->pObj->config['config']['prefixLocalAnchors']) ? $this->pObj->config['config']['prefixLocalAnchors'] : null;
    }

    /**
     * Substitutes all occurrences of <a href="#"... in $this->content with <a href="[path-to-url]#"...
     *
     * @return void Works directly on $this->content
     */
    protected function prefixLocalAnchorsWithScript()
    {
        if (!$this->pObj->beUserLogin) {
            if (!is_object($this->pObj->cObj)) {
                $this->pObj->newCObj();
            }
            $scriptPath = $this->pObj->cObj->getUrlToCurrentLocation();
        } else {
            // To break less existing sites, we allow the REQUEST_URI to be used for the prefix
            $scriptPath = GeneralUtility::getIndpEnv('REQUEST_URI');
            // Disable the cache so that these URI will not be the ones to be cached
            $this->pObj->no_cache = true;
        }
        $originalContent = $this->pObj->content;
        $this->pObj->content = preg_replace('/(<(?:a|area).*?href=")(#[^"]*")/i', '${1}' . htmlspecialchars($scriptPath) . '${2}', $originalContent);
        // There was an error in the call to preg_replace, so keep the original content (behavior prior to PHP 5.2)
        if (preg_last_error() > 0) {
            GeneralUtility::sysLog('preg_replace returned error-code: ' . preg_last_error() . ' in function prefixLocalAnchorsWithScript. Replacement not done!', 'cms', GeneralUtility::SYSLOG_SEVERITY_FATAL);
            $this->pObj->content = $originalContent;
        }
    }
}
