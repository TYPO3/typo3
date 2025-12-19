..  include:: /Includes.rst.txt
..  _concepts-finishers-emailfinisher:

==============
Email finisher
==============

The EmailFinisher sends an email to one recipient. EXT:form has two
EmailFinishers with the identifiers EmailToReceiver and EmailToSender.

..  contents:: Table of contents

..  include:: /Includes/_NoteFinisher.rst


..  _concepts-finishers-emailfinisher-backend:
..  _finishers-email-to-sender:
..  _finishers-email-to-receiver:

Using email finishers in the backend form editor
================================================

Editors can use two email finishers in the backend form editor:

Email to sender (form submitter)
    This finisher sends an email with the contents of the form to the user
    submitting the form .

Email to receiver (you)
    This finisher sends an email with the contents of the form to the owner of the
    website. The settings of this finisher are the
    same as the "Email to sender" finisher

..  _apireference-finisheroptions-emailfinisher-options:

Options of the email finisher
=============================

..  _apireference-finisheroptions-emailfinisher-options-subject:

..  confval:: Subject [subject]
    :name: emailfinisher-subject
    :type: string
    :required: true

    Subject of the email.

..  _apireference-finisheroptions-emailfinisher-options-recipients:

..  confval:: Recipients [recipients]
    :name: emailfinisher-recipients
    :type: array
    :required: true

    Email addresses and names of the recipients (To).

    **Email Address**
        Email address of a recipient, e.g. "some.recipient@example.com"
        or "{email-1}".
    **Name**
        Name of a recipient, e.g. "Some Recipient" or "{text-1}".

..  _apireference-finisheroptions-emailfinisher-options-senderaddress:

..  confval:: Sender address [senderAddress]
    :name: emailfinisher-senderAddress
    :type: string
    :required: true

    Email address of the sender, for example "your.company@example.org".

    If `smtp <https://docs.typo3.org/permalink/t3coreapi:mail-configuration-smtp>`_
    is used, this email address needs to be allowed by the
    SMTP server. Use `replyToRecipients` if you want to enable the receiver to
    reply to the message.

..  _apireference-finisheroptions-emailfinisher-options-sendername:

..  confval:: Sender name [senderName]
    :name: emailfinisher-senderName
    :type: string
    :default: `''`

    Name of the sender, for example "Your Company".

..  _apireference-finisheroptions-emailfinisher-options-replytorecipients:

..  confval:: Reply-to Recipients [replyToRecipients]
    :name: emailfinisher-replyToRecipients
    :type: array
    :default: `[]`

    Email address which will be used when someone replies to the email.

    **Email Address**:
        Email address for reply-to.
    **Name**
        Name for reply-to.

..  _apireference-finisheroptions-emailfinisher-options-carboncopyrecipients:

..  confval:: CC Recipient [carbonCopyRecipients]
    :name: emailfinisher-carbonCopyRecipients
    :type: array
    :default: `[]`

    Email address to which a copy of the email is sent. The information is
    visible to all other recipients.

    **Email Address**:
        Email address for CC.
    **Name**
        Name for CC.

..  _apireference-finisheroptions-emailfinisher-options-blindcarboncopyrecipients:

..  confval:: BCC Recipients [blindCarbonCopyRecipients]
    :name: emailfinisher-blindCarbonCopyRecipients
    :type: array
    :default: `[]`

    Email address to which a copy of the email is sent. The information is not
    visible to any of the recipients.

    **Email Address**:
        Email address for BCC.
    **Name**
        Name for BCC.

..  _apireference-finisheroptions-emailfinisher-options-addhtmlpart:

..  confval:: Add HTML part [addHtmlPart]
    :name: emailfinisher-addHtmlPart
    :type: bool
    :default: `true`

    If set, emails will contain plaintext and HTML, otherwise only plaintext.
    In this way, HTML can be disabled and plaintext-only emails enforced.

..  _apireference-finisheroptions-emailfinisher-options-attachuploads:

..  confval:: Attach uploads [attachUploads]
    :name: emailfinisher-attachUploads
    :type: bool
    :default: `true`

    If set, all uploaded items are attached to the email.

..  _apireference-finisheroptions-emailfinisher-options-title:

..  confval:: Title [title]
    :name: emailfinisher-title
    :type: string
    :required: false
    :default: `undefined`

    The title shown in the email.

..  _apireference-finisheroptions-emailfinisher-options-translation-language:

..  confval:: Translation language [translation.language]
    :name: emailfinisher-translation-language
    :type: string
    :required: false
    :default: `undefined`

    If not set, the finisher options are translated depending on the current
    frontend language (if translations exist). This option allows you to force
    translations for a given language isocode, e.g. `da` or `de`.
    See :ref:`Translate finisher options<concepts-frontendrendering-translation-finishers>`.

..  _apireference-finisheroptions-emailfinisher-options-options:

Additional email finisher options
=================================

Additional options can be set in the form definition YAML and
programmatically in the options array but **not** in the backend editor:

..  _apireference-finisheroptions-emailfinisher-options-translation-propertiesExcludedFromTranslation:

