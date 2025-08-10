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
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to create internal links within the backend.
 *
 * ```
 *  <form action="{be:moduleLink(route:'pages_new', arguments:'{id:pageUid}')}" method="post">
 *     <!-- form content -->
 *  </form>
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-backend-modulelink
 */
final class ModuleLinkViewHelper extends AbstractViewHelper
{
    public function __construct(
        private readonly UriBuilder $uriBuilder
    ) {}

    public function initializeArguments(): void
    {
        $this->registerArgument('route', 'string', 'The route to link to', true);
        $this->registerArgument('arguments', 'array', 'Additional link arguments (e.g. id or returnUrl)', false, []);
        $this->registerArgument('query', 'string', 'Additional link arguments as string  (e.g. id or returnUrl)');
        $this->registerArgument('currentUrlParameterName', 'string', 'Add current url as given parameter');
    }

    /**
     * Render module link with arguments
     */
    public function render(): string
    {
        $parameters = $this->arguments['arguments'];
        if ($this->arguments['query'] !== null) {
            ArrayUtility::mergeRecursiveWithOverrule($parameters, GeneralUtility::explodeUrl2Array($this->arguments['query']));
        }
        if (!empty($this->arguments['currentUrlParameterName'])
            && empty($this->arguments['arguments'][$this->arguments['currentUrlParameterName']])
            && $this->renderingContext->hasAttribute(ServerRequestInterface::class)
        ) {
            // If currentUrlParameterName is given and if that argument is not hand over yet, and if there is a request, fetch it from request
            // @todo: We may want to deprecate fetching stuff from request and advise handing over a proper value as 'arguments' argument.
            $request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
            $parameters[$this->arguments['currentUrlParameterName']] = $request->getAttribute('normalizedParams')->getRequestUri();
        }
        return (string)$this->uriBuilder->buildUriFromRoute($this->arguments['route'], $parameters);
    }
}
