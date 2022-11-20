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

namespace TYPO3\CMS\Fluid\ViewHelpers\Be\Labels;

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
 *
 * @deprecated
 */
final class CshViewHelper extends AbstractBackendViewHelper
{
    /**
     * As this ViewHelper renders HTML, the output must not be escaped.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('table', 'string', 'Table name (\'_MOD_\'+module name). If not set, the current module name will be used');
        $this->registerArgument('field', 'string', 'Field name (CSH locallang main key)', false, '');
        $this->registerArgument('label', 'string', 'Language label which is wrapped with the CSH', false, '');
    }

    public function render(): string
    {
        return self::renderStatic($this->arguments, $this->buildRenderChildrenClosure(), $this->renderingContext);
    }

    /**
     * @deprecated The functionality has been removed in v12. The class will be removed in TYPO3 v13.
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): string
    {
        trigger_error(
            __CLASS__ . ' is returning the translated label wrapped in a <label> tag only in TYPO3 v12 and will be removed in TYPO3 v13.0.',
            E_USER_DEPRECATED
        );

        $label = $arguments['label'];

        $label = self::getLanguageService()->sL($label);
        return '<label>' . htmlspecialchars($label, ENT_QUOTES, '', true) . '</label>';
    }

    protected static function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
