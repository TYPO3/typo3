..  include:: /Includes.rst.txt

..  _important-107328-1756815543:

======================================================
Important: #107328 - $GLOBALS['TCA'] in base TCA files
======================================================

See :issue:`107328`

Description
===========

The backward compatibility for using :php:`$GLOBALS['TCA']` in base TCA files
has been removed. Base TCA files are the first TCA files loaded by the Core and
do not have the fully loaded TCA available yet. Until now, this worked because
the Core temporarily populated this global array during the loading process.

Note that the usage of :php:`$GLOBALS['TCA']` in base TCA files was never
explicitly allowed or disallowed, only discouraged. It worked only due to
internal system knowledge by the user, for example, knowing that the loading
order of extensions affects when those files are loaded. The only place this
array should be used is inside TCA/Overrides/*.php files. For all other cases,
the :php:`TcaSchema` should be preferred.

Migration
=========

In the uncommon case that you find usages of :php:`$GLOBALS['TCA']` in base TCA
files, move that access to TCA/Overrides/*.php files instead.

..  index:: TCA, ext:core
