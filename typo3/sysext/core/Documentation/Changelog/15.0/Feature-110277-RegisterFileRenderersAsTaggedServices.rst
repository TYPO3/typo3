..  include:: /Includes.rst.txt

..  _feature-110277-1784812454:

=============================================================
Feature: #110277 - Register file renderers as tagged services
=============================================================

See :issue:`110277`

Description
===========

File renderers, used for example by the :html:`<f:media>` ViewHelper to
render audio, video or online media files, are now registered as tagged
services via dependency injection instead of the previous programmatic
registration through
:php:`\TYPO3\CMS\Core\Resource\Rendering\RendererRegistry->registerRendererClass()`
in :file:`ext_localconf.php`.

A file renderer class implementing
:php:`\TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface` is
registered by adding the new PHP attribute
:php:`\TYPO3\CMS\Core\Attribute\AsFileRenderer` to the class:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Resource/Rendering/MyVideoRenderer.php

    use TYPO3\CMS\Core\Attribute\AsFileRenderer;
    use TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface;

    #[AsFileRenderer(priority: 10)]
    final class MyVideoRenderer implements FileRendererInterface
    {
        // ...
    }

The renderer priority is defined at registration time via the attribute.
Renderers with a higher priority are asked first whether they can render
a given file (:php:`canRender()`). The file renderers shipped with TYPO3
Core are registered with the default priority :php:`0` (previously
:php:`1` via the removed :php:`getPriority()` method), so any custom
renderer using a priority above :php:`0` takes precedence over them. The
order in which renderers of the same priority are evaluated is not
defined and must not be relied upon; use distinct priorities if the
evaluation order matters.

Alternatively, the service tag :yaml:`fal.file_renderer` can be used
directly in :file:`Configuration/Services.yaml`:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    MyVendor\MyExtension\Resource\Rendering\MyVideoRenderer:
      tags:
        - name: fal.file_renderer
          priority: 10

Impact
======

Registering file renderers as tagged services has the following
benefits over the previous programmatic registration:

-   The registration is resolved once at container compile time instead
    of executing registration code from :file:`ext_localconf.php` on
    every request. Loading and validating the renderer classes per
    request, as well as instantiating and sorting them by priority at
    runtime, is not necessary anymore.

-   File renderers are proper services now and can use dependency
    injection in their constructor.

-   The renderer priority is declared at the class itself instead of
    depending on the loading order of :file:`ext_localconf.php` files.

-   The registration is validated at container compile time: a service
    tagged as :yaml:`fal.file_renderer` that does not implement
    :php:`FileRendererInterface` fails the container build with a
    speaking exception, instead of causing errors when a file is
    rendered.

Registration in :file:`ext_localconf.php` via
:php:`RendererRegistry->registerRendererClass()` is not evaluated
anymore, see :ref:`breaking-110277-1784812454` for the upgrade path.

..  index:: FAL, PHP-API, ext:core
