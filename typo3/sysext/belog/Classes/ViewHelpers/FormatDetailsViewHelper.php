<?php
namespace TYPO3\CMS\Belog\ViewHelpers;

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

use TYPO3\CMS\Belog\Domain\Model\LogEntry;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;

/**
 * Create detail string from log entry
 * @internal
 */
class FormatDetailsViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * Create formatted detail string from log row.
     *
     * The method handles two properties of the model: details and logData
     * Details is a string with possible %s placeholders, and logData an array
     * with the substitutions.
     * Furthermore, possible files in logData are stripped to their basename if
     * the action logged was a file action
     *
     * @param LogEntry $logEntry
     * @return string Formatted details
     */
    public function render(LogEntry $logEntry)
    {
        return static::renderStatic(
            [
                'logEntry' => $logEntry
            ],
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * @param array $arguments
     * @param callable $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     * @throws Exception
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $logEntry = $arguments['logEntry'];
        $detailString = $logEntry->getDetails();
        $substitutes = $logEntry->getLogData();
        // Strip paths from file names if the log was a file action
        if ($logEntry->getType() === 2) {
            $substitutes = self::stripPathFromFilenames($substitutes);
        }
        // Substitute
        $detailString = vsprintf($detailString, $substitutes);
        // Remove possible pending other %s
        $detailString = str_replace('%s', '', $detailString);
        return htmlspecialchars($detailString);
    }

    /**
     * Strips path from array of file names
     *
     * @param array $files
     * @return array
     */
    protected static function stripPathFromFilenames(array $files = [])
    {
        foreach ($files as $key => $file) {
            $files[$key] = basename($file);
        }
        return $files;
    }
}
