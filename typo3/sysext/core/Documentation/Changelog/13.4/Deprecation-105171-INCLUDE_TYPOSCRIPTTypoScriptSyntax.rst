.. include:: /Includes.rst.txt

.. _deprecation-105171-1727785626:

===========================================================
Deprecation: #105171 - INCLUDE_TYPOSCRIPT TypoScript syntax
===========================================================

See :issue:`105171`

Description
===========

The old TypoScript syntax to import external TypoScript files based on
:typoscript:`<INCLUDE_TYPOSCRIPT:` has been marked as deprecated
with TYPO3 v13 and will be removed in v14.

Integrators should switch to :typoscript:`@import`.

There are multiple reasons to finally phase out this old construct:

* The :typoscript:`<INCLUDE_TYPOSCRIPT:` syntax is clumsy and hard to grasp,
  especially when combining multiple options. It is hard to learn for integrators
  new to the project.
* The implementation has a high level of complexity, is only partially tested
  and consists of edge cases that are hard to decide on and even harder to change
  since that may break existing usages in hard to debug ways.
* The syntax can have negative security impact when not used wisely by loading
  TypoScript from editor related folders like :file:`fileadmin/`. The syntax
  based on :typoscript:`@import` has been designed more thoughtful in this regard.
* Loading TypoScript files from folders relative to the public web folder is
  unfortunate and can have negative side effects when switching "legacy" based
  instances to composer.
* The syntax based on :typoscript:`@import` has been introduced in TYPO3 v9 already
  and :quote:`has been designed to stay` at this point in time. With TYPO3 v12, the
  last missing feature of :typoscript:`<INCLUDE_TYPOSCRIPT:` has been made possible
  for :typoscript:`@import` as well: :typoscript:`@import` can be loaded conditionally
  by putting them into the body of a casual TypoScript condition.
* TYPO3 v12 discouraged using :typoscript:`<INCLUDE_TYPOSCRIPT:` within the documentation
  and already anticipated a future deprecation and removal.

Impact
======

When using TypoScript :typoscript:`<INCLUDE_TYPOSCRIPT:` syntax a deprecation level
log entry in TYPO3 v13 is emitted. The syntax will stop working with TYPO3 v14 and
will be detected as an "invalid line" in the TypoScript related Backend modules.


Affected installations
======================

Instances TypoScript syntax based on :typoscript:`<INCLUDE_TYPOSCRIPT:`.


Migration
=========

Most usages of :typoscript:`<INCLUDE_TYPOSCRIPT:` can be turned into :typoscript:`@import`
easily. A few examples:

.. code-block:: typoscript

    # Before
    <INCLUDE_TYPOSCRIPT: source="FILE:EXT:my_extension/Configuration/TypoScript/myMenu.typoscript">
    # After
    @import 'EXT:my_extension/Configuration/TypoScript/myMenu.typoscript'

    # Before
    # Including .typoscript files in a single (non recursive!) directory
    <INCLUDE_TYPOSCRIPT: source="DIR:EXT:my_extension/Configuration/TypoScript/" extensions="typoscript">
    # After
    @import 'EXT:my_extension/Configuration/TypoScript/*.typoscript'

    # Before
    # Including .typoscript and .ts files in a single (non recursive!) directory
    <INCLUDE_TYPOSCRIPT: source="DIR:EXT:my_extension/Configuration/TypoScript/" extensions="typoscript,ts">
    # After
    @import 'EXT:my_extension/Configuration/TypoScript/*.typoscript'
    @import 'EXT:my_extension/Configuration/TypoScript/*.ts'

    # Before
    # Including a file conditionally
    <INCLUDE_TYPOSCRIPT: source="FILE:EXT:my_extension/Configuration/TypoScript/user.typoscript" condition="[frontend.user.isLoggedIn]">
    # After
    [frontend.user.isLoggedIn]
        @import 'EXT:my_extension/Configuration/TypoScript/user.typoscript'
    [END]

There are a few more use cases that can't be transitioned so easily since :typoscript:`@import` is
a bit more restrictive.

As one restriction :typoscript:`@import` can't include files from arbitrary directories
like :file:`fileadmin/`, but only from extensions by using the :typoscript:`EXT:`
prefix. Instances that use :typoscript:`<INCLUDE_TYPOSCRIPT:` with :typoscript:`source="FILE:./someDirectory/..."`
should move this typoscript into a project or site extension. Such instances are also encouraged to
look into the TYPO3 v13 "Site sets" feature and eventually transition towards it along the way.

Secondly, :typoscript:`@import` does not allow recursive directory includes by not allowing
wildcards in directory segments. Having directories like :file:`TypoScript/foo` and :file:`TypoScript/bar`, each
having :file:`.typoscript` files, could be included using
:typoscript:`<INCLUDE_TYPOSCRIPT: source=DIR:EXT:my_extension/Configuration/TypoScript extensions="typoscript">`,
which would find such files in :file:`foo` and :file:`bar`, and any other directory. This level of complexity
was not wished to allow in the :typoscript:`@import` syntax since it can make file includes more intransparent
with too much attached magic. Instances using this should either reorganize their files, or have multiple dedicated
:typoscript:`@import` statements. The need for recursive includes may also be mitigated by restructuring
TypoScript based functionality using "Site sets".

The transition from :typoscript:`<INCLUDE_TYPOSCRIPT:` can be often further relaxed with these features in mind:

* :ref:`Automatic inclusion of user TSconfig of extensions <feature-101807-1693473782>`
* :ref:`Automatic inclusion of page TSconfig of extensions <feature-96614>`
* :ref:`TypoScript provider for sites and sets <feature-103439-1712321631>` automatically loads TypoScript
  per site when located next to site :file:`config.yaml` files as :file:`constants.typoscript` and :file:`setup.typoscript`,
  which is a good alternative to files in :file:`fileadmin` and similar. Note that configured site sets TypoScript
  are loaded before, so :file:`constants.typoscript` and :file:`setup.typoscript` are designed to adapt site set
  TypoScript to specific site needs.


.. index:: TypoScript, NotScanned, ext:core
