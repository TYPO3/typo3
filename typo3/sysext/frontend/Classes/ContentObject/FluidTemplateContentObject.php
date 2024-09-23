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

namespace TYPO3\CMS\Frontend\ContentObject;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Mvc\Web\RequestBuilder;
use TYPO3\CMS\Fluid\View\FluidViewAdapter;
use TYPO3\CMS\Frontend\ContentObject\Exception\ContentRenderingException;

/**
 * Contains FLUIDTEMPLATE class object
 */
class FluidTemplateContentObject extends AbstractContentObject
{
    public function __construct(
        private readonly ContentDataProcessor $contentDataProcessor,
        private readonly TypoScriptService $typoScriptService,
        private readonly ViewFactoryInterface $viewFactory,
    ) {}

    /**
     * Rendering the cObject, FLUIDTEMPLATE
     *
     * Configuration properties:
     * - file string+stdWrap The FLUID template file
     * - layoutRootPaths array of filepath+stdWrap Root paths to layouts (fallback)
     * - partialRootPaths array of filepath+stdWrap Root paths to partials (fallback)
     * - variable array of cObjects, the keys are the variable names in fluid
     * - dataProcessing array of data processors which are classes to manipulate $data
     * - extbase.pluginName
     * - extbase.controllerExtensionName
     * - extbase.controllerName
     * - extbase.controllerActionName
     *
     * Example:
     * 10 = FLUIDTEMPLATE
     * 10.templateName = MyTemplate
     * 10.templateRootPaths.10 = EXT:site_configuration/Resources/Private/Templates/
     * 10.partialRootPaths.10 = EXT:site_configuration/Resources/Private/Partials/
     * 10.layoutRootPaths.10 = EXT:site_configuration/Resources/Private/Layouts/
     * 10.variables {
     *   mylabel = TEXT
     *   mylabel.value = Label from TypoScript coming
     * }
     *
     * @param array $conf Array of TypoScript properties
     */
    public function render($conf = []): string
    {
        if (!is_array($conf)) {
            $conf = [];
        }

        $request = $this->buildExtbaseRequestIfNeeded($this->request, $conf);
        $templateFilename = '';
        $templateSource = null;

        if ((!empty($conf['templateName']) || !empty($conf['templateName.']))
            && !empty($conf['templateRootPaths.']) && is_array($conf['templateRootPaths.'])
        ) {
            // This is the most preferred way to render fluid: set up paths, then call render('My/Template')
            $viewFactoryData = new ViewFactoryData(
                templateRootPaths: $this->applyStandardWrapToFluidPaths($conf['templateRootPaths.']),
                partialRootPaths: $this->getPartialRootPaths($conf),
                layoutRootPaths: $this->getLayoutRootPaths($conf),
                request: $request,
                format: $this->cObj->stdWrapValue('format', $conf, null),
            );
            $templateFilename = $this->cObj->stdWrapValue('templateName', $conf);
        } elseif (!empty($conf['template']) && !empty($conf['template.'])) {
            // Fetch the Fluid template by template cObject "template = TEXT, template.value = <f:foo ..."
            $templateSource = $this->cObj->cObjGetSingle($conf['template'], $conf['template.'], 'template');
            if ($templateSource === '') {
                throw new ContentRenderingException(
                    'Could not find template source for ' . $conf['template'],
                    1437420865
                );
            }
            $viewFactoryData = new ViewFactoryData(
                partialRootPaths: $this->getPartialRootPaths($conf),
                layoutRootPaths: $this->getLayoutRootPaths($conf),
                request: $request,
                format: $this->cObj->stdWrapValue('format', $conf, null),
            );
        } else {
            // Fetch the Fluid template by file stdWrap "file = EXT:myExt/.../Foo.html"
            $file = (string)$this->cObj->stdWrapValue('file', $conf);
            // Get the absolute file name
            $templatePathAndFilename = GeneralUtility::getFileAbsFileName($file);
            $viewFactoryData = new ViewFactoryData(
                partialRootPaths: $this->getPartialRootPaths($conf),
                layoutRootPaths: $this->getLayoutRootPaths($conf),
                templatePathAndFilename: $templatePathAndFilename,
                request: $request,
                format: $this->cObj->stdWrapValue('format', $conf, null),
            );
        }

        $view = $this->viewFactory->create($viewFactoryData);
        if (!$view instanceof FluidViewAdapter) {
            throw new ContentRenderingException(
                'The FLUIDTEMPLATE content object only works with FluidViewAdapter view. Use a different'
                . ' content object to render some other view',
                1724680477
            );
        }

        if ($templateSource) {
            $view->getRenderingContext()->getTemplatePaths()->setTemplateSource($templateSource);
        }

        if (isset($conf['settings.'])) {
            $settings = $this->typoScriptService->convertTypoScriptArrayToPlainArray($conf['settings.']);
            $view->assign('settings', $settings);
        }
        $variables = $this->getContentObjectVariables($conf);
        $variables = $this->contentDataProcessor->process($this->cObj, $conf, $variables);
        $view->assignMultiple($variables);

        $this->renderFluidTemplateAssetsIntoPageRenderer($view, $variables);

        $content = $view->render($templateFilename);
        if (isset($conf['stdWrap.'])) {
            return $this->cObj->stdWrap($content, $conf['stdWrap.']);
        }
        return $content;
    }

