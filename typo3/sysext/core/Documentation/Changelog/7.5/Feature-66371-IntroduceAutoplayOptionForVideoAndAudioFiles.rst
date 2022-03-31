
.. include:: /Includes.rst.txt

=====================================================================
Feature: #66371 - Introduce autoplay option for video and audio files
=====================================================================

See :issue:`66371`

Description
===========

The `RenderingRegistry` added with #61800 introduced the option to render video
and audio tags with the new `MediaViewHelper` added with #66366.
To improve the usability of this feature an autoplay checkbox has been added to
the `sys_file_reference` records to enable the editor to configure this option
on a per file basis.

To make the autoplay option available in sys_file_reference records, make use of
the new palettes `videoOverlayPalette` and `audioOverlayPalette` in your TCA.

However, the autoplay property of the `sys_file_reference` is only taken into
account if the view helper does not explicitly specify an autoplay option.

Examples:
---------

Example config of an sys_file_reference field in TCA:

.. code-block:: php

	'media' => array(
		'exclude' => 1,
		'label' => 'Media',
		'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
			'media',
			array(
				'foreign_types' => array(
					\TYPO3\CMS\Core\Resource\File::FILETYPE_AUDIO => array(
						'showitem' => '
							--palette--;;audioOverlayPalette,
							--palette--;;filePalette',
					),
					\TYPO3\CMS\Core\Resource\File::FILETYPE_VIDEO => array(
						'showitem' => '
							--palette--;;videoOverlayPalette,
							--palette--;;filePalette',
					)
				)
			),
			'wav,mpeg,mp4,ogg'
		)
	)


.. code-block:: html

	<code title="MP4 Video Object with autoplay option set regardless of sys_file_reference checkbox">
		<f:media file="{file}" width="400" height="375" additionalConfig="{autoplay: '1'}" />
	</code>
	<output>
		<video width="400" height="375" controls autoplay><source src="fileadmin/user_upload/my-video.mp4" type="video/mp4"></video>
	</output>

	 <code title="MP4 Video Object without autoplay option set will respect the configuration of the sys_file_reference record">
		<f:media file="{file}" width="400" height="375" />
	</code>
	<output>
		<video width="400" height="375" controls><source src="fileadmin/user_upload/my-video.mp4" type="video/mp4"></video>
	</output>


.. index:: FAL, TCA, Backend, Frontend
