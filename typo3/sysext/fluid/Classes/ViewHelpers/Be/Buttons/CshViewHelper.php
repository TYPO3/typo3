<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Be\Buttons;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;
use TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper;

/**
 * View helper which returns CSH (context sensitive help) button with icon
 * Note: The CSH button will only work, if the current BE user has
 * the "Context Sensitive Help mode" set to something else than
 * "Display no help information" in the Users settings
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
class CshViewHelper extends AbstractBackendViewHelper implements CompilableInterface
{
    /**
     * Render context sensitive help (CSH) for the given table
     *
     * @param string $table Table name ('_MOD_'+module name). If not set, the current module name will be used
     * @param string $field Field name (CSH locallang main key)
     * @param bool $iconOnly Deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     * @param string $styleAttributes Deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     * @param string $wrap Markup to wrap around the CSH, split by "|"
     * @return string the rendered CSH icon
     */
    public function render($table = null, $field = '', $iconOnly = false, $styleAttributes = '', $wrap = '')
    {
        if ($iconOnly) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog(
                'The option iconOnly has no effect anymore and can be removed without problems. The parameter will be removed in TYPO3 CMS 8.'
            );
        }
        if ($styleAttributes) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog(
                'The option styleAttributes has no effect anymore and can be removed without problems. The parameter will be removed in TYPO3 CMS 8.'
            );
        }
        return static::renderStatic(
            [
                'table' => $table,
                'field' => $field,
                'wrap' => $wrap
            ],
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * @param array $arguments
     * @param callable $renderChildrenClosure
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
