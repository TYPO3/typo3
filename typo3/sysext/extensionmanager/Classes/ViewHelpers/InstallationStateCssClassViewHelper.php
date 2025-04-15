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

namespace TYPO3\CMS\Extensionmanager\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to returns a string meant to be used as CSS class, stating whether an extension is available or installed.
 *
 * ```
 *   <tr class="{em:installationStateCssClass(needle:extension.extensionKey, haystack:availableAndInstalled)}">
 * ```
 *
 * @internal
 */
final class InstallationStateCssClassViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('needle', 'string', '', true);
        $this->registerArgument('haystack', 'array', '', true);
    }

    /**
     * Returns string meant to be used as css class
     * 'installed' => if an extension is installed
     * 'available' => if an extension is available in the system
     * '' (empty string) => if neither installed nor available
     */
    public function render(): string
    {
        $needle = $this->arguments['needle'];
        $haystack = $this->arguments['haystack'];
        if (array_key_exists($needle, $haystack)) {
            if (isset($haystack[$needle]['installed']) && $haystack[$needle]['installed'] === true) {
                return 'installed';
            }
            return 'available';
        }
        return '';
    }
}
