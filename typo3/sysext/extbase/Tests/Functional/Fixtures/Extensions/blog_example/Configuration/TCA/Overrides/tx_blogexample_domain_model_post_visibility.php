<?php

declare(strict_types=1);

defined('TYPO3') or die();

$GLOBALS['TCA']['tx_blogexample_domain_model_post']['ctrl']['enablecolumns']['starttime'] = 'starttime';
$GLOBALS['TCA']['tx_blogexample_domain_model_post']['ctrl']['enablecolumns']['endtime'] = 'endtime';
$GLOBALS['TCA']['tx_blogexample_domain_model_post']['ctrl']['enablecolumns']['fe_group'] = 'fe_group';
$GLOBALS['TCA']['tx_blogexample_domain_model_post']['columns']['starttime'] = ['config' => ['type' => 'datetime', 'default' => 0]];
$GLOBALS['TCA']['tx_blogexample_domain_model_post']['columns']['endtime'] = ['config' => ['type' => 'datetime', 'default' => 0]];
$GLOBALS['TCA']['tx_blogexample_domain_model_post']['columns']['fe_group'] = $GLOBALS['TCA']['tx_blogexample_domain_model_blog']['columns']['fe_group'];
