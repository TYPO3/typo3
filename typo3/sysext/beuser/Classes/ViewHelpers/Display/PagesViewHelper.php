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

use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;

/**
 * Converts comma separated list of pages uids to html unordered list (<ul>) with speaking titles
 * @internal
 */
class PagesViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * Render unordered list for pages
     *
     * @param string $uids
     * @return string
     */
    public function render($uids = '')
    {
        return static::renderStatic(
            [
                'uids' => $uids,
            ],
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * @param array $arguments
     * @param callable $renderChildrenClosure
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
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'uid, title',
            'pages',
            'uid IN (' . $GLOBALS['TYPO3_DB']->cleanIntList($uids) . ')',
            'uid ASC'
        );
        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            $content .= '<li>' . htmlspecialchars($row['title']) . ' [' . htmlspecialchars($row['uid']) . ']</li>';
        }
        $GLOBALS['TYPO3_DB']->sql_free_result($res);
        return '<ul>' . $content . '</ul>';
    }
}
