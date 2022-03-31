.. include:: /Includes.rst.txt

==================================================================================
Feature: #94623 - tt_content images, assets, media showPossibleLocalizationRecords
==================================================================================

See :issue:`94623`

Description
===========

When a default language content element is localized to another language
in "connected" / "translation" mode (as opposed to "copy"), relations like
images and assets connected to the default language record are localized as well.

When the default language element is later changed and additional images, assets
or media relations are added, the localized content element now shows those new
default language relations as shadowed box and allows to localize them with one click.


Impact
======

This is a usability improvement for editors, who now see which tt_content
relations of casual elements like "Image" and "Media" are missing when
editing localizations. They can localize those with one click.

.. index:: Backend, TCA, ext:backend
