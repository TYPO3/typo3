<?php

declare(strict_types=1);

defined('TYPO3') or die();

// New copies and translations of an info are hidden until an editor enables them.
$GLOBALS['TCA']['tx_blogexample_domain_model_info']['ctrl']['hideAtCopy'] = true;
