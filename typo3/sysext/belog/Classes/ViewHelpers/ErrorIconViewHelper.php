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

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Display error icon from error integer value
 * @internal
 */
class ErrorIconViewHelper extends AbstractBackendViewHelper
{
    /**
     * As this ViewHelper renders HTML, the output must not be escaped.
     *
     * @var bool
     */
    protected $escapeOutput = false;

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
            array(
                'errorNumber' => $errorNumber
            ),
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $errorSymbols = array(
            '0' => '',
            '1' => 'status-dialog-warning',
            '2' => 'status-dialog-error',
            '3' => 'status-dialog-error'
        );
        if ($errorSymbols[$arguments['errorNumber']]) {
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
            return $iconFactory->getIcon($errorSymbols[$arguments['errorNumber']], Icon::SIZE_SMALL)->render();
        } else {
            return '';
        }
    }
}
