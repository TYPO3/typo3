.. include:: ../../Includes.txt

=========================================================================================
Breaking: #82252 - Override TypoScript configuration formDefinitionOverrides by FlexForms
=========================================================================================

See :issue:`82252`

Description
===========

Override TypoScript configuration formDefinitionOverrides by FlexForms configuration.


Impact
======

Before this patch, your FlexForm configuration of form was overriden by TypoScript formDefinitionOverrides,
which is not the intended behaviour. With this patch, the FlexForm configuration overrides the
TypoScript configuration of the formDefinitionOverrides. This means, if you have a configuration of
form in FlexForms and TypoScript, your form will change its behavior and start using the configuration
defined in your FlexForm.


Affected Installations
======================

All instalations using the form framework and TypoScript/ FlexForm overrides are affected.

.. index:: ext:form, FlexForm, TypoScript, NotScanned