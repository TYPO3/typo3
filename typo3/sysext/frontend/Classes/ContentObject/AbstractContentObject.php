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
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\Exception\ContentRenderingException;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Contains an abstract class for all tslib content class implementations.
 */
abstract class AbstractContentObject
{
    protected ?PageRenderer $pageRenderer = null;

    /**
     * Always set via setRequest() by ContentObjectFactory after instantiation
     */
    protected ServerRequestInterface $request;

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
        return $this->cObj ?? $this->getTypoScriptFrontendController()->cObj;
    }

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    public function setContentObjectRenderer(ContentObjectRenderer $cObj): void
    {
        $this->cObj = $cObj;
        // Provide the ContentObjectRenderer to the request as well, for code
        // that only passes the request to more underlying layers, like Extbase does.
        // Also makes sure the request in a Fluid RenderingContext also has the current
        // content object available.
        $this->request = $this->request->withAttribute('currentContentObject', $cObj);
    }

    protected function hasTypoScriptFrontendController(): bool
    {
        return $this->cObj?->getTypoScriptFrontendController() instanceof TypoScriptFrontendController;
    }

    /**
     * @throws ContentRenderingException
     */
    protected function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        if (!$this->hasTypoScriptFrontendController()) {
            throw new ContentRenderingException('TypoScriptFrontendController is not available.', 1655723512);
        }

        return $this->cObj->getTypoScriptFrontendController();
    }

    protected function getPageRepository(): PageRepository
    {
        if (!$this->hasTypoScriptFrontendController()) {
            return GeneralUtility::makeInstance(PageRepository::class);
        }
        /** do not lose the used {@link \TYPO3\CMS\Core\Context\Context} of TSFE, if it is currently not fully initialized */
        if (!$this->getTypoScriptFrontendController()->sys_page instanceof PageRepository) {
            return GeneralUtility::makeInstance(
                PageRepository::class,
                $this->getTypoScriptFrontendController()->getContext()
            );
        }
        return $this->getTypoScriptFrontendController()->sys_page;
    }

    protected function getPageRenderer(): PageRenderer
    {
        if ($this->pageRenderer === null) {
            $this->pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        }

        return $this->pageRenderer;
    }
}
