..  include:: /Includes.rst.txt

============
Introduction
============

This system extension adds the possibility to receive webhooks in TYPO3.

A webhook is defined as an authorized POST request to the TYPO3 backend.

It offers a backend module, :guilabel:`System > Reactions`, that can be used
to configure reactions triggered by a webhook.

The extensions provides a basic default reaction that can be used to
:ref:`create database records <create-database-record>` triggered and enriched
by data from the caller.

Additionally, the Core provides the
:php:`\TYPO3\CMS\Reactions\Reaction\ReactionInterface`
to allow extension authors to add their own reaction types.

Any reaction record is defined by a unique identifier and also requires a
secret. Both information are generated in the backend. The secret is only
visible once and stored in the database as an encrypted value like a backend
user password.
