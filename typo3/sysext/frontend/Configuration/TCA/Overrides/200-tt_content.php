<?php

defined('TYPO3') or die();

// @todo: This is actually currently probably the sane default that might be moved to
//        TcaEnrichment: Setting different star/end time restriction differently on localized
//        records is not or at least only partially supported by current core?!
// Override TceEnrichment defaults
$GLOBALS['TCA']['tt_content']['columns']['starttime']['l10n_mode'] = 'exclude';
$GLOBALS['TCA']['tt_content']['columns']['starttime']['l10n_display'] = 'defaultAsReadonly';
$GLOBALS['TCA']['tt_content']['columns']['endtime']['l10n_mode'] = 'exclude';
$GLOBALS['TCA']['tt_content']['columns']['endtime']['l10n_display'] = 'defaultAsReadonly';
