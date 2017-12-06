<?php
namespace TYPO3\CMS\Extensionmanager\ViewHelpers;

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

use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extensionmanager\Domain\Model\Dependency;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Shows the version numbers of the TYPO3 dependency, if any
 *
 * @internal
 */
class Typo3DependencyViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * As this ViewHelper renders HTML, the output must not be escaped.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initialize arguments
     *
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        $this->registerArgument('extension', Extension::class, '', true);
    }

    /**
     * Finds and returns the suitable TYPO3 versions of an extension
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $extension = $arguments['extension'];
        /** @var Dependency $dependency */
        foreach ($extension->getDependencies() as $dependency) {
            if ($dependency->getIdentifier() === 'typo3') {
                $lowestVersion = $dependency->getLowestVersion();
                $highestVersion = $dependency->getHighestVersion();
                $cssClass = self::isVersionSuitable($lowestVersion, $highestVersion) ? 'success' : 'default';
                return
                    '<span class="label label-' . $cssClass . '">'
                        . htmlspecialchars($lowestVersion) . ' - ' . htmlspecialchars($highestVersion)
                    . '</span>';
            }
        }
        return '';
    }

    /**
     * Check if current TYPO3 version is suitable for the extension
     *
     * @param string $lowestVersion
     * @param string $highestVersion
     * @return bool
     */
    protected static function isVersionSuitable($lowestVersion, $highestVersion)
    {
        $numericTypo3Version = VersionNumberUtility::convertVersionNumberToInteger(VersionNumberUtility::getNumericTypo3Version());
        $numericLowestVersion = VersionNumberUtility::convertVersionNumberToInteger($lowestVersion);
        $numericHighestVersion = VersionNumberUtility::convertVersionNumberToInteger($highestVersion);
        return MathUtility::isIntegerInRange($numericTypo3Version, $numericLowestVersion, $numericHighestVersion);
    }
}
