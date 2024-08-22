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

namespace TYPO3\CMS\Core\View;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3Fluid\Fluid\View\TemplateView;

/**
 * Create a view based on fluid.
 *
 * Return an instance of ext:core FluidViewAdapter that implements ext:core ViewInterface.
 *
 * This is the default implementation when a class asks for a ViewFactoryInterface injection.
 */
#[AsAlias(ViewFactoryInterface::class, public: true)]
final readonly class FluidViewFactory implements ViewFactoryInterface
{
    public function __construct(
        private RenderingContextFactory $renderingContextFactory,
    ) {}

    public function create(ViewFactoryData $data): ViewInterface
    {
        $pathTuple = [];
        if (!empty($data->templateRootPaths)) {
            $pathTuple['templateRootPaths'] = $data->templateRootPaths;
        }
        if (!empty($data->layoutRootPaths)) {
            $pathTuple['layoutRootPaths'] = $data->layoutRootPaths;
        }
        if (!empty($data->partialRootPaths)) {
            $pathTuple['partialRootPaths'] = $data->partialRootPaths;
        }
        $renderingContext = $this->renderingContextFactory->create($pathTuple, $data->request);
        if ($data->templatePathAndFilename) {
            $renderingContext->getTemplatePaths()->setTemplatePathAndFilename($data->templatePathAndFilename);
        }
        if ($data->format) {
            // @todo: We may want to hand this over to RenderingContextFactory
            //        and set up TemplatePaths() with the format correctly already?
            $renderingContext->getTemplatePaths()->setFormat($data->format);
        }
        $view = new TemplateView($renderingContext);
        return new FluidViewAdapter($view);
    }
}
