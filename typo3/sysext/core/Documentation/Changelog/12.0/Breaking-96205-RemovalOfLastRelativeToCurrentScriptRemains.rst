.. include:: /Includes.rst.txt

.. _breaking-96205:

==================================================================
Breaking: #96205 - Removal of last relativeToCurrentScript remains
==================================================================

See :issue:`96205`

Description
===========

Due to the removal of relative paths in the FAL API (:issue:`95027` and
:issue:`96201`) the :php:`$usedPathsRelativeToCurrentScript` argument in
media renderers :php:`render()` method got obsolete. The same applies to
the :php:`$relativeToCurrentScript` argument of :php:`Avatar->getUrl()`.

Therefore, :php:`$usedPathsRelativeToCurrentScript` is removed as last
argument from following PHP class methods:

- :php:`\TYPO3\CMS\Core\Resource\Rendering\AudioTagRenderer->render()`
- :php:`\TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface->render()`
- :php:`\TYPO3\CMS\Core\Resource\Rendering\VideoTagRenderer->render()`
- :php:`\TYPO3\CMS\Core\Resource\Rendering\VimeoRenderer->render()`
- :php:`\TYPO3\CMS\Core\Resource\Rendering\YoutubeRenderer->render()`

Further is :php:`$relativeToCurrentScript` removed as last argument
from :php:`\TYPO3\CMS\Backend\Backend\Avatar->getUrl()`.

Impact
======

Passing the removed argument to one of the mentioned methods does
no longer have any effect.

Affected Installations
======================

Installations, passing the removed argument to one of the mentioned
methods, which is rather unlikely as those methods are usually not
called by extension code directly.

Migration
=========

Remove the corresponding argument from the methods.

.. index:: FAL, PHP-API, FullyScanned, ext:core
