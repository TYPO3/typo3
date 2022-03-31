.. include:: /Includes.rst.txt

=================================================================
Feature: #90728 - Add FluidEmail option to EXT:form EmailFinisher
=================================================================

See :issue:`90728`

Description
===========

After the introduction of FluidEmail in v10 the option to send mails in a
standardized way is now also added to the EmailFinisher of the system extension
EXT:from.

To use FluidEmail a new option `useFluidEmail` is added to both the EmailToReceiver
and EmailToSender finisher. It defaults to :php:`FALSE` so extension authors are
able to smoothly test and upgrade their forms. Furthermore a new option `title`
is available which can be used to add an E-Mail title to the default FluidEmail
template. This option is capable of rendering form element variables using the
known bracket syntax and can be overwritten in the FlexForm configuration of the
form plugin.

To customize the templates beeing used following options can be set:

* `templateName`: The template name (for both HTML and plaintext) without the extension
* `templateRootPaths`: The paths to the templates
* `partialRootPaths`: The paths to the partials
* `layoutRootPaths`: The paths to the layouts

For FluidEmail, the field `templatePathAndFilename` is not evaluated anymore.

A finisher configuration could look like this:

.. code-block:: yaml

   identifier: contact
   type: Form
   prototypeName: standard
   finishers:
   -
      identifier: EmailToSender
      options:
         subject: 'Your Message: {message}'
         title: 'Hello {name}, your confirmation'
         templateName: ContactForm
         templateRootPaths:
            100: 'EXT:sitepackage/Resources/Private/Templates/Email/'
         partialRootPaths:
            100: 'EXT:sitepackage/Resources/Private/Partials/Email/'
         addHtmlPart: true
         useFluidEmail: true

Please note that the old template name syntax `{@format}.html` does not work for
FluidEmail as each format needs a different template with the corresponing file
extension. In the example above the following files must exist in the specified
template path:

* `ContactForm.html`
* `ContactForm.txt`

Impact
======

It's now possible to use FluidEmail for sending mails in EXT:form.

.. index:: Fluid, Frontend, ext:form
