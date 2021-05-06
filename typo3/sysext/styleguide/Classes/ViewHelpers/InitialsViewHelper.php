<?php

declare(strict_types=1);
namespace TYPO3\CMS\Styleguide\ViewHelpers;

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
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * First two chars of a string
 */
class InitialsViewHelper extends AbstractViewHelper
{

    /**
     * Initializes the arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('text', 'string', 'the text to trim into initials', true);
    }

    /**
     * @return string
     */
    public function render()
    {
        return substr($this->arguments['text'], 0, 1) . strtolower(substr($this->arguments['text'], 1, 1));
    }
}
