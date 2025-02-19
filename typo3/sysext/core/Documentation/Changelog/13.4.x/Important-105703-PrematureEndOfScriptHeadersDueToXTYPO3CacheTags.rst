..  include:: /Includes.rst.txt

..  _important-105703-1742970227:

==========================================================================
Important: #105703 - Premature end of script headers due to X-TYPO3-Cache-Tags
==========================================================================

See :issue:`105703`

Description
===========

The `X-TYPO3-Cache-Tags` header is now split into multiple lines if it exceeds the maximum
of 8000 characters. This change prevents premature end of script headers and ensures
that the header is sent correctly, even if it contains a large number of cache tags.

Affected installations
----------------------

This change affects all TYPO3 installations that have `$GLOBALS['TYPO3_CONF_VARS']['FE']['debug']`
enabled and misusing the `X-TYPO3-Cache-Tags` header for anything else then debugging.
If you have a large number of cache tags, the header is now split into multiple
lines to avoid exceeding the maximum header size limit imposed by some web servers.
As this header is for debugging purposes only, this does not effect any production
environments.

..  index:: Backend, ext:core
