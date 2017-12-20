
.. include:: ../../Includes.txt

================================================================================
Feature: #69543 - Introduced $GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext']
================================================================================

See :issue:`69543`

Description
===========

Now we got the `RendererRegistry` with the `VideoTagRenderer`, `AudioTagRenderer` and `MediaViewHelper` in the
core we needed also a way to define a list of file extensions of the files that can be handled by these. This list
can then be used in the TCA for allowing sys_file_references to these files.

.. code-block:: php

	// Comma list of file extensions perceived as media files by TYPO3.
	// Lowercase and no spaces between
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext'] = 'gif,jpg,jpeg,bmp,png,pdf,svg,ai,mov,avi';


TCA example:
------------

.. code-block:: php

	'media' => array(
		'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.media',
		'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig('media', array(
			'foreign_types' => array(
				'0' => array(
					'showitem' => '
						--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
						--palette--;;filePalette'
				),
				\TYPO3\CMS\Core\Resource\File::FILETYPE_TEXT => array(
					'showitem' => '
						--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
						--palette--;;filePalette'
				),
				\TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => array(
					'showitem' => '
						--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
						--palette--;;filePalette'
				),
				\TYPO3\CMS\Core\Resource\File::FILETYPE_AUDIO => array(
					'showitem' => '
						--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
						--palette--;;filePalette'
				),
				\TYPO3\CMS\Core\Resource\File::FILETYPE_VIDEO => array(
					'showitem' => '
						--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
						--palette--;;filePalette'
				),
				\TYPO3\CMS\Core\Resource\File::FILETYPE_APPLICATION => array(
					'showitem' => '
						--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
						--palette--;;filePalette'
				)
			)
		), $GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext'])
	),


Extending this list:
--------------------

If you want to extend this list you can add the desired extension name to list in the `ext_localconf.php` of your extension.

.. code-block:: php

	$GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext'] .= ',myext';


.. index:: TCA, Backend, LocalConfiguration
