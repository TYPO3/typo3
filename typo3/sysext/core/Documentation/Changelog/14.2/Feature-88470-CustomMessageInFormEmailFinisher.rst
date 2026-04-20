..  include:: /Includes.rst.txt

..  _feature-88470-1771684874:

=========================================================
Feature: #88470 - Custom message in form email finisher
=========================================================

See :issue:`88470`

Description
===========

The email finishers `EmailToSender` and `EmailToReceiver` in the TYPO3 form
framework now support an optional custom message field. This allows editors
to add personalized text to the email sent by the form, either before or
after the submitted form values.

The message field supports rich text editing using the `form-content` RTE
preset, which provides formatting options such as bold, italic, links, and
lists.

A special placeholder `{formValues}` can be used inside the message to control
where the submitted form data table is rendered. If the placeholder is omitted,
only the custom message is shown and the form values table is hidden.

The message field is available in:

*   The form editor backend module
*   The form finisher override settings

Impact
======

Editors can now configure a custom message for email finishers directly in the
form editor or via finisher overrides in the form plugin. This provides more
flexibility in crafting email notifications without the need for custom Fluid
templates.

..  index:: Frontend, Backend, ext:form
