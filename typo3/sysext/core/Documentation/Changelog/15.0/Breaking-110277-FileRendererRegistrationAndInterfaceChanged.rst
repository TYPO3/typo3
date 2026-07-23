..  include:: /Includes.rst.txt

..  _breaking-110277-1784812454:

======================================================================
Breaking: #110277 - File renderer registration and interface changed
======================================================================

See :issue:`110277`

Description
===========

File renderers, used for example by the :html:`<f:media>` ViewHelper to
render audio, video or online media files, are now registered as tagged
services via dependency injection (see
:ref:`feature-110277-1784812454`). This comes with the following breaking
changes:

-   :php:`\TYPO3\CMS\Core\Resource\Rendering\RendererRegistry->registerRendererClass()`
    is now a no-op. Calling the method has no effect anymore, but triggers
    an :php:`E_USER_DEPRECATED` notice. The method will be removed in
    TYPO3 v16.0.

-   :php:`\TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface` does
    not extend :php:`\TYPO3\CMS\Core\SingletonInterface` anymore. File
    renderers are shared services managed by the dependency injection
    container.

-   The method :php:`getPriority()` has been removed from
    :php:`FileRendererInterface`. The renderer priority is now defined at
    registration time via the :php:`#[AsFileRenderer]` attribute or the
    :yaml:`fal.file_renderer` service tag. Note that the file renderers
    shipped with TYPO3 Core previously returned a priority of :php:`1`
    from :php:`getPriority()` and are now registered with the attribute's
    default priority of :php:`0`. Custom renderers that relied on a
    priority of :php:`0` to rank strictly below all Core renderers now
    rank equally with them instead and should use a negative priority to
    keep the previous ordering.

-   Renderers registered with the same priority are no longer guaranteed
    to be asked in the order they were added: previously, same-priority
    renderers kept the order in which
    :php:`RendererRegistry->registerRendererClass()` was called from
    :file:`ext_localconf.php`. The order of same-priority tagged services
    is now an implementation detail of the dependency injection container
    and must not be relied upon. Extensions that depend on a specific
    evaluation order between renderers should assign distinct priorities
    instead.

-   The remaining methods of :php:`FileRendererInterface` are now
    strictly typed: :php:`canRender(FileInterface $file): bool` and
    :php:`render(FileInterface $file, int|string $width, int|string $height, array $options = []): string`.

-   The methods :php:`createRendererInstance()` and
    :php:`compareRendererPriority()` have been removed from
    :php:`RendererRegistry`, the method :php:`getRendererInstances()` has
    been changed from public to protected visibility, and the remaining
    methods are now strictly typed.

-   :php:`RendererRegistry` is now marked as :php:`@internal`, since
    registering file renderers does not require interacting with the
    registry anymore. TYPO3 Core resolves the matching renderer via
    :php:`getRenderer()` internally, for example in the
    :html:`<f:media>` ViewHelper.

Impact
======

File renderers registered via
:php:`RendererRegistry->registerRendererClass()` in
:file:`ext_localconf.php` are no longer evaluated. The corresponding
files (audio, video or online media) are no longer rendered by the custom
renderer until it is registered as a tagged service.

Custom renderer classes implementing :php:`FileRendererInterface` without
the adapted method signatures will cause a fatal PHP error.

Affected installations
======================

All installations with custom extensions registering file renderers via
:php:`RendererRegistry->registerRendererClass()`, or providing custom
implementations of :php:`FileRendererInterface`. The extension scanner
reports usages of :php:`registerRendererClass()` as weak match.

Migration
=========

Remove the :php:`RendererRegistry->registerRendererClass()` call from
:file:`ext_localconf.php` and add the :php:`#[AsFileRenderer]` attribute
to the renderer class instead. Move the priority previously returned by
:php:`getPriority()` to the attribute and remove the method. Add the
native type declarations to :php:`canRender()` and :php:`render()`:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Resource/Rendering/MyVideoRenderer.php

    use TYPO3\CMS\Core\Attribute\AsFileRenderer;
    use TYPO3\CMS\Core\Resource\FileInterface;
    use TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface;

    #[AsFileRenderer(priority: 10)]
    final class MyVideoRenderer implements FileRendererInterface
    {
        public function canRender(FileInterface $file): bool
        {
            // ...
        }

        public function render(FileInterface $file, int|string $width, int|string $height, array $options = []): string
        {
            // ...
        }
    }

In case the extension supports both TYPO3 v14 and v15, keep the
:php:`getPriority()` method (it is simply unused in v15) and register the
renderer in both ways: the :file:`ext_localconf.php` registration is
evaluated in v14, the attribute in v15. Since PHP parameter types must
not be narrowed in implementations, keep the :php:`$width` and
:php:`$height` parameters untyped in this case — only the :php:`bool`
and :php:`string` return type declarations are compatible with both
versions.

Code that called :php:`RendererRegistry->getRendererInstances()` to
inspect all registered renderers should inject :php:`RendererRegistry`
and use :php:`getRenderer($file)` to retrieve the matching renderer for
a given file instead.

..  index:: FAL, PHP-API, PartiallyScanned, ext:core
