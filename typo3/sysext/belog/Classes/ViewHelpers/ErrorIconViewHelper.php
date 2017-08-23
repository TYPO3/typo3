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
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Display error icon from error integer value
 * @internal
 */
class ErrorIconViewHelper extends AbstractBackendViewHelper
{
    use CompileWithRenderStatic;

    /**
     * As this ViewHelper renders HTML, the output must not be escaped.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initializes the arguments
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('errorNumber', 'int', 'The error number (0 ... 3)', false, 0);
    }

    /**
     * Renders an error icon link as known from the TYPO3 backend.
     * Error codes 2 and three are mapped to "error" and 1 is mapped to "warning".
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $errorSymbols = [
            '0' => '',
            '1' => 'status-dialog-warning',
            '2' => 'status-dialog-error',
            '3' => 'status-dialog-error'
        ];
        if ($errorSymbols[$arguments['errorNumber']]) {
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
            return $iconFactory->getIcon($errorSymbols[$arguments['errorNumber']], Icon::SIZE_SMALL)->render();
        }
        return '';
    }
}
