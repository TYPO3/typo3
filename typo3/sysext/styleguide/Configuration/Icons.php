<?php

return [
    // Register styleguide module icon
    'module-styleguide' => [
        'provider' => TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => 'EXT:styleguide/Resources/Public/Icons/module.svg',
    ],
    // Register styleguide svg for use within backend module
    'tcarecords-tx_styleguide_forms-default' => [
        'provider' => TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
    ],
    // Register example SVG for icon submodule
    'provider-svg' => [
        'provider' => TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => 'EXT:styleguide/Resources/Public/Icons/provider_svg_icon.svg',
    ],
    // Register example Bitmap for icon submodule
    'provider-bitmap' => [
        'provider' => TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
        'source' => 'EXT:styleguide/Resources/Public/Icons/provider_bitmap_icon.png',
    ],
];
