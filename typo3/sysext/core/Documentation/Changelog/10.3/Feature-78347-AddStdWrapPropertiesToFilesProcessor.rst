.. include:: /Includes.rst.txt

==========================================================
Feature: #78347 - Add StdWrap properties to FilesProcessor
==========================================================

See :issue:`78347`

Description
===========

StdWrap properties have been added to FLUIDTEMPLATEs FilesProcessor the same way as in FilesContentObject.
That way you can implement slide-functionality on rootline for file resources.


TypoScript dataProcessing example with FilesProcessor
-----------------------------------------------------

.. code-block:: typoscript

   page.10 = FLUIDTEMPLATE
   page.10.dataProcessing {
     10 = TYPO3\CMS\Frontend\DataProcessing\FilesProcessor
     10 {
      references.data = levelmedia: -1, slide
      as = myfiles
   }


Impact
======

The FilesProcessor can slide up and down the rootline to collect images for FLUID templates.
One usual feature is to use images attached to pages and use them up and down the page tree
for header images in frontend.


.. index:: TypoScript, Frontend
