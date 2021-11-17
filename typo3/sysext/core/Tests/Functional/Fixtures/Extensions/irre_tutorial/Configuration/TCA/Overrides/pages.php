<?php

declare(strict_types=1);

defined('TYPO3') or die();

// Show copied pages records in frontend request
$GLOBALS['TCA']['pages']['ctrl']['hideAtCopy'] = false;
