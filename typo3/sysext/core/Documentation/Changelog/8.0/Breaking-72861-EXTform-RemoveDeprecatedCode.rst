
.. include:: /Includes.rst.txt

====================================================
Breaking: #72861 - EXT:form - Remove deprecated code
====================================================

See :issue:`72861`

Description
===========

The compatibility layer of EXT:form has been removed. The layer made sure that
EXT:form  acts almost like in TYPO3 6.2. This mainly applies to the layout
configuration and rendering. In the former days integrators could use `.layout`
settings on different levels to change the output of the form elements in the
frontend. Nowadays, changing the frontend output is only possible by utilizing own
fluid templates.

Furthermore the SELECT, TEXTAREA and TEXTBLOCK elements have been adjusted. The
automatic transformation of the `.data` (SELECT, TEXTAREA) and `.content`
(TEXTBLOCK) attribute has been removed. That way `.text` is the only valid
attribute for adding a human readable text to the above mentioned elements.


Impact
======

Using `.layout` will have no effect anymore.

Using the `.data` attribute for SELECT, TEXTAREA and `.content` for TEXTBLOCK
elements will also have no effect.


Affected Installations
======================

Any installation using `.layout` and/ or `.data` and/ or `.content` settings.
Most of the older installations (mainly 6.2 LTS) will be affected when upgrading to
8 LTS.


Migration
=========

All `.layout` settings have to be removed and ported to own fluid templates.

All occurrences of the `.data` and `.content` attribute have to be substituted
by `.text`.

.. index:: TypoScript, ext:form
