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

namespace TYPO3Fluid\FluidTest\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class EscapingInterceptorDisabledViewHelper
 */
abstract class AbstractEscapingBaseViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('content', 'string', 'Content provided as argument', false, null);
    }

    /**
     * @return string
     */
    public function render(): string
    {
        if (!isset($this->arguments['content'])) {
            $content = $this->renderChildren();
        } else {
            $content = $this->arguments['content'];
        }
        return $content;
    }
}
