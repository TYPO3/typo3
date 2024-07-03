<?php

defined('TYPO3') or die();

// @todo: Review. It is unclear if start/end time restrictions different from default-lang actually work if FE.
$GLOBALS['TCA']['sys_category']['columns']['starttime']['config']['behaviour']['allowLanguageSynchronization'] = true;
$GLOBALS['TCA']['sys_category']['columns']['endtime']['config']['behaviour']['allowLanguageSynchronization'] = true;
