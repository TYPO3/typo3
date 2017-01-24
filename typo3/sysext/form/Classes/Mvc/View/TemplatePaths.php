<?php
declare(strict_types=1);
namespace TYPO3\CMS\Form\Mvc\View;

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

use TYPO3\CMS\Fluid\View\TemplatePaths as FluidTemplatePaths;

/**
 * Extend fluids TemplatePaths
 *
 * Scope: frontend
 * **This class is NOT meant to be sub classed by developers.**
 * @internal
 */
class TemplatePaths extends FluidTemplatePaths
{

    /**
     * Reset the templatePathAndFilename property to NULL.
     * $this->setTemplatePathAndFilename(null) don't work
     * because there is a (string) cast in setTemplatePathAndFilename
     * and this results in "$this->templatePathAndFilename = '';"
     *
     * @param string $templatePathAndFilename
     * @return void
     */
    public function clearTemplatePathAndFilename()
    {
        $this->templatePathAndFilename = null;
    }
}
