..  include:: /Includes.rst.txt
..  _concepts-finishers-emailfinisher:

==============
Email finisher
==============

The EmailFinisher sends an email to one recipient. EXT:form uses two
EmailFinisher declarations with the identifiers EmailToReceiver and EmailToSender.

..  include:: /Includes/_NoteFinisher.rst

..  contents:: Table of contents

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
