..  include:: /Includes.rst.txt

..  _important-111339-1793809730:

==========================================================================
Important: #111339 - Consider OneToOne/ManyToOne in workspace publishing
==========================================================================

See :issue:`111339`

Description
===========

When publishing a record in workspaces, there were already circumstances in which
referenced/child records were auto-published together with the parent record:

* any soft or hard references from FlexForms
* TCA fields of type `inline` or `file` with relationship type `OneToMany`
* TCA fields of type `inline` or `file` with relationship type `List`

With this change, that list is extended:

* TCA fields of type `inline` or `file` with relationship type `OneToOne`
* TCA fields of type `inline` or `file` with relationship type `ManyToOne`

Impact
======

TYPO3 installations that use workspaces need to be aware that more relationship types
are now affected by automatic dependency publishing. In practice, the impact of this
change is considered minimal because `inline` or `file` referenced are already
expected to belong to the parent record. Also, both `OneToOne` and `ManyToOne`
relations are less often used than other relationship types.

..  index:: Backend, ext:workspaces
