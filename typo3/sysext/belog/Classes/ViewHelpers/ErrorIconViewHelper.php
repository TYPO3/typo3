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

use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;
use TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper;

/**
 * Display error icon from error integer value
 * @internal
 */
class ErrorIconViewHelper extends AbstractBackendViewHelper implements CompilableInterface
{
    /**
     * Renders an error icon link as known from the TYPO3 backend.
     * Error codes 2 and three are mapped to "error" and 1 is mapped to "warning".
     *
     * @param int $errorNumber The error number (0 ... 3)
     * @return string the rendered error icon link
     */
    public function render($errorNumber = 0)
    {
        return static::renderStatic(
            [
                'errorNumber' => $errorNumber
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
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $errorSymbols = [
            '0' => '',
            '1' => DocumentTemplate::STATUS_ICON_WARNING,
            '2' => DocumentTemplate::STATUS_ICON_ERROR,
            '3' => DocumentTemplate::STATUS_ICON_ERROR
        ];
        $doc = GeneralUtility::makeInstance(DocumentTemplate::class);
        return $doc->icons($errorSymbols[$arguments['errorNumber']]);
    }
}
