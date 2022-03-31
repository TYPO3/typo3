.. include:: /Includes.rst.txt

==========================================================
Feature: #82706 - Render fieldset labels in form templates
==========================================================

See :issue:`82706`

Description
===========

The section element :yaml:`Fieldset` is now accessible in templates of the "form" extension and can be used to add more structure.

By default this affects the :yaml:`SummaryPage` form element as well as the :yaml:`EmailToReceiver` / :yaml:`EmailToSender` finishers.

A common use case are two fieldsets for a delivery and a billing address where the fields within the fieldset are usually named the same. Till now, the default mail sent by the "form" extension would e.g. show "Street" twice without giving a hint about the context. Now the fieldset label is rendered in between to separate those fields.


Impact
======

The summary page of the "form" extension and mails now show fieldset labels as separators.

Custom templates have access to the fieldset element for rendering.

.. index:: Frontend, ext:form
