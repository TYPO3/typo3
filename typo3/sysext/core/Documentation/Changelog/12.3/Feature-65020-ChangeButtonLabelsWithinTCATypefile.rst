.. include:: /Includes.rst.txt

.. _feature-65020-1679498591:

===========================================================
Feature: #65020 - Change button labels within TCA type=file
===========================================================

See :issue:`65020`

Description
===========

When working with file references (:sql:`sys_file_reference` records) within FormEngine,
there are up to three buttons available:

* "Create new relation"
* "Select & upload files"
* "Add media by URL"

Whereas the first button text can be changed via TCA on a per-field basis via
:php:`[config][appearance][createNewRelationLinkTitle] = 'LLL:my_extension/...';`
the two other label fields are hard-coded. It is especially useful to override such a label
when only a certain type of media is required (for example, just images) or online media of type YouTube.

It is now possible to do so by using two new TCA configuration settings for TCA type=file

* :php:`[config][appearance][uploadFilesLinkTitle]`
* :php:`[config][appearance][addMediaLinkTitle]`


Impact
======

An extension author can now completely modify the label texts of all buttons.

.. index:: TCA, ext:backend
