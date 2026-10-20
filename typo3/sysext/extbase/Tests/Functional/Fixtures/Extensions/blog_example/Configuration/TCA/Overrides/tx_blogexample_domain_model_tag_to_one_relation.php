<?php

declare(strict_types=1);

defined('TYPO3') or die();

$GLOBALS['TCA']['tx_blogexample_domain_model_tag']['columns']['items']['config']['MM_oppositeUsage']['tx_blogexample_domain_model_person'][] = 'main_tag';
