.. include:: /Includes.rst.txt

=============================================================
Feature: #80420 - Allow multiple recipients in email finisher
=============================================================

See :issue:`80420`

Description
===========

Mails sent by the :php:`EmailFinisher` of EXT:form can now have multiple recipients. For this the following new finisher options have been added:

* :yaml:`recipients` (:code:`To`)
* :yaml:`replyToRecipients` (:code:`Reply-To`)
* :yaml:`carbonCopyRecipients` (:code:`CC`)
* :yaml:`blindCarbonCopyRecipients` (:code:`BCC`)

These options must contain a YAML hash with email addresses as keys and recipient names as values:

.. code-block:: yaml

   recipients:
     first@example.org: First Recipient
     second@example.org: Second Recipient

Additionally this now allows for setting the name of a CC and BCC recipient:

.. code-block:: yaml

   carbonCopyRecipients:
     firstCC@example.org: First CC Recipient

The form editor in the backend module provides a visual UI to enter an arbitrary amount of recipients.


Impact
======

Mails sent by EXT:form can be sent to multiple recipients, optionally via CC or BCC. Replies can be sent to multiple recipients.

.. index:: ext:form
