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

namespace TYPO3\CMS\Adminpanel\ViewHelpers\Fluid;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

use TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException;

/**
 * Overrides the default f:render implementation by EXT:fluid via Fluid's
 * namespaces merging to display debugging information for rendered templates,
 * partials, layouts and sections.
 *
 * @internal
 */
final class RenderViewHelper extends \TYPO3Fluid\Fluid\ViewHelpers\RenderViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('debug', 'boolean', 'If true, the admin panel shows debug information if activated,', false, true);
    }

    public function render(): mixed
    {
        // Output debugging information if feature in admin panel is active
        if ($this->renderingContext->hasAttribute(ServerRequestInterface::class)
            && ApplicationType::fromRequest($this->renderingContext->getAttribute(ServerRequestInterface::class))->isFrontend()
            && $this->getBackendUser() instanceof BackendUserAuthentication
            && ($this->getAdmPanelUserTsConfiguration()['override.']['preview.']['showFluidDebug'] ?? $this->getBackendUser()->uc['AdminPanel']['preview_showFluidDebug'] ?? false)
        ) {
            return $this->renderWithDebugInformation();
        }
        return parent::render();
    }

    private function renderWithDebugInformation(): string
    {
        $isDebug = $this->arguments['debug'];
        $section = $this->arguments['section'];
        $partial = $this->arguments['partial'];
        $arguments = (array)$this->arguments['arguments'];
        $optional = (bool)$this->arguments['optional'];
        $contentAs = $this->arguments['contentAs'];
        $tagContent = $this->renderChildren();

        if ($contentAs !== null) {
            $arguments[$contentAs] = $tagContent;
        }

        $content = '';
        $view = $this->renderingContext->getViewHelperVariableContainer()->getView();
        if (!$view) {
            throw new Exception(
                'The f:render ViewHelper was used in a context where the ViewHelperVariableContainer does not contain '
                . 'a reference to the View. Normally this is taken care of by the TemplateView, so most likely this '
                . 'error is because you overrode AbstractTemplateView->setRenderingContext() and did not call '
                . '$renderingContext->getViewHelperVariableContainer()->setView($this). '
                . 'This is an issue you must fix in your code as f:render is fully unable to render anything without a View.',
                1768299479,
            );
        }
        if ($partial !== null) {
            $content = $view->renderPartial($partial, $section, $arguments, $optional);
        } elseif ($section !== null) {
            $content = $view->renderSection($section, $arguments, $optional);
        }
        // Replace empty content with default value. If default is
        // not set, NULL is returned and cast to a new, empty string
        // outside this ViewHelper.
        if ($content === '') {
            $content = $this->arguments['default'] ?? (string)$tagContent;
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
            try {
                $path = $this->renderingContext->getTemplatePaths()->getPartialPathAndFilename($partial);
                $path = str_replace(
                    [
                        Environment::getExtensionsPath() . '/',
                        Environment::getFrameworkBasePath() . '/',
                    ],
                    'EXT:',
                    $path,
                );
                if (str_starts_with($path, Environment::getPublicPath())) {
                    $path = substr($path, strlen(Environment::getPublicPath() . '/'));
                }
                $debugInfo['Partial'] = 'Partial: ' . $path;
            } catch (InvalidTemplateResourceException) {
                $debugInfo['Partial'] = 'Partial not found: ' . $partial;
            }
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

    private function getAdmPanelUserTsConfiguration(): array
    {
        return $this->getBackendUser()?->getTSConfig()['admPanel.'] ?? [];
    }

    private function getBackendUser(): ?BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'] ?? null;
    }
}
