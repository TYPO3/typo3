.. include:: /Includes.rst.txt

=====================================================
Important: #73227 - TSconfig option altIcons restored
=====================================================

See :issue:`73227`

Description
===========

The TSconfig option :typoscript:`altIcons`, introduced in :issue:`35891`,
allowed to add / override icons for TCA select items. This option was then
accidentally removed without further notice, while reworking the FormEngine.

Therefore and because it's sometimes necessary to use different icons for
already defined select items - depending on the current page or site context -
the option is restored.

The usage is as following:

.. code-block:: typoscript

   TCEFORM.pages.doktype.altIcons {
      1 = custom-icon-identifier
      2 = EXT:my_ext/path/to/icon.svg
   }

For more information you can also have a look at the initial
:doc:`changelog <../7.1/Feature-35891-AddTCAItemsWithIconsViaPageTSConfig>`.

.. index:: Backend, TSConfig, ext:backend
