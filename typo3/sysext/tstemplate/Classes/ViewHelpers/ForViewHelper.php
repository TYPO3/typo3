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

namespace TYPO3\CMS\Tstemplate\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Repeats rendering of children with a typical for loop:
 * Starting at index $from it will loop until the index has reached $to.
 *
 * @internal This is not part of TYPO3 Core API.
 */
final class ForViewHelper extends AbstractViewHelper
{
    protected $escapeChildren = false;
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('to', 'integer', 'Number the index needs to be smaller than before stopping (<)', true);
        $this->registerArgument('from', 'integer', 'Starting number for the index', false, 0);
    }

    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): string
    {
        $to = (int)$arguments['to'];
        $from = (int)$arguments['from'];
        $content = '';
        if ($from < $to) {
            for ($i = $from; $i < $to; $i++) {
                $content .= $renderChildrenClosure();
            }
        }
        return $content;
    }
}
