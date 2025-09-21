..  include:: /Includes.rst.txt
..  _concepts-finishers-emailfinisher:

==============
Email finisher
==============

The EmailFinisher sends an email to one recipient. EXT:form uses two
EmailFinisher declarations with the identifiers EmailToReceiver and EmailToSender.

..  contents:: Table of contents

..  include:: /Includes/_NoteFinisher.rst


..  _concepts-finishers-emailfinisher-backend:
..  _finishers-email-to-sender:
..  _finishers-email-to-receiver:

Email finisher usage in the backend form
========================================

The backend form offers two email finishers to the editor:

Email to sender (form submitter)
    This finisher sends an email to the form submitter - i.e. the user - with the
    contents of the form.

Email to receiver (you)
    This finisher sends an email to the receiver - you as the owner of the
    website - with the contents of the form. The settings of the finisher are the
    same as for the finisher "Email to sender"

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
    is used to send the email it should always be an email address allowed by the
    SMTP server. Use `replyToRecipients` if you want to enable the receiver to
    easily reply to the message.

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

    If set, mails will contain a plaintext and HTML part, otherwise only a
    plaintext part. That way, it can be used to disable HTML and enforce
    plaintext-only mails.

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

    The title, being shown in the email.

..  _apireference-finisheroptions-emailfinisher-options-translation-language:

..  confval:: Translation language [translation.language]
    :name: emailfinisher-translation-language
    :type: string
    :required: false
    :default: `undefined`

    If not set, the finisher options are translated depending on the current
    frontend language (if translations exist). This option allows you to force
    translations for a given language isocode, e.g. `da` or `de`.
    Read :ref:`Translate finisher options<concepts-frontendrendering-translation-finishers>`.

..  _apireference-finisheroptions-emailfinisher-options-options:

Additional options of the email finisher
========================================

These additional options can be set directly in the form definition YAML or
programmatically in the options array but **not** from the backend editor:

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

Redirect finisher in the YAML form definition
=============================================

This finisher sends an email to one recipient.
EXT:form uses 2 EmailFinisher declarations with the identifiers
`EmailToReceiver` and `EmailToSender`.

..  literalinclude:: _codesnippets/_form.yaml
    :caption: public/fileadmin/forms/my_form.yaml

..  _apireference-finisheroptions-emailfinisher:

Usage of the Email finisher in PHP code
=======================================

Developers can create a confirmation finisher by using the key `EmailToReceiver`
or `EmailToSender`.

..  literalinclude:: _codesnippets/_finisher.php.inc
    :language: php

This finisher is implemented in :php:`TYPO3\CMS\Form\Domain\Finishers\EmailFinisher`.

..  _concepts-finishers-emailfinisher-bcc-recipients:

Working with BCC recipients
===========================

Both email finishers support different recipient types, including Carbon Copy
(CC) and Blind Carbon Copy (BCC). Depending on the configuration of the server
and the TYPO3 instance, it may not be possible to send emails to BCC recipients.
The configuration of the :php:`$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_sendmail_command']`
value is crucial. As documented in :ref:`CORE API <t3coreapi:mail-configuration-sendmail>`,
TYPO3 recommends the parameter :php:`-bs` (instead of :php:`-t -i`) when using
:php:`sendmail`. The parameter :php:`-bs` tells TYPO3 to use the SMTP standard
and that way the BCC recipients are properly set. `Symfony <https://symfony.com/doc/current/mailer.html#using-built-in-transports>`__
refers to the problem of using the :php:`-t` parameter as well. Since TYPO3 7.5
(`#65791 <https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/7.5/Feature-65791-UsePHPConfiguredSendmailPathIfMAILtransportSendmailIsActive.html>`__)
the :php:`transport_sendmail_command` is automatically set from the PHP runtime
configuration and saved. Thus, if you have problems with sending emails to BCC
recipients, check the above mentioned configuration.

..  _concepts-finishers-emailfinisher-fluidemail:

About FluidEmail
================

..  versionchanged:: 12.0
    The :php:`EmailFinisher` always sends email via :php:`FluidEmail`.

FluidEmail allows to send mails in a standardized way.

The option :yaml:`title` is available which can
be used to add an email title to the default FluidEmail template. This option is
capable of rendering form element variables using the known bracket syntax and can
be overwritten in the FlexForm configuration of the form plugin.

To customize the templates being used following options can be set:

*   :yaml:`templateName`: The template name (for both HTML and plaintext) without the
    extension
*   :yaml:`templateRootPaths`: The paths to the templates
*   :yaml:`partialRootPaths`: The paths to the partials
*   :yaml:`layoutRootPaths`: The paths to the layouts

..  note::
    The formerly available field :yaml:`templatePathAndFilename` is not evaluated
    anymore.

A finisher configuration could look like this:

..  literalinclude:: _codesnippets/_example-email.yaml
    :caption: public/fileadmin/forms/my_form_with_email_finisher.yaml

In the example above the following files must exist in the specified
template path:

*   :file:`EXT:my_site_package/Resources/Private/Templates/Email/ContactForm.html`
*   :file:`EXT:my_site_package/Resources/Private/Templates/Email/ContactForm.txt`
