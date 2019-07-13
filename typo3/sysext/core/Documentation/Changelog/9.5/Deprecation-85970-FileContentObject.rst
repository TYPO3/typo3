.. include:: ../../Includes.txt

=========================================
Deprecation: #85970 - FILE content object
=========================================

See :issue:`85970`

Description
===========

The cObject :typoscript:`FILE` mixed concerns (rendering an image, or dumping file contents), and
rather became low-level since Fluid's rise in rendering Frontend.

:typoscript:`FILE` shows an image if the file is jpg,jpeg,gif,png (but not SVG). If a
different file ending is detected, it will check if the file is less than 1MB, and will get its contents
and output that. If the file is 1 byte bigger, nothing is done.
Fluid offers more flexibility nowadays.

Thus, cObject :typoscript:`FILE` will be removed in TYPO3 v10.


Impact
======

Instances using the TypoScript cObject :typoscript:`FILE` will find PHP :php:`E_USER_DEPRECATED` errors for each usage.


Affected Installations
======================

Instances using the TypoScript cObject :typoscript:`FILE`.


Migration
=========

Use :typoscript:`IMAGE` to show images instead.

For realising :typoscript:`TEMPLATE`, migrate to using :typoscript:`FLUIDTEMPLATE`. It comes with all
the benefits Fluid offers.

However due to the flexibility of custom cObjects, an extension author could just re-implement
the functionality in a custom extension within minutes.
See this example for ext:frontend where the registration resides in :file:`ext_localconf.php`.

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects'] = array_merge(
      $GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects'],
      [
         'FILE' => \TYPO3\CMS\Frontend\ContentObject\FileContentObject::class,
      ],
   );


.. index:: TypoScript, NotScanned