    protected function getLayoutRootPaths(array $conf): ?array
    {
        $layoutPaths = [];
        $layoutRootPath = (string)$this->cObj->stdWrapValue('layoutRootPath', $conf);
        if ($layoutRootPath !== '') {
            $layoutPaths[] = GeneralUtility::getFileAbsFileName($layoutRootPath);
        }
        if (isset($conf['layoutRootPaths.'])) {
            $layoutPaths = array_replace($layoutPaths, $this->applyStandardWrapToFluidPaths($conf['layoutRootPaths.']));
        }
        return !empty($layoutPaths) ? $layoutPaths : null;
    }

    protected function getPartialRootPaths(array $conf): ?array
    {
        $partialPaths = [];
        $partialRootPath = (string)$this->cObj->stdWrapValue('partialRootPath', $conf);
        if ($partialRootPath !== '') {
            $partialPaths[] = GeneralUtility::getFileAbsFileName($partialRootPath);
        }
        if (isset($conf['partialRootPaths.'])) {
            $partialPaths = array_replace($partialPaths, $this->applyStandardWrapToFluidPaths($conf['partialRootPaths.']));
        }
        return !empty($partialPaths) ? $partialPaths : null;
    }

    /**
     * @todo: This magic has to fall one way or the other. It has been introduced for ext:form to
     *        mimic extbase, see https://forge.typo3.org/issues/78842. This is actively used when
     *        rendering forms using the formvh:render strategy, see the documentation.
     */
    protected function buildExtbaseRequestIfNeeded(ServerRequestInterface $request, array $conf): ServerRequestInterface
    {
        $requestPluginName = (string)$this->cObj->stdWrapValue('pluginName', $conf['extbase.'] ?? []);
        $requestControllerExtensionName = (string)$this->cObj->stdWrapValue('controllerExtensionName', $conf['extbase.'] ?? []);
        $requestControllerName = (string)$this->cObj->stdWrapValue('controllerName', $conf['extbase.'] ?? []);
        $requestControllerActionName = (string)$this->cObj->stdWrapValue('controllerActionName', $conf['extbase.'] ?? []);
        if ($requestPluginName && $requestControllerExtensionName && $requestControllerName && $requestControllerActionName) {
            $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
            $configurationManager->setConfiguration([
                'extensionName' => $requestControllerExtensionName,
                'pluginName' => $requestPluginName,
            ]);
            if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$requestControllerExtensionName]['plugins'][$requestPluginName]['controllers'])) {
                $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$requestControllerExtensionName]['plugins'][$requestPluginName]['controllers'] = [
                    $requestControllerName => [
                        'actions' => [
                            $requestControllerActionName,
                        ],
                    ],
                ];
            }
            $requestBuilder = GeneralUtility::makeInstance(RequestBuilder::class);
            $request = $requestBuilder->build($request);
        }
        return $request;
    }

    /**
     * Attempts to render HeaderAssets and FooterAssets sections from the
     * Fluid template, then adds each (if not empty) to either header or
     * footer, as appropriate, using PageRenderer.
     */
    protected function renderFluidTemplateAssetsIntoPageRenderer(FluidViewAdapter $view, array $variables): void
    {
        $pageRenderer = $this->getPageRenderer();
        $headerAssets = $view->renderSection('HeaderAssets', [...$variables, 'contentObject' => $this], true);
        $footerAssets = $view->renderSection('FooterAssets', [...$variables, 'contentObject' => $this], true);
        if (!empty(trim($headerAssets))) {
            $pageRenderer->addHeaderData($headerAssets);
        }
        if (!empty(trim($footerAssets))) {
            $pageRenderer->addFooterData($footerAssets);
        }
    }

    /**
     * Compile rendered content objects in variables array ready to assign to the view.
     */
    protected function getContentObjectVariables(array $conf): array
    {
        $variables = [];
        $reservedVariables = ['data', 'current'];
        // Accumulate the variables to be process and loop them through cObjGetSingle
        $variablesToProcess = (array)($conf['variables.'] ?? []);
        foreach ($variablesToProcess as $variableName => $cObjType) {
            if (is_array($cObjType)) {
                continue;
            }
            if (!in_array($variableName, $reservedVariables)) {
                $cObjConf = $variablesToProcess[$variableName . '.'] ?? [];
                $variables[$variableName] = $this->cObj->cObjGetSingle($cObjType, $cObjConf, 'variables.' . $variableName);
            } else {
                throw new \InvalidArgumentException(
                    'Cannot use reserved name "' . $variableName . '" as variable name in FLUIDTEMPLATE.',
                    1288095720
                );
            }
        }
        $variables['data'] = $this->cObj->data;
        $variables['current'] = $this->cObj->data[$this->cObj->currentValKey] ?? null;
        return $variables;
    }

    protected function applyStandardWrapToFluidPaths(array $paths): array
    {
        $finalPaths = [];
        foreach ($paths as $key => $path) {
            if (str_ends_with((string)$key, '.')) {
                if (isset($paths[substr($key, 0, -1)])) {
                    continue;
                }
                $path = $this->cObj->stdWrap('', $path);
            } elseif (isset($paths[$key . '.'])) {
                $path = $this->cObj->stdWrap($path, $paths[$key . '.']);
            }
            $finalPaths[$key] = GeneralUtility::getFileAbsFileName($path);
        }
        return $finalPaths;
    }
}
