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

namespace TYPO3\CMS\FluidStyledContent\ViewHelpers\Link;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper for creating a link for an image popup.
 *
 * ```
 *   <ce:link.clickEnlarge image="{image}" configuration="{settings.images.popup}"><img src=""></ce:link.clickEnlarge>
 * ```
 *
 * @internal this is not part of TYPO3 Core API.
 */
final class ClickEnlargeViewHelper extends AbstractViewHelper
{
    /**
     * @var bool
     */
    protected $escapeOutput = false;

    public function __construct(
        private readonly TypoScriptService $typoScriptService,
    ) {}

    public function initializeArguments(): void
    {
        $this->registerArgument('image', FileInterface::class, 'The original image file', true);
        $this->registerArgument(
            'configuration',
            'array',
            'TypoScript properties for the "imageLinkWrap" function',
            true
        );
    }

    public function render(): string
    {
        /** @var FileInterface $image */
        $image = $this->arguments['image'];
        $request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($request);
        $contentObjectRenderer->start($request->getAttribute('frontend.page.information')->getPageRecord(), 'pages');
        $contentObjectRenderer->setCurrentFile($image);
        $objDataBackup = null;
        if ($this->renderingContext->getVariableProvider()->exists('data')) {
            $objDataBackup = $contentObjectRenderer->data;
            $contentObjectRenderer->data = $this->renderingContext->getVariableProvider()->get('data');
        }
        $configuration = $this->typoScriptService->convertPlainArrayToTypoScriptArray($this->arguments['configuration']);
        $content = $this->renderChildren();
        $configuration['enable'] = true;
        $result = $contentObjectRenderer->imageLinkWrap((string)$content, $image, $configuration);
        if ($objDataBackup) {
            $contentObjectRenderer->data = $objDataBackup;
        }
        return $result;
    }
}
