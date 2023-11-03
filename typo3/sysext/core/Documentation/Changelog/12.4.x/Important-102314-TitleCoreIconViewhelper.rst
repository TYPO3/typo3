.. include:: /Includes.rst.txt

.. _important-102314-1699259952:

=========================================================
Important: #102314 - Add title argument to IconViewhelper
=========================================================

See :issue:`102314`

Description
===========

The `IconViewhelper` in EXT:core has been extended for a new argument `title`.
The new argument allows to set a corresponding title, which will be rendered
as `title` attribute in the icon HTML markup. The `title` attribute will only
be rendered, if explicitly passed. You can also pass an empty string.

This `title` attribute will improve accessibility, since screenreaders can
choose not to ignore aria-hidden elements (e.g. the icons above the page tree),
which is a mode people with low visibility might choose. If a `title` attribute
is missing, a purely technical output will be given, which is very hard to
make sense of.

Example
=======

.. code-block:: html

	<core:icon title="Open actions menu" identifier="actions-menu" />

This will be rendered as:

.. code-block:: html
    <span title="Open actions menu" class="t3js-icon icon icon-size-small icon-state-default icon-actions-menu" data-identifier="actions-menu" aria-hidden="true">
        <span class="icon-markup">
            <img src="/typo3/sysext/core/Resources/Public/Icons/T3Icons/actions/actions-menu.svg" width="16" height="16">
        </span>
    </span>

.. index:: Backend, NotScanned, ext:core
