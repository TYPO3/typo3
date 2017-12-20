
.. include:: ../../Includes.txt

================================================
Breaking: #67753 - Drop "Show secondary options"
================================================

See :issue:`67753`

Description
===========

When editing records, the checkbox at the bottom "Show secondary options (palettes)" has been dropped, palettes are now
always shown and the collapse buttons are no longer rendered.


Impact
======


PageTSconfig
------------

Setting `options.enableShowPalettes` has no effect anymore and can be removed from `PageTSconfig`.


TCA
---

Setting `canNotCollapse` in `ctrl` and `palettes` section are obsolete and can be dropped:

.. code-block:: php

	$GLOBALS['TCA']['aTable']['ctrl']['canNotCollapse'] = 1; // Obsolete
	$GLOBALS['TCA']['aTable']['palettes']['aPaletteName']['canNotCollapse'] = 1; // Obsolete


PHP
---

The following method has been dropped. If an extension calls it, a PHP fatal error will be thrown.
This was an internal method and external usage is unlikely:

.. code-block:: php

	\TYPO3\CMS\Backend\Controller\EditDocumentController->functionMenus()


The following properties have been dropped, calling those may trigger a PHP warning level error, external usage is unlikely:

.. code-block:: php

	\TYPO3\CMS\Backend\Controller\EditDocumentController->MOD_MENU
	\TYPO3\CMS\Backend\Controller\EditDocumentController->MOD_SETTINGS
	\TYPO3\CMS\Backend\Form\FormEngine->palettesCollapsed


Affected Installations
======================

In the rare case that an extension uses one of the above methods or properties, a fatal PHP error may be triggered.


Migration
=========

The above properties can be dropped, the `PageTS` and `TCA` settings have no effect anymore.


.. index:: TCA, TSConfig, PHP-API, Backend
