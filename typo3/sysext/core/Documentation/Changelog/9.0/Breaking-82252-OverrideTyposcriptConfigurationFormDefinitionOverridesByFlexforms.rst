.. include:: /Includes.rst.txt

=========================================================================================
Breaking: #82252 - Override TypoScript configuration formDefinitionOverrides by FlexForms
=========================================================================================

See :issue:`82252`

Description
===========

Override TypoScript configuration :typoscript:`formDefinitionOverrides` by FlexForms configuration.


Impact
======

Before this, FlexForm configuration of form was overridden by TypoScript :typoscript:`formDefinitionOverrides`,
which is not the intended behaviour. Now the FlexForm configuration overrides the
TypoScript configuration of the :typoscript:`formDefinitionOverrides`. This means, having a configuration of
form in FlexForms and TypoScript, the form will change its behavior and start using the configuration
defined in your FlexForm.


Affected Installations
======================

All installations using the form framework and TypoScript/ FlexForm overrides are affected.

.. index:: ext:form, FlexForm, TypoScript, NotScanned
