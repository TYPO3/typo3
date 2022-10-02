.. include:: /Includes.rst.txt

.. _feature-90919:

======================================================================
Feature: #90919 - Skip translation of overridden form finisher options
======================================================================

See :issue:`90919`

Description
===========

If form finisher options are overridden via FlexForm, they must not be translated
by the :php:`TranslationService`. Otherwise, they would probably be overridden
again by a localization from a translation file.

To address this issue, a new translation option :yaml:`propertiesExcludedFromTranslation`
has been introduced. The option allows skipping all those finisher options whose
option value has been changed within a FlexForm. The translation option is only
respected in :php:`TranslationService::translateFinisherOption()`.

The following example excludes three properties (subject, recipients and format).
That way, the options can only be overridden within a FlexForm but not by
:php:`TranslationService`. The option is automatically generated as soon as
FlexForm overrides are in place. The following syntax is only documented for
completeness. Nonetheless, it can also be written manually into a form definition.

..  code-block:: yaml

    finishers:
      -
        options:
          identifier: EmailToSender
          subject: 'Email to sender'
          recipients:
            recipient@sender.de: 'recipient@sender name'
          translation:
            propertiesExcludedFromTranslation:
              - subject
              - recipients
              - format

Impact
======

The translation order is as follows:

1. Default value from form definition
2. Overridden value within a FlexForm (if any)
3. Localized value provided by translation files (if any)

With the new translation option, the last (third) step can be skipped. That way,
the FlexForm value will be preferred.

.. index:: Frontend, FlexForm, ext:form
