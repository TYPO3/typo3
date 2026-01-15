<?php

declare(strict_types=1);

defined('TYPO3') or die();

$caminoIconList =  [
    [
        'label' => 'theme_camino.backend_fields:tx_camino.icon.none',
        'value' => '',
    ],
    [
        'label' => 'theme_camino.backend_fields:tx_camino.icon.arrow-left',
        'value' => 'arrow-left',
    ],
    [
        'label' => 'theme_camino.backend_fields:tx_camino.icon.arrow-right',
        'value' => 'arrow-right',
    ],
    [
        'label' => 'theme_camino.backend_fields:tx_camino.icon.chevron',
        'value' => 'chevron',
    ],
    [
        'label' => 'theme_camino.backend_fields:tx_camino.icon.chevron-double-left',
        'value' => 'chevron-double-left',
    ],
    [
        'label' => 'theme_camino.backend_fields:tx_camino.icon.chevron-double-right',
        'value' => 'chevron-double-right',
    ],
    [
        'label' => 'theme_camino.backend_fields:tx_camino.icon.chevron-left',
        'value' => 'chevron-left',
    ],
    [
        'label' => 'theme_camino.backend_fields:tx_camino.icon.chevron-right',
        'value' => 'chevron-right',
    ],
    [
        'label' => 'theme_camino.backend_fields:tx_camino.icon.close',
        'value' => 'close',
    ],
    [
        'label' => 'theme_camino.backend_fields:tx_camino.icon.download',
        'value' => 'download',
    ],
    [
        'label' => 'theme_camino.backend_fields:tx_camino.icon.globe',
        'value' => 'globe',
    ],
    [
        'label' => 'theme_camino.backend_fields:tx_camino.icon.launch',
        'value' => 'launch',
    ],
    [
        'label' => 'theme_camino.backend_fields:tx_camino.icon.mail',
        'value' => 'mail',
    ],
    [
        'label' => 'theme_camino.backend_fields:tx_camino.icon.menu',
        'value' => 'menu',
    ],
    [
        'label' => 'theme_camino.backend_fields:tx_camino.icon.phone',
        'value' => 'phone',
    ],
    [
        'label' => 'theme_camino.backend_fields:tx_camino.icon.play',
        'value' => 'play',
    ],
    [
        'label' => 'theme_camino.backend_fields:tx_camino.icon.search',
        'value' => 'search',
    ],
    [
        'label' => 'theme_camino.backend_fields:tx_camino.icon.social-facebook',
        'value' => 'social-facebook',
    ],
    [
        'label' => 'theme_camino.backend_fields:tx_camino.icon.social-instagram',
        'value' => 'social-instagram',
    ],
    [
        'label' => 'theme_camino.backend_fields:tx_camino.icon.social-linkedin',
        'value' => 'social-linkedin',
    ],
    [
        'label' => 'theme_camino.backend_fields:tx_camino.icon.social-x',
        'value' => 'social-x',
    ],
    [
        'label' => 'theme_camino.backend_fields:tx_camino.icon.social-xing',
        'value' => 'social-xing',
    ],
    [
        'label' => 'theme_camino.backend_fields:tx_camino.icon.social-youtube',
        'value' => 'social-youtube',
    ],
    [
        'label' => 'theme_camino.backend_fields:tx_camino.icon.zoom',
        'value' => 'zoom',
    ],
];

$GLOBALS['TCA']['tx_themecamino_list_item']['columns']['link_icon']['config']['items'] = $caminoIconList;
$GLOBALS['TCA']['tt_content']['columns']['tx_themecamino_link_icon']['config']['items'] = $caminoIconList;

unset($caminoIconList);
