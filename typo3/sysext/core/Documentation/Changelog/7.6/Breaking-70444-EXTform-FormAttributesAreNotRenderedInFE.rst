
.. include:: ../../Includes.txt

====================================================================
Breaking: #70444 - EXT:form - Form attributes are not rendered in FE
====================================================================

See :issue:`70444`

Description
===========

The TypoScript configuration of EXT:form has been streamlined. Useless
attributes for the specific form elements have been removed.
Additionally, missing attributes have been added.
Furthermore, the array notation of `htmlAttributes` and
`htmlAttributesUsedByTheViewHelperDirectly` has changed.
The whole cleanup was done to provide a solid configuration for the LTS
version.


Impact
======

The removed attributes will not be available anymore out of the box for
the specific form element.
Custom TypoScript which copied, referenced or removed certain attribute
configurations will not work anymore.


Affected Installations
======================

Any installation that relies on the structure of `htmlAttributes` and
`htmlAttributesUsedByTheViewHelperDirectly`.
Since the whole configuration has not been documented yet and the
functionality has been introduced with 7.5 the possibility that a lot of
installations customize the configuration is very low.


Migration
=========

Affected installations have to re-add the missing attributes manually
and adopt the new array notation.


.. index:: Frontend, ext:form
