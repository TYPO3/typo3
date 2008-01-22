<?php
/**
 * 
 */
class tx_install_module_gfx	extends tx_install_module_base	{
	
	/**
	 * This returns an overview over the current gfx settings.
	 *
	 * @return XHTML
	 */
	public function overview() {
		/**
		 * ATTENTION! This ist just a copy and paste from old installer!!! Please use this as inspiration. ;-)
		 */
		$im_path = $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path'];
		if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_version_5'] == 'gm')	{
			$im_path_version = $this->config_array['im_versions'][$im_path]['gm'];
		} else {
			$im_path_version = $this->config_array['im_versions'][$im_path]['convert'];
		}
		
		$im_path_lzw = $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path_lzw'];
		$im_path_lzw_version = $this->config_array['im_versions'][$im_path_lzw]['convert'];
		
		$msg = '
		ImageMagick enabled: <strong>'.$GLOBALS['TYPO3_CONF_VARS']['GFX']['im'].'</strong>
		ImageMagick path: <strong>'.$im_path.'</strong> ('.$im_path_version.')
		ImageMagick path/LZW: <strong>'.$im_path_lzw.'</strong>  ('.$im_path_lzw_version.')
		Version 5/GraphicsMagick flag: <strong>'.$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_version_5'].'</strong>

		GDLib enabled: <strong>'.$GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib'].'</strong>
		GDLib using PNG: <strong>'.$GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png'].'</strong>
		GDLib 2 enabled: <strong>'.$GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_2'].'</strong>
		IM5 effects enabled: <strong>'.$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_v5effects'].'</strong> (Blurring/Sharpening with IM 5+)
		Freetype DPI: <strong>'.$GLOBALS['TYPO3_CONF_VARS']['GFX']['TTFdpi'].'</strong> (Should be 96 for Freetype 2)
		Mask invert: <strong>'.$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_imvMaskState'].'</strong> (Should be set for some IM versions approx. 5.4+)

		File Formats: <strong>'.$GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'].'</strong>
		';
		
		return $msg;
	}
	
	/**
	 * Checks with image formats can be read.
	 *
	 * @return unknown
	 */
	public function readingImageFormats() {
		return 'readingImageFormats';	
	}
	
	/**
	 * Tests if GIF and PNG files can be written
	 *
	 * @return unknown
	 */
	public function writingGIFandPNG() {
		return 'writingGIFandPNG';
	}
	
	/**
	 * Tests if images will be scaled correctly
	 *
	 * @return unknown
	 */
	public function scalingImages() {
		return 'scalingImages';
	}
	
	/**
	 * Tests if combining images works.
	 *
	 * @return unknown
	 */
	public function combiningImages() {
		return 'combiningImages';
	}
	
	/**
	 * Tests the GD library function like FreeType etc.
	 *
	 * @return unknown
	 */
	public function gdLibraryFunctions() {
		return 'gdLibraryFunctions';
	}
}

?>
