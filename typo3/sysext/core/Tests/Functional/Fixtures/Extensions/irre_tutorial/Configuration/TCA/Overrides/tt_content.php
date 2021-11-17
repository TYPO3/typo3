<?php

declare(strict_types=1);

defined('TYPO3') or die();

// Show copied tt_content records in frontend request
$GLOBALS['TCA']['tt_content']['ctrl']['hideAtCopy'] = false;
