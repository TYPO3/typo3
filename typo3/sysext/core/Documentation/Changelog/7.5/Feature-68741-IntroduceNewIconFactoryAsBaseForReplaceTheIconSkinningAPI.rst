
.. include:: ../../Includes.txt

====================================================================================
Feature: #68741 - Introduce new IconFactory as base to replace the icon skinning API
====================================================================================

See :issue:`68741`

Description
===========

The logic for working with icons, icon sizes and icon overlays is now bundled into the new `IconFactory` class.
The new icon factory will replace the old icon skinning API step by step.

All core icons will be registered directly in the `IconRegistry` class, third party extensions must use
`IconRegistry::registerIcon()` to override existing icons or add additional icons to the icon factory.

The `IconFactory` takes care of the correct icon and overlay size and the markup.


IconProvider
------------

The core implements three icon provider classes, which all implement the `IconProviderInterface`.

* `BitmapIconProvider` for all kind of bitmap icons for gif, png and jpg files
* `FontawesomeIconProvider` for font icons from fontawesome.io
* `SvgIconProvider` for svg icons

Third party extensions can provide own icon provider classes, each class must implement the `IconProviderInterface`.


BitmapIconProvider
------------------

The `BitmapIconProvider` has the following option

* `source` The path to the bitmap file, this may also contain the EXT: prefix


FontawesomeIconProvider
-----------------------

The `FontawesomeIconProvider` has the following option

* `name` The name of the icon without the icon prefix e.g. `check` instead of `fa-check`


SvgIconProvider
---------------

The `SvgIconProvider` has the following option

* `source` The path to the svg file, this may also contains the EXT: prefix


Register an icon
----------------

.. code-block:: php

	/*
	 * Put the following code into your ext_localconf.php file of your extension.
	 *
	 * @param string $identifier the icon identifier
	 * @param string $iconProviderClassName the icon provider class name
	 * @param array $options provider specific options, please reference the icon provider class
	 */
	$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
	$iconRegistry->registerIcon($identifier, $iconProviderClassName, array $options = array());


Use an icon
-----------

To use an icon, you need at least the icon identifier. The default size is currently 32x32 px.
The third parameter can be used to add an additional icon as overlay, which can be any registered icon.

The `Icon` class provides only the following constants for Icon sizes:

* `Icon::SIZE_SMALL` which currently means 16x16 px
* `Icon::SIZE_DEFAULT` which currently means 32x32 px
* `Icon::SIZE_LARGE` which currently means 48x48 px

All the sizes can change in future, so please make use of the constants for an unified layout.

.. code-block:: php

	$iconFactory = GeneralUtility::makeInstance(IconFactory::class);
	$iconFactory->getIcon($identifier, Icon::SIZE_SMALL, $overlay)->render();


ViewHelper
----------

The core provides a fluid ViewHelper which makes it really easy to use icons within a fluid view.

.. code-block:: html

	{namespace core = TYPO3\CMS\Core\ViewHelpers}
	<core:icon identifier="my-icon-identifier" />
	<!-- use the "small" size if none given ->
	<core:icon identifier="my-icon-identifier" />
	<core:icon identifier="my-icon-identifier" size="large" />
	<core:icon identifier="my-icon-identifier" overlay="overlay-identifier" />
	<core:icon identifier="my-icon-identifier" size="default" overlay="overlay-identifier" />
	<core:icon identifier="my-icon-identifier" size="large" overlay="overlay-identifier" />


Impact
======

No impact


.. index:: PHP-API, Backend, Fluid
