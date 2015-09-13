=================================================================================================================
Deprecation: #69754 - Deprecate relative path to extension directory and using filename only in TCA ctrl iconfile
=================================================================================================================

Description
===========

* Using relative paths to refer to the extension directory for iconfiles in ``TCA['ctrl']['iconfile']`` has been deprecated.
* Using filenames only to refer to an iconfile in TCA['ctrl'] has been deprecated.


Impact
======

* TCA definitions in ``TCA['ctrl']['iconfile']`` containing ``'../typo3conf/ext/'`` or calls to ``\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath()`` will trigger a message in the deprecation log.
* TCA definitions in ``TCA['ctrl']['iconfile']`` containing a filename only will trigger a message in the deprecation log.


Affected Installations
======================

Any installation with extensions defining ``TCA['ctrl']['iconfile']`` by using ``../typo3conf/ext/`` or  a filename only.


Migration
=========

Relative paths
--------------

Use ``EXT:`` instead of relative path ``'../typo3conf/ext/'`` or ``\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath()``, e.g.

.. code-block:: php

	'ctrl' => array(
		'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('my_extension') . 'Resources/Public/Icons/image.png'
	),

has to be migrated to

.. code-block:: php

	'ctrl' => array(
		'iconfile' => 'EXT:my_extension/Resources/Public/Icons/image.png'
	),

File name only
--------------

Use a full absolute path or an ``EXT:`` definition instead of a filename only:

.. code-block:: php

	'ctrl' => array(
		'iconfile' => '_icon_ftp.gif'
	),

has to be migrated to

.. code-block:: php

	'ctrl' => array(
		'iconfile' => 'EXT:t3skin/icons/gfx/i/_icon_ftp.gif'
	),

or

.. code-block:: php

	'ctrl' => array(
		'iconfile' => '/fileadmin/icons/_icon_ftp.gif'
	),
