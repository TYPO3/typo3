<?php
declare(strict_types=1);
namespace TYPO3\CMS\Form\ViewHelpers\Be;

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

use TYPO3\CMS\Fluid\ViewHelpers\Be\PageRendererViewHelper as FluidPageRendererViewHelper;

/**
 * Extends the FluidPageRendererViewHelper
 * Add the additional argument 'addInlineSettings' to add settings to
 * the TYPO3 javascript inline setting
 *
 * Scope: backend
 * @internal
 */
class PageRendererViewHelper extends FluidPageRendererViewHelper
{

    /**
     * Initialize arguments.
     *
     * @return void
     * @internal
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('addInlineSettings', 'array', 'Adds Javascript Inline Setting');
    }

    /**
     * @return void
     * @internal
     */
    public function render()
    {
        $addInlineSettings = $this->arguments['addInlineSettings'];
        if (is_array($addInlineSettings) && count($addInlineSettings) > 0) {
            $this->pageRenderer->addInlineSettingArray(null, $addInlineSettings);
        }

        parent::render();
    }
}
