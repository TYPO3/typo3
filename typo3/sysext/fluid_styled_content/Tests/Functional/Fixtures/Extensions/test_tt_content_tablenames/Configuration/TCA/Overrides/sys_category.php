<?php

declare(strict_types=1);

defined('TYPO3') or die();

// "multiple" makes DefaultTcaSchema add a uid column to sys_category_record_mm
$GLOBALS['TCA']['sys_category']['columns']['items']['config']['multiple'] = true;
