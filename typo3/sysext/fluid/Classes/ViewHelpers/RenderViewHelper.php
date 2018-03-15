<?php
namespace TYPO3\CMS\Fluid\ViewHelpers;

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

/**
 * Class RenderViewHelper
 * @inheritdoc
 */
class RenderViewHelper extends \TYPO3Fluid\Fluid\ViewHelpers\RenderViewHelper
{
    /**
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('debug', 'boolean', 'If true, the admin panel shows debug information if activated,', false, true);
    }
}
