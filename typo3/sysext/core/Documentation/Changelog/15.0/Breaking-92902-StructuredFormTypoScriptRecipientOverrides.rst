..  include:: /Includes.rst.txt

..  _breaking-92902-1788434591:

=================================================================
Breaking: #92902 - Structured Form TypoScript recipient overrides
=================================================================

See :issue:`92902`

Description
===========

Form TypoScript overrides for email finisher recipient options now use structured
list entries. Email addresses are values instead of TypoScript identifiers.

Impact
======

TypoScript configurations using an email address as the key of a recipient map
are no longer supported. Recipient options configured through TypoScript replace
the complete existing list instead of being recursively merged with it.

Affected installations
======================

Installations using :typoscript:`plugin.tx_form.settings.formDefinitionOverrides`
for the :typoscript:`recipients`, :typoscript:`carbonCopyRecipients`,
:typoscript:`blindCarbonCopyRecipients` or :typoscript:`replyToRecipients`
options are affected.

Migration
=========

The same migration applies to the :typoscript:`recipients`,
:typoscript:`carbonCopyRecipients`, :typoscript:`blindCarbonCopyRecipients` and
:typoscript:`replyToRecipients` options. For example, change the following
configuration:

..  code-block:: typoscript

    recipients {
      user@example.org = User
    }

to a structured list entry:

..  code-block:: typoscript

    recipients {
      10 {
        email = user@example.org
        name = User
      }
    }

The numeric key is an arbitrary list identifier. The complete desired list must
be specified because an override replaces the existing list. Dynamic TypoScript
values can be used for :typoscript:`email`, for example
:typoscript:`email.data = TSFE : fe_user|user|email`.

..  index:: Backend, Frontend, ext:form, FullyScanned
