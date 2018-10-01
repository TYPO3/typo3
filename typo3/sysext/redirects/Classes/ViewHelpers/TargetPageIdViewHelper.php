<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Redirects\ViewHelpers;

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

use TYPO3\CMS\Core\LinkHandling\Exception\UnknownUrnException;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * The target of a redirect can contain a t3://page link.
 * This ViewHelper checks for such a case and returns the Page ID
 * @internal
 */
class TargetPageIdViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Initializes the arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('target', 'string', 'The target of the redirect.', true);
    }

    /**
     * Renders the page ID
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): string
    {
        if (!strpos($arguments['target'], 't3://page', 0) === 0) {
            return '';
        }

        try {
            $linkService = GeneralUtility::makeInstance(LinkService::class);
            $resolvedLink = $linkService->resolveByStringRepresentation($arguments['target']);
            return $resolvedLink['pageuid'] ?? '';
        } catch (UnknownUrnException $e) {
            return '';
        }
    }
}
