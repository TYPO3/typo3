<?php
$EM_CONF[$_EXTKEY] = array(
  'title' => 'Extension skeleton for TYPO3 7',
  'description' => 'Description for ext',
  'category' => 'Example Extensions',
  'author' => 'Helmut Hummel',
  'author_email' => 'info@helhum.io',
  'author_company' => 'helhum.io',
  'shy' => '',
  'priority' => '',
  'module' => '',
  'state' => 'stable',
  'internal' => '',
  'uploadfolder' => '0',
  'createDirs' => '',
  'modify_tables' => '',
  'clearCacheOnLoad' => 0,
  'lockType' => '',
  'version' => '0.0.2',
  'constraints' =>
  array(
    'depends' =>
    array(
      'typo3' => '7.5.0-7.99.99',
    ),
    'conflicts' =>
    array(
    ),
    'suggests' =>
    array(
    ),
  ),
  'autoload' =>
  array(
    'psr-4' =>
    array(
      'TYPO3Fluid\\FluidTest\\' => 'Classes',
    ),
  ),
  'autoload-dev' =>
  array(
    'psr-4' =>
    array(
      'TYPO3Fluid\\FluidTest\\Tests\\' => 'Tests',
    ),
  ),
);
