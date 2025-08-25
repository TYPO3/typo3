..  include:: /Includes.rst.txt

..  _important-107328-1756815543:

======================================================
Important: #107328 - $GLOBALS['TCA'] in base TCA files
======================================================

See :issue:`107328`

Description
===========

The backwards compatibility for usage of :php:`$GLOBALS['TCA']` in base TCA files
is removed. Base TCA files are the first TCA files loaded by Core and don't have
have the fully loaded TCA yet. As until now this worked, because Core temporarily
populated this global array in the loading process.

Note that the usage of :php:`$GLOBALS['TCA']` in base TCA files was never explicitly
allowed nor disallowed, merely discouraged. It only worked, because of internal
knowledge of the system by the user. For example that the loading order of extensions
is used to load those files. The only place this array should be used is inside
TCA/Overrides/*.php files. For all other cases the :php:`TcaSchema` shall be preferred.

Migration
=========

In the uncommon case you do find usages of :php:`$GLOBALS['TCA']` in base TCA files,
consider moving the access to TCA/Overrides/*.php files instead.

..  index:: TCA, ext:core
