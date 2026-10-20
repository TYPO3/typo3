<?php

declare(strict_types=1);

defined('TYPO3') or die();

// The subtitle follows the default record into its translations, the description gets the language prefix.
$GLOBALS['TCA']['tx_blogexample_domain_model_blog']['columns']['subtitle']['behaviour']['allowLanguageSynchronization'] = true;
$GLOBALS['TCA']['tx_blogexample_domain_model_blog']['columns']['description']['l10n_mode'] = 'prefixLangTitle';
