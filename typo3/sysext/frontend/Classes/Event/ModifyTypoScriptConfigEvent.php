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

namespace TYPO3\CMS\Frontend\Event;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;

/**
 * This event allows listeners to adjust and react on TypoScript 'config'.
 *
 * This event is dispatched *before* final TypoScript 'config' is written to cache, and
 * *not* when a page can be successfully retrieved from cache, which is typically
 * the case in 'page is fully cached' scenarios.
 *
 * This incoming $configTree has already been merged with the determined
 * PAGE "page.config" TypoScript of the requested 'type' / 'typeNum' and the global
 * TypoScript setup 'config'.
 *
 * The result of this event is available as Request attribute:
 * $request->getAttribute('frontend.typoscript')->getConfigTree(),
 * and its array variant $request->getAttribute('frontend.typoscript')->getConfigArray().
 *
 * Registered listener can *set* a modified setup config AST. Note the TypoScript AST
 * structure is still marked @internal within v13 core and may change later,
 * using the event to *write* different 'config' data is thus still a bit risky.
 */
final class ModifyTypoScriptConfigEvent
{
    public function __construct(
        private readonly ServerRequestInterface $request,
        private readonly RootNode $setupTree,
        private RootNode $configTree,
    ) {}

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getSetupTree(): RootNode
    {
        return $this->setupTree;
    }

    public function getConfigTree(): RootNode
    {
        return $this->configTree;
    }

    public function setConfigTree(RootNode $configTree): void
    {
        $this->configTree = $configTree;
    }
}
