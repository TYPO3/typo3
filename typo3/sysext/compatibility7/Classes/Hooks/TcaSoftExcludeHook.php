<?php
namespace TYPO3\CMS\Compatibility7\Hooks;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Hook to validate the TypoScript option
 * config.sys_language_softExclude
 * that allows to set certain table/field combinations to use l10n_mode=exclude which have not set to l10n_mode
 * by default.
 *
 * This option is mostly obsolete with TYPO3 v8 as the database sets for translation modes are handled via
 * "allowLanguageSynchronization" where all fields are properly filled in all translations
 */
class TcaSoftExcludeHook
{
    /**
     * Hooks in TSFE after the language initialization to set TCA l10n_mode=exclude on certain fields
     * on runtime, called "softExclude"
     *
     * @param array $parameters left empty, not in use
     * @param TypoScriptFrontendController $parentObject
     */
    public function setCustomExcludeFields(array $parameters, TypoScriptFrontendController $parentObject)
    {
        if (isset($parentObject->config['config']['sys_language_softExclude'])
            && !empty($parentObject->config['config']['sys_language_softExclude'])) {
            $tableFieldCombinations = GeneralUtility::trimExplode(',', $parentObject->config['config']['sys_language_softExclude'], true);
            foreach ($tableFieldCombinations as $tableFieldCombination) {
                list($tableName, $fieldName) = explode(':', $tableFieldCombination);
                $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['l10n_mode'] = 'exclude';
            }
        }
    }
}
