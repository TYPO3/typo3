
.. include:: ../../Includes.txt

===================================================================
Feature: #67950 - Move CE table options from flexform to tt_content
===================================================================

See :issue:`67950`

Description
===========

The CE table (processing) configuration

* `Table caption`
* `Field delimiter`
* `Text enclosure`
* `Table header position`
* `Use table footer`

were in EXT:css_styled_content configured/saved in a flexform. This has now been moved to regular database fields.


Impact
======

When EXT:css_styled_content isn't installed a Migration wizard is shown in the install tool to move the flexform values
to regular database fields in the tt_content table.


.. index:: FlexForm, Backend, ext:css_styled_content
