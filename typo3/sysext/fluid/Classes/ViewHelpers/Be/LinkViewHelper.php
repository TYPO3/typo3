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

namespace TYPO3\CMS\Fluid\ViewHelpers\Be;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * ViewHelper for creating URIs to backend modules.
 *
 * ```
 *   <f:be.link route="web_ts" parameters="{id: 92}">Go to web_ts</f:be.link>
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-be-link
 */
final class LinkViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'a';

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('route', 'string', 'The name of the route', true);
        $this->registerArgument('parameters', 'array', 'An array of parameters', false, []);
        $this->registerArgument('referenceType', 'string', 'The type of reference to be generated (one of the constants)', false, UriBuilder::ABSOLUTE_PATH);
    }

    public function render(): string
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $route = $this->arguments['route'];
        $parameters = $this->arguments['parameters'];
        $referenceType = $this->arguments['referenceType'];
        $uri = $uriBuilder->buildUriFromRoute($route, $parameters, $referenceType);
        $this->tag->addAttribute('href', (string)$uri);
        $this->tag->setContent((string)$this->renderChildren());
        $this->tag->forceClosingTag(true);
        return $this->tag->render();
    }
}
