<?php

defined('TYPO3_MODE') or die();

// Register FormEngine node type resolver hook to render RTE in FormEngine if enabled
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeResolver'][1480314091] = [
    'nodeName' => 'text',
    'priority' => 50,
    'class' => \TYPO3\CMS\RteCKEditor\Form\Resolver\RichTextNodeResolver::class,
];

// Hook to add rte_ckeditor requirejs config to PageRenderer in backend
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess'][] =
    \TYPO3\CMS\RteCKEditor\Hook\PageRendererRenderPreProcess::class . '->addRequireJsConfiguration';

// Register the presets
if (empty($GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['default'])) {
    $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['default'] = 'EXT:rte_ckeditor/Configuration/RTE/Default.yaml';
}
if (empty($GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['minimal'])) {
    $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['minimal'] = 'EXT:rte_ckeditor/Configuration/RTE/Minimal.yaml';
}
if (empty($GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['full'])) {
    $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['full'] = 'EXT:rte_ckeditor/Configuration/RTE/Full.yaml';
}
