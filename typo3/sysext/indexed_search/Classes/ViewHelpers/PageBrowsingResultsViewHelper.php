<?php
namespace TYPO3\CMS\IndexedSearch\ViewHelpers;

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

use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;

/**
 * renders the header of the results page
 * @internal
 */
class PageBrowsingResultsViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * main render function
     *
     * @param int $numberOfResults
     * @param int $resultsPerPage
     * @param int $currentPage
     * @return string the content
     */
    public function render($numberOfResults, $resultsPerPage, $currentPage = 1)
    {
        return static::renderStatic(
            [
                'numberOfResults' => $numberOfResults,
                'resultsPerPage' => $resultsPerPage,
                'currentPage' => $currentPage,
            ],
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * @param array $arguments
     * @param callable|\Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $numberOfResults = $arguments['numberOfResults'];
        $resultsPerPage = $arguments['resultsPerPage'];
        $currentPage = $arguments['currentPage'];

        $firstResultOnPage = $currentPage * $resultsPerPage + 1;
        $lastResultOnPage = $currentPage * $resultsPerPage + $resultsPerPage;
        $label = LocalizationUtility::translate('displayResults', 'IndexedSearch');
        return sprintf($label, $firstResultOnPage, min([$numberOfResults, $lastResultOnPage]), $numberOfResults);
    }
}
