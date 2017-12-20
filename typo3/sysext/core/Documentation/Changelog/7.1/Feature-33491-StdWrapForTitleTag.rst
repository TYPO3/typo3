
.. include:: ../../Includes.txt

==========================================================
Feature: #33491 - Add stdWrap functionality to <title> tag
==========================================================

See :issue:`33491`

Description
===========

The <title> tag of a frontend page can already be controlled by various settings via TypoScript. However, the stdWrap
part was not available yet, but is now compliant and available as an option `config.pageTitle`. This option will be
executed after all other existing processing options like `config.titleTagFunction` and `config.pageTitleFirst`.

The new option can be used like this, e.g. in order to write everything in uppercase in the title tag:

.. code-block:: typoscript

	page = PAGE
	page.config.pageTitle.case = upper


.. index:: TypoScript, Frontend
