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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Converts comma separated list of sys_language uids to html unordered list (<ul>) with speaking titles
 * @internal
 */
class SysLanguageViewHelper extends AbstractViewHelper
{
    /**
     * As this ViewHelper renders HTML, the output must not be escaped.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Render unordered list for sys_language
     *
     * @param string $uids
     * @return string
     */
    public function render($uids = '')
    {
        return static::renderStatic(
            array(
                'uids' => $uids,
            ),
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
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

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_filemounts');
        $queryBuilder->getRestrictions()->removeAll();

        $res = $queryBuilder
            ->select('uid', 'title', 'flag')
            ->from('sys_language')
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    GeneralUtility::intExplode(',', $uids)
                )
            )
            ->orderBy('title', 'ASC')
            ->execute();

        while ($row = $res->fetch()) {
            $content .= '<li>' . htmlspecialchars($row['title']) . ' [' . htmlspecialchars($row['uid']) . ']</li>';
        }
        return '<ul>' . $content . '</ul>';
    }
}
