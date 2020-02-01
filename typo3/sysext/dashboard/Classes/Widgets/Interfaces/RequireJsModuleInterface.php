<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Dashboard\Widgets\Interfaces;

/**
 * Interface RequireJsModuleInterface
 * In case a widget should provide additional requireJS modules, the widget must implement this interface.
 */
interface RequireJsModuleInterface
{
    /**
     * This method returns an array with requireJs modules.
     * e.g. [
     *   'TYPO3/CMS/Backend/Modal',
     *   'TYPO3/CMS/MyExt/FooBar' => 'function(FooBar) { ... }'
     * ]
     * @return array
     */
    public function getRequireJsModules(): array;
}