..  confval:: Properties excluded from translation [translation.propertiesExcludedFromTranslation]
    :name: emailfinisher-translation-propertiesExcludedFromTranslation
    :type: array
    :required: false
    :default: `undefined`

    If not set, the finisher options are translated depending on the current frontend language (if translations exists).
    This option allows you to force translations for a given language isocode, e.g 'da' or 'de'.
    See :ref:`Translate finisher options<concepts-frontendrendering-translation-finishers>`.
    It will be skipped for all specified finisher options.

..  _apireference-finisheroptions-emailfinisher-options-translation-translationfiles:

..  confval:: translation.translationFiles
    :name: emailfinisher-translation-translationFiles
    :type: array
    :required: false
    :default: `undefined`

    If set, this translation file(s) will be used for finisher option
    translations. If not set, the translation file(s) from the `Form` element
    will be used.
    Read :ref:`Translate finisher options<concepts-frontendrendering-translation-finishers>`.

..  _apireference-finisheroptions-emailfinisher-options-layoutrootpaths:

..  confval:: layoutRootPaths
    :name: emailfinisher-layoutRootPaths
    :type: array
    :required: false
    :default: `undefined`

    Fluid layout paths.

..  _apireference-finisheroptions-emailfinisher-options-partialrootpaths:

..  confval:: partialRootPaths
    :name: emailfinisher-partialRootPaths
    :type: array
    :required: false
    :default: `undefined`

    Fluid partial paths.

..  _apireference-finisheroptions-emailfinisher-options-templaterootpaths:

..  confval:: templateRootPaths
    :name: emailfinisher-templateRootPaths
    :type: array
    :required: false
    :default: `undefined`

    Fluid template paths; all templates get the current :php:`FormRuntime`
    assigned as :code:`form` and the :php:`FinisherVariableProvider` assigned
    as :code:`finisherVariableProvider`.

..  _apireference-finisheroptions-emailfinisher-options-variables:

..  confval:: variables
    :name: emailfinisher-variables
    :type: array
    :required: false
    :default: `undefined`

    Associative array of variables which are available inside the Fluid template.

..  _concepts-finishers-emailfinisher-yaml:

Email finishers in the YAML form definition
===========================================

This finisher sends an email to one recipient.
EXT:form has two email finishers with identifiers
`EmailToReceiver` and `EmailToSender`.

..  literalinclude:: _codesnippets/_form.yaml
    :caption: public/fileadmin/forms/my_form.yaml

..  _apireference-finisheroptions-emailfinisher:

Using Email finishers in PHP code
=================================

Developers can create a confirmation finisher by using the key `EmailToReceiver`
or `EmailToSender`.

..  literalinclude:: _codesnippets/_finisher.php.inc
    :language: php

This finisher is implemented in :php:`TYPO3\CMS\Form\Domain\Finishers\EmailFinisher`.

..  _concepts-finishers-emailfinisher-bcc-recipients:

Working with BCC recipients
===========================

Email finishers can work with different recipient types, including Carbon Copy
(CC) and Blind Carbon Copy (BCC). Depending on the configuration of your server
and TYPO3 instance, it may not be possible to send emails to BCC recipients.
The :php:`$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_sendmail_command']`
configuration value is important here. As documented in :ref:`CORE API <t3coreapi:mail-configuration-sendmail>`,
TYPO3 recommends using the parameter :php:`-bs` (instead of :php:`-t -i`) with
:php:`sendmail`. The parameter :php:`-bs` tells TYPO3 to use the SMTP standard
so that BCC recipients are properly set. `Symfony <https://symfony.com/doc/current/mailer.html#using-built-in-transports>`__
also mentions the :php:`-t` parameter problem. Since TYPO3 7.5
(`#65791 <https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/7.5/Feature-65791-UsePHPConfiguredSendmailPathIfMAILtransportSendmailIsActive.html>`__)
the :php:`transport_sendmail_command` is automatically set from the PHP runtime
configuration and saved. If you have problems sending emails to BCC
recipients, this could be the solution.

..  _concepts-finishers-emailfinisher-fluidemail:

About FluidEmail
================

..  versionchanged:: 12.0
    The :php:`EmailFinisher` always sends email via :php:`FluidEmail`.

The FluidEmail finisher allows emails to be sent in a standardized way.

The finisher has an :yaml:`option` property :yaml:`title` that adds an email title to the default
FluidEmail template. Variables can be used in options using the bracket syntax.
These variables can be overwritten by FlexForm configuration in the form plugin

Use these options to customize the fluid templates:

*   :yaml:`templateName`: The template name (for both HTML and plaintext, without the
    extension)
*   :yaml:`templateRootPaths`: The paths to the templates
*   :yaml:`partialRootPaths`: The paths to the partials
*   :yaml:`layoutRootPaths`: The paths to the layouts

..  note::
    The field :yaml:`templatePathAndFilename` is no longer evaluated.

Here is an example finisher configuration:

..  literalinclude:: _codesnippets/_example-email.yaml
    :caption: public/fileadmin/forms/my_form_with_email_finisher.yaml

These template files must exist:

*   :file:`EXT:my_site_package/Resources/Private/Templates/Email/ContactForm.html`
*   :file:`EXT:my_site_package/Resources/Private/Templates/Email/ContactForm.txt`
