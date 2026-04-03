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

namespace TYPO3\CMS\Fluid\ViewHelpers;

/**
 * A ViewHelper to render a section, a partial, a specified section in a partial
 * or a delegate ParsedTemplateInterface implementation.
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-render
 */
final class RenderViewHelper extends \TYPO3Fluid\Fluid\ViewHelpers\RenderViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('debug', 'boolean', 'If true, the admin panel shows debug information if activated,', false, true);
    }
}
