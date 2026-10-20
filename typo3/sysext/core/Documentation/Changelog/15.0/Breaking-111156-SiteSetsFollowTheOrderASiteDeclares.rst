..  include:: /Includes.rst.txt

..  _breaking-111156-1792483200:

==============================================================
Breaking: #111156 - Site sets follow the order a site declares
==============================================================

See :issue:`111156`

Description
===========

The sets a site lists in its :yaml:`dependencies` are now applied in the order
the site lists them, each of them preceded by the sets it depends on. A set
reached more than once, because several sets depend on it, keeps its first
position.

So far, the order came from one global ordering of every installed set. Sets
with no dependency relation between them were ordered by the number of
dependencies they declare and then by name, and the order the site listed them
in had no effect. Installing an extension whose set declares one more
dependency could therefore change which of two unrelated sets wins, on sites
that do not use that extension's set at all.

:php:`\TYPO3\CMS\Core\Site\Set\SetRegistry::getSets()` returns the sets in the
order described above. All consumers of the set order follow it, which are:

*   TypoScript setup and constants
*   page TSconfig
*   site settings, their definitions and their categories
*   route enhancers

The protected method :php:`SetRegistry::hasDependency()` has been removed.

Impact
======

On a site listing two sets without a dependency relation between them, the set
listed last now wins: its TypoScript, page TSconfig, settings and route
enhancers override those of the set listed before it. A set still always comes
after the sets it depends on, so a set that depends on another one keeps
overriding it, wherever the site lists them.

Sites where the previous order happened to match the order of their
:yaml:`dependencies` are not affected.

Affected installations
======================

Installations with sites listing several sets, where two of those sets, or the
sets they depend on, configure the same TypoScript, page TSconfig, setting or
route enhancer without one of them depending on the other.

A common case is a theme or site package set that replaces
:typoscript:`lib.contentElement`, listed before a set of another extension that
depends on :yaml:`typo3/fluid-styled-content`. That set of
:yaml:`typo3/fluid-styled-content` is now applied after the theme and replaces
its :typoscript:`lib.contentElement`, while it may have been applied before the
theme so far.

Extensions extending :php:`SetRegistry` and calling
:php:`hasDependency()` are affected as well.

Migration
=========

List the sets of a site in the order they are meant to override each other,
the set that is meant to win last:

..  code-block:: yaml
    :caption: config/sites/my-site/config.yaml

    dependencies:
      - vendor/some-extension
      - myvendor/my-theme

Alternatively, declare the dependency between the sets explicitly in the
:file:`config.yaml` of the set that is meant to win, which makes the order
independent of any site.

..  index:: Frontend, Backend, YAML, PartiallyScanned, ext:core
