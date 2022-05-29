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

namespace TYPO3\CMS\Fluid\View;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException;

/**
 * A standalone template view.
 * Should be used as view if you want to use Fluid without Extbase extensions
 */
class StandaloneView extends AbstractTemplateView
{
    /**
     * Constructor
     *
     * @param ContentObjectRenderer|null $contentObject @deprecated The current cObject. If NULL a new instance will be created
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    public function __construct(ContentObjectRenderer $contentObject = null)
    {
        // @deprecated since v11, will be removed with v12. Drop $contentObject argument and ConfigurationManager handling.
        $configurationManager = GeneralUtility::getContainer()->get(ConfigurationManager::class);
        if ($contentObject !== null) {
            trigger_error('Argument $contentObject of class ' . __CLASS__ . ' is deprecated since v11, will be removed with v12.', E_USER_DEPRECATED);
        } else {
            $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        }
        $configurationManager->setContentObject($contentObject);

        $request = GeneralUtility::makeInstance(Request::class);
        $renderingContext = GeneralUtility::makeInstance(RenderingContextFactory::class)->create();
        $renderingContext->setRequest($request);
        parent::__construct($renderingContext);
    }

    /**
     * Sets the format of the current request (default format is "html")
     *
     * @param string $format
     * @throws \RuntimeException
     */
    public function setFormat($format)
    {
        if ($this->baseRenderingContext instanceof RenderingContext) {
            $this->baseRenderingContext->getRequest()->setFormat($format);
            $this->baseRenderingContext->getTemplatePaths()->setFormat($format);
        } else {
            throw new \RuntimeException('The rendering context must be of type ' . RenderingContext::class, 1482251886);
        }
    }

    /**
     * Returns the format of the current request (defaults is "html")
     *
     * @return string $format
     * @throws \RuntimeException
     */
    public function getFormat()
    {
        if ($this->baseRenderingContext instanceof RenderingContext) {
            return $this->baseRenderingContext->getRequest()->getFormat();
        }
        throw new \RuntimeException('The rendering context must be of type ' . RenderingContext::class, 1482251887);
    }

    /**
     * Returns the current request object
     *
     * @return \TYPO3\CMS\Extbase\Mvc\Request
     * @throws \RuntimeException
     * @internal
     */
    public function getRequest()
    {
        if ($this->baseRenderingContext instanceof RenderingContext) {
            return $this->baseRenderingContext->getRequest();
        }
        throw new \RuntimeException('The rendering context must be of type ' . RenderingContext::class, 1482251888);
    }

    /**
     * Returns the absolute path to a Fluid template file if it was specified with setTemplatePathAndFilename() before.
     * If the template filename was never specified, Fluid attempts to resolve the file based on controller and action.
     *
     * NB: If TemplatePaths was previously told to use the specific template path and filename it will short-circuit
     * and return that template path and filename directly, instead of attempting to resolve it.
     *
     * @return string Fluid template path
     */
    public function getTemplatePathAndFilename()
    {
        $templatePaths = $this->baseRenderingContext->getTemplatePaths();
        return $templatePaths->resolveTemplateFileForControllerAndActionAndFormat(
            $this->baseRenderingContext->getControllerName(),
            $this->baseRenderingContext->getControllerAction(),
            $templatePaths->getFormat()
        );
    }

    /**
     * Sets the Fluid template source
     * You can use setTemplatePathAndFilename() alternatively if you only want to specify the template path
     *
     * @param string $templateSource Fluid template source code
     */
    public function setTemplateSource($templateSource)
    {
        $this->baseRenderingContext->getTemplatePaths()->setTemplateSource($templateSource);
    }

    /**
     * Checks whether a template can be resolved for the current request
     *
     * @return bool
     */
    public function hasTemplate()
    {
        try {
            $this->baseRenderingContext->getTemplatePaths()->getTemplateSource(
                $this->baseRenderingContext->getControllerName(),
                $this->baseRenderingContext->getControllerAction()
            );
            return true;
        } catch (InvalidTemplateResourceException $e) {
            return false;
        }
    }
}
