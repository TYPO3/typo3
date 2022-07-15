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
    /**
     * @var ContentObjectRenderer
     */
    protected $cObj;

    /**
     * @var PageRenderer
     */
    protected $pageRenderer;

    protected ?ServerRequestInterface $request = null;

    /**
     * Default constructor.
     *
     * @param ContentObjectRenderer $cObj
     */
    public function __construct(ContentObjectRenderer $cObj)
    {
        $this->cObj = $cObj;
    }

    /**
     * Renders the content object.
     *
     * @param array $conf
     * @return string
     */
    abstract public function render($conf = []);

    /**
     * Getter for current ContentObjectRenderer
     *
     * @return ContentObjectRenderer
     */
    public function getContentObjectRenderer()
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

    protected function hasTypoScriptFrontendController(): bool
    {
        return $this->cObj->getTypoScriptFrontendController() instanceof TypoScriptFrontendController;
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        if (!$this->hasTypoScriptFrontendController()) {
            throw new ContentRenderingException('TypoScriptFrontendController is not available.', 1655723512);
        }

        return $this->cObj->getTypoScriptFrontendController();
    }

    /**
     * @return PageRepository
     */
    protected function getPageRepository()
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

    /**
     * @return PageRenderer
     */
    protected function getPageRenderer()
    {
        if ($this->pageRenderer === null) {
            $this->pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        }

        return $this->pageRenderer;
    }
}
