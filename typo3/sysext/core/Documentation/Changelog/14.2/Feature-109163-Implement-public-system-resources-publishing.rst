.. include:: /Includes.rst.txt

.. _feature-109163-1772708896:

===============================================================
Feature: #109163 - Implement public system resources publishing
===============================================================

See :issue:`109163`

Description
===========

When implementing the new system resources API (:ref:`feature-107537-1759136314`),
resource publishing was skipped and is now implemented.

The most visible feature with this implementation is the now available
`asset:publish` command. This command can be executed to publish public extension
resources from their `Resources/Public` folder to the document root directory
(`public` by default in Composer mode).

To maintain backwards compatibility for Composer mode installations, this command will
automatically be executed during `composer install`. This means after Composer has done
it's job installing packages, extension assets have already been published.

Public extension resources are however also published when extensions are set up
using `extension:setup` or when activating an extension in extension manager.
Because of this and because it might not be wanted or applicable
to publish assets on Composer build time, it is now possible to skip publishing
during `composer install` by setting the environment variable `TYPO3_SKIP_ASSET_PUBLISH`
e.g. like so: `TYPO3_SKIP_ASSET_PUBLISH=1 composer install`.
Not publishing assets on `composer install` will likely
become default in future TYPO3 versions.

TYPO3 only ships file system based publishing. From now on however, there is an
additional strategy made available besides symlink publishing (*nix systems) and
junction publishing (Windows systems). TYPO3 can now copy all files and folders
from their private locations to the document root. This is handy for many use cases
like container building, deployments with read only file systems,
restrictive hosting environments and others.

By default the linking strategy is kept, especially for backwards compatibility reasons.
It is however possible to influence the behaviour by setting the following configuration option:

Default behaviour: always link:

:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['SystemResources']['filesystemPublishingType'] = 'link';`

Always copy/ mirror files:

:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['SystemResources']['filesystemPublishingType'] = 'mirror';`

Copy/ mirror files in `Production` context and link folders in `Development` context:

:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['SystemResources']['filesystemPublishingType'] = 'auto';`

Beyond file system publishing
-----------------------------

While TYPO3 core only delivers file system based publishing, third party extensions can now
implement other ways to publish public system resources.

By implementing :php:`\TYPO3\CMS\Core\SystemResource\Publishing\SystemResourcePublisherInterface`
and registering the implementing class as alias of this interface, TYPO3 will use this not only
to publish system resources, but also to generate URIs to those, that reflect their new location,
e.g. on a CDN.

This will then work in TYPO3 classic mode as well, because publishing is now part of extension
activation.

..  code-block:: yaml
    :caption: EXT:my_extension/Classes/Service/ExampleResourcePublisher.php (Simple example how to directly generate URIs for a CDN)

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

        public function __construct(private DefaultSystemResourcePublisher $defaultSystemResourcePublisher)
        {}

        public function publishResources(PackageInterface $package): FlashMessageQueue
        {
            // There could be some additional logic here to publish files to a CDN
            // For this example we assume the CDN loads the assets from the source
            // automatically, so we publish as usual
            return $this->defaultSystemResourcePublisher->publishResources($package);
        }

        public function generateUri(
            PublicResourceInterface $publicResource,
            ?ServerRequestInterface $request,
            ?UriGenerationOptions $options = null
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

There is no apparent impact for any TYPO3 installation, as changes made are mostly internal
and public API and behaviour is kept as before. Also for deployments there is nothing to
be changed, as asset publishing is still performed on `composer install`, but also
on `extension:setup` command, which both are obviously already part of any deployment workflow.

Users however now have more control when and how exactly publishing is performed, by setting
environment variable `TYPO3_SKIP_ASSET_PUBLISH=1` for `composer install` or by configuring
`mirror` strategy for publishing by setting
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['SystemResources']['filesystemPublishingType'] = 'mirror';`
in `config/system/additional.php`

.. index:: ext:core
