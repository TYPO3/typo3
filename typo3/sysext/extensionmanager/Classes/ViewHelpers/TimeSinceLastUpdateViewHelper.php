<?php
namespace TYPO3\CMS\Extensionmanager\ViewHelpers;

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
 * Shows the elapsed time since the last update of the extension repository
 * from TER in a readable manner.
 *
 * @internal
 */
class TimeSinceLastUpdateViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * Render method
     *
     * @param \DateTime $lastUpdateTime The date of the last update.
     * @return string
     */
    public function render($lastUpdateTime)
    {
        return static::renderStatic(
            [
                'lastUpdateTime' => $lastUpdateTime,
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
        $lastUpdateTime = $arguments['lastUpdateTime'];
        if (null === $lastUpdateTime) {
            return $GLOBALS['LANG']->sL(
                'LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:extensionList.updateFromTer.never'
            );
        }
        return \TYPO3\CMS\Backend\Utility\BackendUtility::calcAge(
            time() - $lastUpdateTime->format('U'),
            $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.minutesHoursDaysYears')
        );
    }
}
