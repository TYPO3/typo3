.. include:: /Includes.rst.txt

============
Introduction
============

This system extension adds the possibility to receive webhooks in TYPO3.

It offers a backend module, :guilabel:`System > Reactions`, that can be used
to configure reactions triggered by a webhook.

A webhook is defined as an authorized `POST` request to the TYPO3 backend.

The Core provides a basic default reaction that can be used to create
records triggered and enriched by data from the caller.

Additionally, the Core provides the :php:`\TYPO3\CMS\Reactions\Reaction\ReactionInterface`
to allow extension authors to add their own reaction types.

Any reaction record is defined by a unique uid and also requires a secret. Both
information are generated in the backend. The secret is only visible once and
stored in the database as an encrypted value like a backend user password.

Next to static field values, the :guilabel:`create record` reaction features placeholders,
which can be used to dynamically set field values by resolving the incoming
data from the webhook's payload. The syntax for those values is :code:`${key}`.
The key can be a simple string or a path to a nested value like :code:`${key.nested}`.
