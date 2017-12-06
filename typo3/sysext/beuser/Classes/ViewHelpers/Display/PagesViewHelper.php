<?php
namespace TYPO3\CMS\Beuser\ViewHelpers\Display;

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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Converts comma separated list of pages uids to html unordered list (<ul>) with speaking titles
 * @internal
 */
class PagesViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * As this ViewHelper renders HTML, the output must not be escaped.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initializes the arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('uids', 'string', '', false, '');
    }

    /**
     * Render unordered list for pages
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $uids = $arguments['uids'];
        if (!$uids) {
            return '';
        }

        $content = '';

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();

        $res = $queryBuilder
            ->select('uid', 'title')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter(
                        GeneralUtility::intExplode(',', $uids),
                        Connection::PARAM_INT_ARRAY
                    )
                )
            )
            ->orderBy('uid', 'ASC')
            ->execute();

        while ($row = $res->fetch()) {
            $content .= '<li>' . htmlspecialchars($row['title']) . ' [' . htmlspecialchars($row['uid']) . ']</li>';
        }
        return '<ul>' . $content . '</ul>';
    }
}
