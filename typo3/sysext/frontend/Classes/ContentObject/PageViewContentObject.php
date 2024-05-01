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

namespace TYPO3\CMS\Frontend\ContentObject;

use TYPO3\CMS\Core\Page\PageLayoutResolver;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\ContentObject\Exception\ContentRenderingException;

/**
 * PAGEVIEW Content Object.
 *
 * Built to render a full page with Fluid, and does the following
 * - uses the template from the given Page Layout / Backend Layout of the current page in a folder "pages/mylayout.html"
 * - paths are resolved from "paths." configuration
 * - automatically adds templateRootPaths to the layoutRootPaths and partialRootPaths as well with a suffix "layouts/" and "partials/"
 * - injects pageInformation, site and siteLanguage (= language) as variables by default
 * - adds all page settings (= TypoScript constants) into the settings variable of the View
 *
 * In contrast to FLUIDTEMPLATE, by design this cObject
 * - does not handle custom layoutRootPaths and partialRootPaths
 * - does not handle Extbase specialities
 * - does not handle HeaderAssets and FooterAssets
 * - does not handle "templateName.", "template." and "file." resolving from cObject
 *
 * @internal this cObject is considered experimental until TYPO3 v13 LTS
 */
class PageViewContentObject extends AbstractContentObject
{
    protected array $reservedVariables = ['site', 'language', 'page'];

    public function __construct(
        protected readonly ContentDataProcessor $contentDataProcessor,
        protected readonly StandaloneView $view,
        protected readonly TypoScriptService $typoScriptService,
        protected readonly PageLayoutResolver $pageLayoutResolver,
    ) {}

    /**
     * Rendering the cObject, PAGEVIEW
     *
     * Configuration properties:
     *  - paths array to template files
     *  - variables array of cObjects, the keys are the variable names in fluid
     *  - dataProcessing array of data processors which are classes to manipulate $data
     *
     * Example:
     * page.10 = PAGEVIEW
     * page.10.paths.10 = EXT:site_configuration/Resources/Private/Templates/
     * page.10.variables {
     *   mylabel = TEXT
     *   mylabel.value = Label from TypoScript
     * }
     *
     * @param array $conf Array of TypoScript properties
     * @return string The HTML output
     */
    public function render($conf = []): string
    {
        if (!is_array($conf)) {
            $conf = [];
        }
        $this->view->setRequest($this->request);

        $this->setTemplate($conf);
        $this->assignSettings();
        $variables = $this->getContentObjectVariables($conf);
        $variables = $this->contentDataProcessor->process($this->cObj, $conf, $variables);

        $this->view->assignMultiple($variables);

        return (string)$this->view->render();
    }

    protected function setTemplate(array $conf): void
    {
        if (is_array($conf['paths.'] ?? false) && $conf['paths.'] !== []) {
            $this->view->setTemplateRootPaths($conf['paths.']);
            $this->setLayoutPaths();
            $this->setPartialPaths();
        }
        // Fetch the Fluid template by the name of the Page Layout and underneath "Pages"
        $pageInformationObject = $this->request->getAttribute('frontend.page.information');
        $pageLayoutName = $this->pageLayoutResolver->getLayoutIdentifierForPageWithoutPrefix(
            $pageInformationObject->getPageRecord(),
            $pageInformationObject->getRootLine()
        );

        $this->view->getRenderingContext()->setControllerAction($pageLayoutName);
        $this->view->getRenderingContext()->setControllerName('pages');
        // Also allow an upper case folder as fallback
        if (!$this->view->hasTemplate()) {
            $this->view->getRenderingContext()->setControllerName('Pages');
        }
        // If template still does not exist, rendering is not possible.
        if (!$this->view->hasTemplate()) {
            $configuredTemplateRootPaths = implode(', ', $this->view->getTemplateRootPaths());
            throw new ContentRenderingException(
                sprintf(
                    'Could not find template source file "pages/%1$s.html" or "Pages/%1$s.html" in lookup paths: %2$s',
                    ucfirst($pageLayoutName),
                    $configuredTemplateRootPaths
                ),
                1711797936
            );
        }
    }

    /**
     * Set layout root paths from the template paths
     */
    protected function setLayoutPaths(): void
    {
        // Define the default root paths to be located in the base paths under "layouts/" subfolder
        // Handle unix paths to allow upper-case folders as well
        $templateRootPathsLowerCase = array_map(static fn(string $path): string => $path . 'layouts/', $this->view->getTemplateRootPaths());
        $templateRootPathsUpperCase = array_map(static fn(string $path): string => $path . 'Layouts/', $this->view->getTemplateRootPaths());
        $layoutPaths = array_merge($templateRootPathsUpperCase, $templateRootPathsLowerCase);
        if ($layoutPaths !== []) {
            $this->view->setLayoutRootPaths($layoutPaths);
        }
    }

    /**
     * Set partial root path from the template root paths
     */
    protected function setPartialPaths(): void
    {
        // Define the default root paths to be located in the base paths under "partials/" subfolder
        // Handle unix paths to allow upper-case folders as well
        $templateRootPathsLowerCase = array_map(static fn(string $path): string => $path . 'partials/', $this->view->getTemplateRootPaths());
        $templateRootPathsUpperCase = array_map(static fn(string $path): string => $path . 'Partials/', $this->view->getTemplateRootPaths());
        $partialPaths = array_merge($templateRootPathsUpperCase, $templateRootPathsLowerCase);
        if ($partialPaths !== []) {
            $this->view->setPartialRootPaths($partialPaths);
        }
    }

    /**
     * Compile rendered content objects in variables array ready to assign to the view
     *
     * @param array $conf Configuration array
     * @return array the variables to be assigned
     * @throws \InvalidArgumentException
     */
    protected function getContentObjectVariables(array $conf): array
    {
        $variables = [
            'site' => $this->request->getAttribute('site'),
            'language' => $this->request->getAttribute('language'),
            'page' => $this->request->getAttribute('frontend.page.information'),
        ];
        // Accumulate the variables to be process and loop them through cObjGetSingle
        if (is_array($conf['variables.'] ?? false) && $conf['variables.'] !== []) {
            foreach ($conf['variables.'] as $variableName => $cObjType) {
                if (!is_string($cObjType)) {
                    continue;
                }
                if (in_array($variableName, $this->reservedVariables, true)) {
                    throw new \InvalidArgumentException(
                        'Cannot use reserved name "' . $variableName . '" as variable name in PAGEVIEW.',
                        1711748615
                    );
                }
                $cObjConf = $conf['variables.'][$variableName . '.'] ?? [];
                $variables[$variableName] = $this->cObj->cObjGetSingle($cObjType, $cObjConf, 'variables.' . $variableName);
            }
        }
        return $variables;
    }

    /**
     * Set any TypoScript settings to the view, which take precedence over the page-specific settings.
     */
    protected function assignSettings(): void
    {
        $pageSettings = $this->request->getAttribute('frontend.typoscript')->getSettingsTree()->toArray();
        $pageSettings = $this->typoScriptService->convertTypoScriptArrayToPlainArray($pageSettings);
        $this->view->assign('settings', $pageSettings);
    }
}
