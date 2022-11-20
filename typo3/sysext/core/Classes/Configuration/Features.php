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

namespace TYPO3\CMS\Core\Configuration;

/**
 * A lightweight API class to check if a feature is enabled.
 *
 * Features are simple options (true/false), and are stored in the
 * global configuration array $TYPO3_CONF_VARS[SYS][features].
 *
 * For disabling or enabling a feature the "ConfigurationManager"
 * should be used.
 *
 * -- Naming --
 *
 * Feature names should NEVER named "enable" or having a negation, or containing versions or years
 *    "enableFeatureXyz"
 *    "disableOverlays"
 *    "schedulerRevamped"
 *    "useDoctrineQueries"
 *    "disablePreparedStatements"
 *    "disableHooksInFE"
 *
 * Proper namings for features
 *    "ExtendedRichtextFormat"
 *    "NativeYamlParser"
 *    "InlinePageTranslations"
 *    "TypoScriptParserIncludesAsXml"
 *    "NativeDoctrineQueries"
 *
 * Ideally, these feature switches are added via the Install Tool or via FactoryConfiguration
 * and can be used for Extensions as well.
 *
 * --- Usage ---
 *
 * if (GeneralUtility::makeInstance(Features::class)->isFeatureEnabled('InlineSvg')) {
 *   ... do stuff here ...
 * }
 */
class Features
{
    /**
     * A list of features that are always activated (mainly happens if a previous feature switch is now always
     * "turned on" to enforce a behaviour, but still valid for extension authors to ensure the feature switch
     * returns "enabled" for future versions.
     */
    protected array $alwaysActiveFeatures = [
        // Enabled since v12.0 at any time.
        'subrequestPageErrors',
        'yamlImportsFollowDeclarationOrder',
        'security.frontend.htmlSanitizeParseFuncDefault',
        'runtimeDbQuotingOfTcaConfiguration',
        // Enabled since v11.0 at any time.
        'fluidBasedPageModule',
        // Enabled since v10.0 at any time.
        'simplifiedControllerActionDispatching',
        'unifiedPageTranslationHandling',
        'felogin.extbase',
    ];

    /**
     * Checks if a feature is active
     *
     * @param string $featureName the name of the feature
     */
    public function isFeatureEnabled(string $featureName): bool
    {
        if (in_array($featureName, $this->alwaysActiveFeatures, true)) {
            return true;
        }
        return isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['features'][$featureName]) && $GLOBALS['TYPO3_CONF_VARS']['SYS']['features'][$featureName] === true;
    }
}
