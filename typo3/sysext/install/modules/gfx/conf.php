<?php
$GLOBALS['MCA']['gfx'] = array (
	'general' => array (
		'title' => 'module_gfx_title',
	),
	
	'options' => array (
		/** GENERAL **/
	
		'image_processing' => array (
			'categoryMain' => 'gfx',
			'categorySub' => 'general',
			'tags' => array (),
			'elementType' => 'checkbox',
			'value' => 'LC:GFX/image_processing',
			'default' => 1
		),
		'thumbnails' => array (
			'categoryMain' => 'gfx',
			'categorySub' => 'general',
			'tags' => array (),
			'elementType' => 'checkbox',
			'value' => 'LC:GFX/thumbnails',
			'default' => 1
		),
		'thumbnails_png' => array (
			'categoryMain' => 'gfx',
			'categorySub' => 'general',
			'tags' => array (),
			'elementType' => 'input',
			'value' => 'LC:GFX/thumbnails_png',
			'default' => '0'
		),
		'noIconProc' => array (
			'categoryMain' => 'gfx',
			'categorySub' => 'general',
			'tags' => array (),
			'elementType' => 'checkbox',
			'value' => 'LC:GFX/noIconProc',
			'default' => 1
		),
		'gif_compress' => array (
			'categoryMain' => 'gfx',
			'categorySub' => 'general',
			'tags' => array (),
			'elementType' => 'checkbox',
			'value' => 'LC:GFX/gif_compress',
			'default' => 1
		),
		'imagefile_ext' => array (
			'categoryMain' => 'gfx',
			'categorySub' => 'general',
			'tags' => array (),
			'elementType' => 'input',
			'value' => 'LC:GFX/imagefile_ext',
			'size' => 40,
			'default' => 'gif,jpg,jpeg,tif,bmp,pcx,tga,png,pdf,ai'
		),
		
		/** GD **/
		
		'gdlib' => array (
			'categoryMain' => 'gfx',
			'categorySub' => 'gd',
			'tags' => array (),
			'elementType' => 'checkbox',
			'value' => 'LC:GFX/gdlib',
			'default' => 1
		),
		'gdlib_png' => array (
			'categoryMain' => 'gfx',
			'categorySub' => 'gd',
			'tags' => array (),
			'elementType' => 'checkbox',
			'value' => 'LC:GFX/gdlib_png',
			'default' => 0
		),
		'gdlib_2' => array (
			'categoryMain' => 'gfx',
			'categorySub' => 'gd',
			'tags' => array (),
			'elementType' => 'checkbox',
			'value' => 'LC:GFX/gdlib_2',
			'default' => 0
		),

		
		/** Image Graphics-Magick **/
		'im' => array (
			'categoryMain' => 'gfx',
			'categorySub' => 'igm',
			'tags' => array (),
			'elementType' => 'checkbox',
			'value' => 'LC:GFX/im',
			'default' => 1
		),
		'im_path' => array (
			'categoryMain' => 'gfx',
			'categorySub' => 'igm',
			'tags' => array (),
			'elementType' => 'input',
			'value' => 'LC:GFX/im_path',
			'default' => '/usr/bin/'
		),
		'im_path_lzw' => array (
			'categoryMain' => 'gfx',
			'categorySub' => 'igm',
			'tags' => array (),
			'elementType' => 'input',
			'value' => 'LC:GFX/im_path_lzw',
			'default' => '/usr/bin/'
		),
		'im_version_5' => array (
			'categoryMain' => 'gfx',
			'categorySub' => 'igm',
			'tags' => array (),
			'elementType' => 'input',
			'value' => 'LC:GFX/im_version_5',
			'default' => ''
		),
		'im_negate_mask' => array (
			'categoryMain' => 'gfx',
			'categorySub' => 'igm',
			'tags' => array (),
			'elementType' => 'checkbox',
			'value' => 'LC:GFX/im_negate_mask',
			'default' => 0
		),
		'im_imvMaskState' => array (
			'categoryMain' => 'gfx',
			'categorySub' => 'igm',
			'tags' => array (),
			'elementType' => 'checkbox',
			'value' => 'LC:GFX/im_imvMaskState',
			'default' => 0
		),
		'im_no_effects' => array (
			'categoryMain' => 'gfx',
			'categorySub' => 'igm',
			'tags' => array (),
			'elementType' => 'checkbox',
			'value' => 'LC:GFX/im_no_effects',
			'default' => 0
		),
		'im_v5effects' => array (
			'categoryMain' => 'gfx',
			'categorySub' => 'igm',
			'tags' => array (),
			'elementType' => 'input',
			'value' => 'LC:GFX/im_v5effects',
			'default' => 0
		),
		'im_mask_temp_ext_gif' => array (
			'categoryMain' => 'gfx',
			'categorySub' => 'igm',
			'tags' => array (),
			'elementType' => 'checkbox',
			'value' => 'LC:GFX/im_mask_temp_ext_gif',
			'default' => 0
		),
		'im_mask_temp_ext_noloss' => array (
			'categoryMain' => 'gfx',
			'categorySub' => 'igm',
			'tags' => array (),
			'elementType' => 'input',
			'value' => 'LC:GFX/im_mask_temp_ext_noloss',
			'default' => 'miff'
		),
		'im_noScaleUp' => array (
			'categoryMain' => 'gfx',
			'categorySub' => 'igm',
			'tags' => array (),
			'elementType' => 'checkbox',
			'value' => 'LC:GFX/im_noScaleUp',
			'default' => 0
		),
		'im_combine_filename' => array (
			'categoryMain' => 'gfx',
			'categorySub' => 'igm',
			'tags' => array (),
			'elementType' => 'input',
			'value' => 'LC:GFX/im_combine_filename',
			'default' => 'composite'
		),
		'im_noFramePrepended' => array (
			'categoryMain' => 'gfx',
			'categorySub' => 'igm',
			'tags' => array (),
			'elementType' => 'checkbox',
			'value' => 'LC:GFX/im_noFramePrepended',
			'default' => 0
		),
		
		
		/** QUALITY **/
		'jpg_quality' => array (
			'categoryMain' => 'gfx',
			'categorySub' => 'quality',
			'tags' => array (),
			'elementType' => 'input',
			'value' => 'LC:GFX/jpg_quality',
			'valueType' => 'integer',
			'default' => 70
		),
		'enable_typo3temp_db_tracking' => array (
			'categoryMain' => 'gfx',
			'categorySub' => 'quality',
			'tags' => array (),
			'elementType' => 'checkbox',
			'value' => 'LC:GFX/enable_typo3temp_db_tracking',
			'default' => 0
		),
		'TTFLocaleConv' => array (
			'categoryMain' => 'gfx',
			'categorySub' => 'quality',
			'tags' => array (),
			'elementType' => 'input',
			'value' => 'LC:GFX/TTFLocaleConv',
			'default' => ''
		),
		'TTFdpi' => array (
			'categoryMain' => 'gfx',
			'categorySub' => 'quality',
			'tags' => array (),
			'elementType' => 'input',
			'value' => 'LC:GFX/TTFdpi',
			'default' => '72'
		),
		'png_truecolor' => array (
			'categoryMain' => 'gfx',
			'categorySub' => 'quality',
			'tags' => array (),
			'elementType' => 'checkbox',
			'value' => 'LC:GFX/png_truecolor',
			'default' => 0
		),
	),
	
	/*
	'checks' => array (
		'version' => array (
			'title' => 'module_php_check_version_title',
			'description' => 'module_php_check_version_description',
			'categoryMain' => 'server',
			'categorySub' => 'php',
			'method' => 'php:checkVersion'
		)
	),
	*/
	
	'methods' => array (
		'gfxOverview' => array (
			'categoryMain' => 'gfx',
			'categorySub' => 'checks',
			'method' => 'gfx:overview',
			'autostart' => true
		)
	)
);
?>
