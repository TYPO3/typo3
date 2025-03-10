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

use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to shows the version numbers of the TYPO3 dependency, if any.
 *
 * ```
 *   <em:typo3Dependency extension="{distribution}" />
 * ```
 *
 * @internal
 */
final class Typo3DependencyViewHelper extends AbstractViewHelper
{
    /**
     * As this ViewHelper renders HTML, the output must not be escaped.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('extension', Extension::class, '', true);
    }

    public function render(): string
    {
        $dependency = $this->arguments['extension']->getTypo3Dependency();
        if ($dependency === null) {
            return '';
        }
        return sprintf(
            '<span class="badge badge-%s">%s - %s</span>',
            $dependency->isVersionCompatible(VersionNumberUtility::getNumericTypo3Version()) ? 'success' : 'default',
            htmlspecialchars($dependency->getLowestVersion()),
            htmlspecialchars($dependency->getHighestVersion())
        );
    }
}
