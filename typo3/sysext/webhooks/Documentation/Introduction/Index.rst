..  include:: /Includes.rst.txt

.. _introduction:

============
Introduction
============

A webhook is an automated message sent from one application to another via HTTP.

This extension adds the possibility to configure webhooks in TYPO3.

The backend module :guilabel:`Administration > Integrations > Webhooks` provides the possibility to
configure webhooks. The module is available in the TYPO3 backend for users with
administrative rights.

A webhook is defined as an authorized POST or GET request to a defined URL.
For example, a webhook can be used to send a notification to a Slack channel
when a new page is created in TYPO3.

Any webhook record is defined by a unique uid (UUID), a speaking name, an optional
description, a trigger, the target URL and a signing-secret.
Both the unique identifier and the signing-secret are generated in the backend
when a new webhook is created.

Click the copy button next to the secret field to copy the generated secret to
the clipboard. The button is available until the webhook is saved. The
receiving system needs the secret to
:ref:`verify the signature <hash-calculation>` of a request.

..  versionadded:: 15.0
    :changelog: feature-110851-1790717834

    The button to copy the secret to the clipboard was added.
