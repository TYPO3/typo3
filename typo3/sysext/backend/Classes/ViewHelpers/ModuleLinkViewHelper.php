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

namespace TYPO3\CMS\Backend\ViewHelpers;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Create internal link within backend.
 *
 * Examples
 * ========
 *
 * Default::
 *
 *     <form action="{be:moduleLink(route:'pages_new', arguments:'{id:pageUid}')}" method="post">
 *         <!-- form content -->
 *     </form>
 *
 * Output::
 *
 *     <form action="/pages/new" method="post">
 *         <!-- form content -->
 *     </form>
 */
final class ModuleLinkViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    public function initializeArguments(): void
    {
        $this->registerArgument('route', 'string', 'The route to link to', true);
        $this->registerArgument('arguments', 'array', 'Additional link arguments', false, []);
        $this->registerArgument('query', 'string', 'Additional link arguments as string');
        $this->registerArgument('currentUrlParameterName', 'string', 'Add current url as given parameter');
    }

    /**
     * Render module link with arguments
     *
     * @param array<string, mixed> $arguments
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): string
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $parameters = $arguments['arguments'];
        if ($arguments['query'] !== null) {
            ArrayUtility::mergeRecursiveWithOverrule($parameters, GeneralUtility::explodeUrl2Array($arguments['query']));
        }
        /** @var RenderingContext $renderingContext */
        $request = $renderingContext->getRequest();
        if (!empty($arguments['currentUrlParameterName'])
            && empty($arguments['arguments'][$arguments['currentUrlParameterName']])
            && $request instanceof ServerRequestInterface
        ) {
            // If currentUrlParameterName is given and if that argument is not hand over yet, and if there is a request, fetch it from request
            // @todo: We may want to deprecate fetching stuff from request and advise handing over a proper value as 'arguments' argument.
            $parameters[$arguments['currentUrlParameterName']] = $request->getAttribute('normalizedParams')->getRequestUri();
        }
        return (string)$uriBuilder->buildUriFromRoute($arguments['route'], $parameters);
    }
}
