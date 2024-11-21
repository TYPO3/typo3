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
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Frontend\ContentObject\Exception\ContentRenderingException;

/**
 * PAGEVIEW Content Object.
 *
 * Built to render a full page with Fluid, and does the following
 * - uses the template from the given Page Layout / Backend Layout of the current page in a folder "Pages/Mylayout.html"
 * - paths are resolved from "paths." configuration
 * - automatically adds templateRootPaths to the layoutRootPaths and partialRootPaths
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
final class PageViewContentObject extends AbstractContentObject
{
    private const reservedVariables = ['site', 'language', 'page'];

    public function __construct(
        private readonly ContentDataProcessor $contentDataProcessor,
        private readonly TypoScriptService $typoScriptService,
        private readonly PageLayoutResolver $pageLayoutResolver,
        private readonly ViewFactoryInterface $viewFactory,
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
     *   page.10 = PAGEVIEW
     *   page.10.paths.10 = EXT:site_configuration/Resources/Private/Templates/
     *   page.10.variables {
     *     mylabel = TEXT
     *     mylabel.value = Label from TypoScript
     *   }
     *
     * @param array $conf Array of TypoScript properties
     * @return string The HTML output
     * @throws ContentRenderingException
     */
    public function render($conf = []): string
    {
        if (!is_array($conf)) {
            $conf = [];
        }
        if (!is_array($conf['paths.'] ?? false) || $conf['paths.'] === []) {
            throw new ContentRenderingException(
                'PAGEVIEW content object needs a "paths." TypoScript array',
                1724601907
            );
        }
        $paths = array_map(PathUtility::sanitizeTrailingSeparator(...), $conf['paths.']);
        $viewFactoryData = new ViewFactoryData(
            // @todo: Do discuss: Rename 'paths.' to 'templateRootPaths.' again?
            templateRootPaths: $paths,
            // @todo: We should *still* allow setting both partialRootPaths and layoutRootPaths, and only fall back to
            //        [templateRootPaths]/Partials and [templateRootPaths]/Layouts if not set. And the fallback should be
            //        advertised as best practice.
            partialRootPaths: array_map(static fn(string $path): string => $path . 'Partials/', $paths),
            layoutRootPaths: array_map(static fn(string $path): string => $path . 'Layouts/', $paths),
            request: $this->request,
        );
        $view = $this->viewFactory->create($viewFactoryData);

        $pageSettings = $this->request->getAttribute('frontend.typoscript')->getSettingsTree()->toArray();
        $view->assign('settings', $this->typoScriptService->convertTypoScriptArrayToPlainArray($pageSettings));
        $variables = $this->getContentObjectVariables($conf);
        $variables = $this->contentDataProcessor->process($this->cObj, $conf, $variables);
        $view->assignMultiple($variables);

        // Fetch the Fluid template by the name of the Page Layout and underneath "Pages"
        $pageInformationObject = $this->request->getAttribute('frontend.page.information');
        $pageLayoutName = $this->pageLayoutResolver->getLayoutIdentifierForPageWithoutPrefix(
            $pageInformationObject->getPageRecord(),
            $pageInformationObject->getRootLine()
        );

        return $view->render('Pages/' . ucfirst($pageLayoutName));
    }

    /**
     * Compile rendered content objects in variables array ready to assign to the view
     *
     * @param array $conf Configuration array
     * @return array the variables to be assigned
     */
    private function getContentObjectVariables(array $conf): array
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
                if (in_array($variableName, self::reservedVariables, true)) {
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
}
