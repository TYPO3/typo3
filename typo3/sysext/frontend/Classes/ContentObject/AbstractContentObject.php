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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Contains an abstract class for all tslib content class implementations.
 */
abstract class AbstractContentObject
{
    protected ?PageRenderer $pageRenderer = null;

    protected ?ServerRequestInterface $request = null;

    protected ?ContentObjectRenderer $cObj = null;

    /**
     * Renders the content object.
     *
     * @param array $conf
     * @return string
     */
    abstract public function render($conf = []);

    public function getContentObjectRenderer(): ContentObjectRenderer
    {
        return $this->cObj;
    }

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    public function setContentObjectRenderer(ContentObjectRenderer $cObj): void
    {
        $this->cObj = $cObj;
    }

    protected function getPageRenderer(): PageRenderer
    {
        if ($this->pageRenderer === null) {
            $this->pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        }

        return $this->pageRenderer;
    }
}
