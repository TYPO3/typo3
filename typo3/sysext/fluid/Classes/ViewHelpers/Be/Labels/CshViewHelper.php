<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Be\Labels;

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
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * ViewHelper which returns CSH (context sensitive help) label with icon hover.
 *
 * .. note::
 *    The CSH label will only work, if the current BE user has the "Context
 *    Sensitive Help mode" set to something else than "Display no help
 *    information" in the Users settings.
 *
 * .. note::
 *    This ViewHelper is experimental!
 *
 * Examples
 * ========
 *
 * Default::
 *
 *    <f:be.labels.csh />
 *
 * CSH label as known from the TYPO3 backend.
 *
 * Full configuration::
 *
 *    <f:be.labels.csh table="xMOD_csh_corebe" field="someCshKey" label="lang/Resources/Private/Language/locallang/header.languages" />
 *
 * CSH label as known from the TYPO3 backend with some custom settings.
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
     * Returns the Language Service
     * @return LanguageService
     */
    protected static function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

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
        $this->registerArgument('label', 'string', 'Language label which is wrapped with the CSH', false, '');
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
        $label = $arguments['label'];

        if ($table === null) {
            $currentRequest = $renderingContext->getControllerContext()->getRequest();
            $moduleName = $currentRequest->getPluginName();
            $table = '_MOD_' . $moduleName;
        }
        if (strpos($label, 'LLL:') === 0) {
            $label = self::getLanguageService()->sL($label);
        }
        // Double encode can be set to true, once the typo3fluid/fluid fix is released and required
        $label = '<label>' . htmlspecialchars($label, ENT_QUOTES, null, false) . '</label>';
        return BackendUtility::wrapInHelp($table, $field, $label);
    }
}
