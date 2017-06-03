<?php
declare(strict_types=1);
namespace TYPO3\CMS\Install\View;

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

use TYPO3\CMS\Extbase\Mvc\View\AbstractView;

/**
 * Simple JsonView (currently returns an associative array)
 */
class JsonView extends AbstractView
{
    /**
     * Main render() method just returns all variables.
     *
     * @todo: This should be done differently and will be refactored with psr-7 switch
     * @return array
     */
    public function render(): array
    {
        return $this->variables;
    }
}
