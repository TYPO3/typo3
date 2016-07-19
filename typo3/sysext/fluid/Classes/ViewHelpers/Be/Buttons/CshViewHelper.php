<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Be\Buttons;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * View helper which returns CSH (context sensitive help) button with icon
 * Note: The CSH button will only work, if the current BE user has the "Context Sensitive Help mode"
 * set to something else than "Display no help information" in the Users settings
 * Note: This view helper is experimental!
 *
 * = Examples =
 *
 * <code title="Default">
 * <f:be.buttons.csh />
 * </code>
 * <output>
 * CSH button as known from the TYPO3 backend.
 * </output>
 *
 * <code title="Full configuration">
 * <f:be.buttons.csh table="xMOD_csh_corebe" field="someCshKey" />
 * </code>
 * <output>
 * CSH button as known from the TYPO3 backend with some custom settings.
 * </output>
 */
class CshViewHelper extends AbstractBackendViewHelper
{
    /**
     * As this ViewHelper renders HTML, the output must not be escaped.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initialize arguments.
     *
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('table', 'string', 'Table name (\'_MOD_\'+module name). If not set, the current module name will be used');
        $this->registerArgument('field', 'string', 'Field name (CSH locallang main key)', false, '');
        $this->registerArgument('wrap', 'string', 'Markup to wrap around the CSH, split by "|"', false, '');
    }

    /**
     * Render context sensitive help (CSH) for the given table
     *
     * @return string the rendered CSH icon
     */
    public function render()
    {
        return static::renderStatic(
            $this->arguments,
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $table = $arguments['table'];
        $field = $arguments['field'];
        $wrap = $arguments['wrap'];

        if ($table === null) {
            $currentRequest = $renderingContext->getControllerContext()->getRequest();
            $moduleName = $currentRequest->getPluginName();
            $table = '_MOD_' . $moduleName;
        }
        return '<div class="docheader-csh">' . BackendUtility::cshItem($table, $field, '', $wrap) . '</div>';
    }
}
