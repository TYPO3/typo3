..  include:: /Includes.rst.txt

..  _important-111343-1793811746:

====================================================================================
Important: #111343 - Provide more context in IsReferenceConsideredForDependencyEvent
====================================================================================

See :issue:`111343`

Description
===========

When publishing or changing states in workspaces, dependencies of the selected
records are resolved and auto-published together with the record. The
:php-short:`\TYPO3\CMS\Workspaces\Event\IsReferenceConsideredForDependencyEvent`
is used by TYPO3 Core to define which related records are considered a dependency.
That same event can also be used to alter TYPO3's default behavior.

In order to determine the proper dependency status of a relation, certain context
information was missing from the event. This data has now been added as
additional getters to the event:

*   :php:`getFlexPointer()` allows event listeners to gain more information
    about references from flexform fields
*   :php:`getSoftReferenceKey()` and :php:`getSoftReferenceId()` give more context
    to soft references (e. g. links in RTEs)
*   :php:`getDirection()` lets event listeners differentiate between parents
    and children resolving by the
    :php-short:`\TYPO3\CMS\Workspaces\Dependency\DependencyResolver`

..  index:: Backend, PHP-API, ext:workspaces
