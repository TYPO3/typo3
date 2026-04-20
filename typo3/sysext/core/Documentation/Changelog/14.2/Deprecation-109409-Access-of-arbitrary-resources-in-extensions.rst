..  include:: /Includes.rst.txt

..  _deprecation-109409-1774787352:

==================================================================
Deprecation: #109409 - Access to arbitrary resources in extensions
==================================================================

See :issue:`109409`

Description
===========

Accessing extension resources outside the configured resource
definitions is deprecated.

By default, extension resources are limited to the following paths:

*   :folder:`Configuration`
*   :folder:`Resources/Private`
*   :folder:`Resources/Public`

If a resource identifier references another extension path, that path
must be configured explicitly in :file:`Configuration/Resources.php`.

See :ref:`feature-109409-1774770383` for information on how to
configure resources for extensions.

Impact
======

TYPO3 installations using resource identifiers that reference extension
folders outside :folder:`Configuration`,
:folder:`Resources/Private`, or :folder:`Resources/Public`
will receive a deprecation message when such a resource is resolved.

Every accessed resource must be configured beforehand as described in
:ref:`feature-109409-1774770383`.

Affected installations
======================

TYPO3 installations using resource identifiers that reference extension
folders outside :folder:`Configuration`,
:folder:`Resources/Private`, or :folder:`Resources/Public`.

Migration
=========

Either configure the referenced paths explicitly in
:file:`Configuration/Resources.php`, as described in
:ref:`feature-109409-1774770383`, or move the resources to a path that
is already configured.

..  index:: PHP-API, NotScanned, ext:core
