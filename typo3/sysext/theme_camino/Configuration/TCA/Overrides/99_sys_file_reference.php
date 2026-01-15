<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Resource\FileType;

defined('TYPO3') or die();

/**
 * use custom field configuration for images/media fields where appropriate.
 * We never want to show a link field for example if linking an image does not make sense (e.g. separately linking an
 * image that is part of a fully linked teaser).
 */
$GLOBALS['TCA']['sys_file_reference']['palettes']['caminoImagePalette'] = [
    'showitem' => 'alternative,title,--linebreak--,crop',
    'label' => 'core.tca:sys_file_reference.imageoverlayPalette',
];

$defaultImageTypeConfig = $GLOBALS['TCA']['sys_file_reference']['types'];
$defaultImageTypeConfig[FileType::IMAGE->value]['showitem'] = '
    --palette--;;caminoImagePalette,
    --palette--;;filePalette
';

$GLOBALS['TCA']['tt_content']['types']['camino_author']['columnsOverrides']['image']['config']['overrideChildTca']['types'] = $defaultImageTypeConfig;
$GLOBALS['TCA']['tt_content']['types']['camino_hero']['columnsOverrides']['image']['config']['overrideChildTca']['types'] = $defaultImageTypeConfig;
$GLOBALS['TCA']['tt_content']['types']['camino_hero_small']['columnsOverrides']['image']['config']['overrideChildTca']['types'] = $defaultImageTypeConfig;
$GLOBALS['TCA']['tt_content']['types']['camino_testimonial']['columnsOverrides']['image']['config']['overrideChildTca']['types'] = $defaultImageTypeConfig;
$GLOBALS['TCA']['tt_content']['types']['camino_textmedia_teaser']['columnsOverrides']['image']['config']['overrideChildTca']['types'] = $defaultImageTypeConfig;
$GLOBALS['TCA']['tx_themecamino_list_item']['columns']['images']['config']['overrideChildTca']['types'] = $defaultImageTypeConfig;
$GLOBALS['TCA']['pages']['columns']['tx_themecamino_logo']['config']['overrideChildTca']['types'] = $defaultImageTypeConfig;

unset($defaultImageTypeConfig);
