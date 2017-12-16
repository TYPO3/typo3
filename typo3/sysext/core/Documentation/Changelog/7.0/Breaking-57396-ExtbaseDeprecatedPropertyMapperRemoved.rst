
.. include:: ../../Includes.txt

=================================================================
Breaking: #57396 - Deprecated Extbase Property Mapper was removed
=================================================================

See :issue:`57396`

Description
===========

A new Property Mapper that mapped request arguments to controller action arguments
was introduced in Extbase 1.4 and the old one was deprecated at the same time.
Along with the mapping, the validation API has also been changed.
The rewritten property mapper is turned on by default since TYPO3 6.2

Now the old mapping and validation API is completely removed.

Impact
======

Extbase extensions that relied on the internal behaviour of the deprecated property mapper
or make use of the old validation API will stop working or may not work as expected any more.

Affected installations
======================

Extbase extensions that turned off the introduced feature switch with the TypoScript setting
:code:`features.rewrittenPropertyMapper = 0` because they relied on internal behavior of the old property mapper
will stop working.

Migration
=========

Manual migration of extension code might be required, especially when own validators using the old
validation API were used.
