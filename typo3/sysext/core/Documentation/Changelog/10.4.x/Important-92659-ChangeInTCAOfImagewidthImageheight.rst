.. include:: ../../Includes.txt

=============================================================
Important: #92659 - Change in TCA of imagewidth & imageheight
=============================================================

See :issue:`92659`

Description
===========

The TCA of the fields `imagewidth` and `imageheight` of `tt_content` has been simplified. The following options have been removed:

.. code-block:: php

   'max' => 4,
   'range' => [
     'lower' => 0,
   ],

TYPO3 itself shouldn't limit the inputs of an editor by using a number which was large 10 years ago.

If you need a custom range, please provide it in your site package and set it in :file:`Configuration/TCA/Overrides.php` with:

.. code-block:: php

   $GLOBALS['TCA']['tt_content']['columns']['imagewidth']['config']['range']['upper'] = 1999;

.. index:: Backend, TCA, ext:frontend
