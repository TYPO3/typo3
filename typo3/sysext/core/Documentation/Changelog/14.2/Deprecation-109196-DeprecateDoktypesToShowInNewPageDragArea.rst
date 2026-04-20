..  include:: /Includes.rst.txt

..  _deprecation-109196-1742122800:

==============================================================================
Deprecation: #109196 - Deprecate doktypesToShowInNewPageDragArea user TSconfig
==============================================================================

See :issue:`109196`

Description
===========

The user TSconfig option
:tsconfig:`options.pageTree.doktypesToShowInNewPageDragArea`
has been deprecated and will be removed in TYPO3 v15.0.

The page tree toolbar submenu now automatically determines available doktypes
based on the user's group permissions. Manual TSconfig configuration is no
longer needed.

Impact
======

Using the deprecated user TSconfig option triggers a deprecation-level log
entry and will stop working in TYPO3 v15.0.

Affected installations
======================

TYPO3 installations that set
:tsconfig:`options.pageTree.doktypesToShowInNewPageDragArea` in their user
TSconfig.

Migration
=========

Remove the :tsconfig:`options.pageTree.doktypesToShowInNewPageDragArea` option
from your user TSconfig. The page tree toolbar will then display all
doktypes that the current backend user is allowed to create based on their
group permissions.

..  index:: Backend, TSConfig, NotScanned, ext:backend
