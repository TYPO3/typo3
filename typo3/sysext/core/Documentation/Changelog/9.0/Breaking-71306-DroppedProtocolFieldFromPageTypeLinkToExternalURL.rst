.. include:: /Includes.rst.txt

=================================================================================
Breaking: #71306 - Dropped "Protocol" field from page type "Link to external URL"
=================================================================================

See :issue:`71306`

Description
===========

When selecting the page type "External Url" the option to select the protocol / URL scheme for the
external target has been dropped, and is now added directly to the external target field.

Previously it was possible to select between "http://", "https://", "ftp://" and "mailto:" or no
prefix. As this sets confusion for editors and also makes it more complicated when pasting an
external URL directly, the field is removed.

The according PHP functionality has been removed:

* The TCA definition for "pages.urltype" and "pages_language_overlay.urltype" has been removed.
* The according database fields is not populated anymore, and will be removed when using the Database
  Scheme Migrations in the install tool. For new installations the fields are not created anymore.
* The public PHP class property :php:`PageRepository->urltypes` has been removed.


Impact
======

Editing a page record or page translation record of type "External URL" in the TYPO3 Backend will
not include the "Protocol" field is anymore. Instead, the "URL" field will contain the full target
including the scheme part of a URL.

Accessing the public property will throw a PHP-internal warning message.


Affected Installations
======================

TYPO3 extensions making use of the "urltype" fields or TCA values for custom targets or evaluating
the urltype field in a separate functionality like "custom external redirects".

Migration
=========

The existing data is migrated via an Install Tool wizard to have all external URLs behave the same
as before.

The "pages.url" field will now contain the full URL target with scheme.

.. index:: Backend, Database, Frontend, PHP-API, TCA, PartiallyScanned
