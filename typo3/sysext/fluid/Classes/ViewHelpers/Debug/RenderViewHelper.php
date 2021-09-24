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

namespace TYPO3\CMS\Fluid\ViewHelpers\Debug;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Debuggable version of :ref:`f:render <typo3-fluid-render>` - performs the
 * same rendering operation but wraps the output with HTML that can be
 * inspected with the admin panel in frontend.
 *
 * Replaces ``f:render`` when the admin panel decides (see
 * :php:`ViewHelperResolver` class). Also possible to use explicitly by using
 * ``f:debug.render`` instead of the normal ``f:render`` statement.
 */
class RenderViewHelper extends AbstractViewHelper
{

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initializes additional arguments available for this ViewHelper.
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('debug', 'boolean', 'If true, the admin panel shows debug information if activated,', false, true);
        $this->registerArgument('section', 'string', 'Section to render - combine with partial to render section in partial', false, null);
        $this->registerArgument('partial', 'string', 'Partial to render, with or without section', false, null);
        $this->registerArgument('arguments', 'array', 'Array of variables to be transferred. Use {_all} for all variables', false, []);
        $this->registerArgument('optional', 'boolean', 'If TRUE, considers the *section* optional. Partial never is.', false, false);
        $this->registerArgument('default', 'mixed', 'Value (usually string) to be displayed if the section or partial does not exist', false, null);
        $this->registerArgument('contentAs', 'string', 'If used, renders the child content and adds it as a template variable with this name for use in the partial/section', false, null);
    }

    /**
     * Renders the content.
     *
     * @return string
     */
    public function render()
    {
        $isDebug = $this->arguments['debug'];
        $section = $this->arguments['section'];
        $partial = $this->arguments['partial'];
        $arguments = (array)$this->arguments['arguments'];
        $optional = (boolean)$this->arguments['optional'];
        $contentAs = $this->arguments['contentAs'];
        $tagContent = $this->renderChildren();

        if ($contentAs !== null) {
            $arguments[$contentAs] = $tagContent;
        }

        $content = '';
        $viewHelperVariableContainer = $this->renderingContext->getViewHelperVariableContainer();
        if ($partial !== null) {
            $content = $viewHelperVariableContainer->getView()->renderPartial($partial, $section, $arguments, $optional);
        } elseif ($section !== null) {
            $content = $viewHelperVariableContainer->getView()->renderSection($section, $arguments, $optional);
        }
        // Replace empty content with default value. If default is
        // not set, NULL is returned and cast to a new, empty string
        // outside of this ViewHelper.
        if ($content === '') {
            $content = $this->arguments['default'] ?? $tagContent;
        }

        // if debug is disabled, return content
        if (!$isDebug) {
            return $content;
        }

        $cssRules = [];
        $cssRules[] = 'display: block';
        $cssRules[] = 'background-color: #fff';
        $cssRules[] = 'padding: 5px';
        $cssRules[] = 'border: 1px solid #f00';
        $cssRules[] = 'color: #000';
        $cssRules[] = 'overflow: hidden';
        $cssWrapper = implode(';', $cssRules);
        $cssRules[] = 'font-size: 11px';
        $cssRules[] = 'font-family: Monospace';
        $cssTitle = implode(';', $cssRules);

        $debugInfo = [];
        if (isset($this->arguments['partial'])) {
            $path = $this->renderingContext->getTemplatePaths()->getPartialPathAndFilename($partial);
            $path = str_replace(
                [
                    Environment::getBackendPath() . '/ext/',
                    Environment::getExtensionsPath() . '/',
                    Environment::getFrameworkBasePath() . '/',
                ],
                'EXT:',
                $path
            );
            $path = PathUtility::stripPathSitePrefix($path);
            $debugInfo['Partial'] = 'Partial: ' . $path;
        }
        if (isset($this->arguments['section'])) {
            $debugInfo['Section'] = 'Section: ' . htmlspecialchars($section);
        }

        $debugContent = sprintf(
            '<strong>%s</strong>',
            implode('<br />', $debugInfo)
        );

        return sprintf(
            '<div class="t3js-debug-template" title="%s" style="%s"><span style="%s">%s</span>%s</div>',
            htmlspecialchars(implode('/', array_keys($debugInfo))),
            $cssTitle,
            $cssWrapper,
            $debugContent,
            $content
        );
    }
}
