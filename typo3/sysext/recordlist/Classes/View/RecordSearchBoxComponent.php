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

namespace TYPO3\CMS\Recordlist\View;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3Fluid\Fluid\View\ViewInterface;

/**
 * Renders the search box for the record listing and the element browser.
 *
 * @internal
 */
class RecordSearchBoxComponent
{
    protected ViewInterface $view;
    protected array $allowedSearchLevels = [];
    protected string $searchWord = '';
    protected int $searchLevel = 0;

    public function __construct(ViewInterface $view = null)
    {
        $this->view = $view ?? $this->getView('Search.html');
    }

    public function setSearchWord(string $searchWord): self
    {
        $this->searchWord = $searchWord;
        return $this;
    }

    public function setSearchLevel(int $searchLevel): self
    {
        $this->searchLevel = $searchLevel;
        return $this;
    }

    public function setAllowedSearchLevels(array $allowedSearchLevels): self
    {
        $this->allowedSearchLevels = $allowedSearchLevels;
        return $this;
    }

    public function render(string $formUrl = ''): string
    {
        return $this->view
            ->assignMultiple([
                'formUrl' => $formUrl,
                'availableSearchLevels' => $this->allowedSearchLevels,
                'selectedSearchLevel' => $this->searchLevel,
                'searchString' => $this->searchWord,
            ])
            ->render();
    }

    protected function getView(string $filename): StandaloneView
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplateRootPaths(['EXT:recordlist/Resources/Private/Templates']);
        $view->setTemplate($filename);
        return $view;
    }
}
