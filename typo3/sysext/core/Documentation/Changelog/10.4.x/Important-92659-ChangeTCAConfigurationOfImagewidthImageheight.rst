.. include:: /Includes.rst.txt

========================================================================
Important: #92659 - Change TCA configuration of imagewidth & imageheight
========================================================================

See :issue:`92659`

Description
===========

The TCA configuration for `tt_content` fields `imagewidth` and `imageheight` has
been simplified. Therefore, the following options have been removed from these
fields:

.. code-block:: php

   'max' => 4,
   'range' => [
     'upper' => 1999
   ]

TYPO3 itself shouldn't limit the inputs of an editor by using a number which
was assumend to be large, 10 years ago.

If you rely on these options, please provide it in your site package by defining
it in :file:`Configuration/TCA/Overrides/tt_content.php`:

.. code-block:: php

   $GLOBALS['TCA']['tt_content']['columns']['imagewidth']['config']['max'] = 4;
   $GLOBALS['TCA']['tt_content']['columns']['imagewidth']['config']['range']['upper'] = 1999;

   $GLOBALS['TCA']['tt_content']['columns']['imageheight']['config']['max'] = 4;
   $GLOBALS['TCA']['tt_content']['columns']['imageheight']['config']['range']['upper'] = 1999;

.. index:: Backend, TCA, ext:frontend
