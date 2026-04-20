..  include:: /Includes.rst.txt

..  _feature-109163-1772708896:

===============================================================
Feature: #109163 - Implement public system resources publishing
===============================================================

See :issue:`109163`

Description
===========

When implementing the new system resources API
(:ref:`feature-107537-1759136314`), resource publishing was skipped and has now
been implemented.

The most visible feature of this implementation is the new
`asset:publish` command. This command can publish public
extension resources from their `Resources/Public` folder to the document root
directory (`public` by default in Composer mode).

To maintain backward compatibility for Composer mode installations, this
command is automatically executed during `composer install`. This means
that after Composer has done its job installing packages, extension assets
are already published.

Public extension resources are also published when extensions are set up
with the `extension:setup` command or when an extension is activated in the Extension
Manager. Because of this, and because it might not be desirable or applicable
to publish assets at Composer build time, it is now possible to skip publishing
during `composer install` by setting an environment variable
`TYPO3_SKIP_ASSET_PUBLISH`, for example:
`TYPO3_SKIP_ASSET_PUBLISH=1 composer install`.
Not publishing assets at `composer install` is likely to become default behavior
in future TYPO3 versions.

TYPO3 ships file system-based publishing only. From now on, however, there is
an additional strategy available besides symlink publishing (*nix systems) and
junction publishing (Windows systems). TYPO3 can now copy all files and
folders from their private locations to the document root. This is useful for
many use cases such as container builds, deployments with read-only file
systems, restrictive hosting environments, and others.

By default, the linking strategy is being kept, particularly for backward
compatibility reasons. It is, however, possible to influence the behavior by
setting the following configuration option:

Default behavior: always link:

:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['SystemResources']['filesystemPublishingType'] = 'link';`

Always copy / mirror files:

:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['SystemResources']['filesystemPublishingType'] = 'mirror';`

Copy / mirror files in a `Production` context and link folders in a `Development`
context:

:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['SystemResources']['filesystemPublishingType'] = 'auto';`

Beyond file system publishing
-----------------------------

Although TYPO3 Core only delivers file system-based publishing, third-party
extensions can now implement other ways of publishing public system resources.

By implementing
:php:`\TYPO3\CMS\Core\SystemResource\Publishing\SystemResourcePublisherInterface`
and registering the implementing class as an alias of the interface, TYPO3
will use this not only to publish system resources, but also to generate URIs
that reflect their new location, for example on a CDN.

This also works in TYPO3 classic mode, because publishing is now part of
extension activation.

Simple example of how to generate URIs for a CDN:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Service/ExampleResourcePublisher.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\Service;

    use Psr\Http\Message\ServerRequestInterface;
    use Psr\Http\Message\UriInterface;
    use Symfony\Component\DependencyInjection\Attribute\AsAlias;
    use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
    use TYPO3\CMS\Core\Http\Uri;
    use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
    use TYPO3\CMS\Core\Package\PackageInterface;
    use TYPO3\CMS\Core\SystemResource\Publishing\DefaultSystemResourcePublisher;
    use TYPO3\CMS\Core\SystemResource\Publishing\SystemResourcePublisherInterface;
    use TYPO3\CMS\Core\SystemResource\Publishing\UriGenerationOptions;
    use TYPO3\CMS\Core\SystemResource\Type\PublicPackageFile;
    use TYPO3\CMS\Core\SystemResource\Type\PublicResourceInterface;

    #[Autoconfigure(public: true), AsAlias(SystemResourcePublisherInterface::class, public: true)]
    final readonly class ExampleResourcePublisher implements SystemResourcePublisherInterface
    {
        private const CDN_URL = 'https://my.awsome.cdn/files/';

        public function __construct(
            private DefaultSystemResourcePublisher
                $defaultSystemResourcePublisher,
        ) {}

        public function publishResources(
            PackageInterface $package,
        ): FlashMessageQueue {
            // Additional logic to publish files to a CDN could be added
            // here. For this example, the CDN loads the assets from the
            // source automatically, so resources are published as usual.
            return $this->defaultSystemResourcePublisher->publishResources(
                $package,
            );
        }

        public function generateUri(
            PublicResourceInterface $publicResource,
            ?ServerRequestInterface $request,
            ?UriGenerationOptions $options = null,
        ): UriInterface {
            $defaultUri = $this->defaultSystemResourcePublisher->generateUri(
                $publicResource,
                $request,
                new UriGenerationOptions(
                    uriPrefix: '',
                    absoluteUri: false,
                    cacheBusting: false,
                ),
            );
            if ($publicResource instanceof PublicPackageFile) {
                return new Uri(self::CDN_URL . $defaultUri);
            }
            return $defaultUri;
        }
    }

Impact
======

There is no apparent impact for any TYPO3 installation, as the changes are mostly internal
and the public API and behavior are the same as before. For deployments, nothing needs to
be changed, as asset publishing is still performed at `composer install`, and also
by the `extension:setup` command, both of which are already part of any deployment workflow.

Users, however, now have more control over when and how publishing is performed, by setting
the environment variable `TYPO3_SKIP_ASSET_PUBLISH=1` for `composer install` or by configuring
the `mirror` strategy for publishing by setting
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['SystemResources']['filesystemPublishingType'] = 'mirror';`
in `config/system/additional.php`.

..  index:: ext:core
