..  include:: /Includes.rst.txt

..  _feature-111541-1795458397:

=============================================================
Feature: #111541 - Configure the public URLs of file storages
=============================================================

See :issue:`111541`

Description
===========

File storages based on Flysystem, which includes all local storages, now
generate their public URLs with a Flysystem
:php:`\League\Flysystem\UrlGeneration\PublicUrlGenerator`, which is selected
with the driver option :yaml:`publicUrl`:

:yaml:`type: prefix` (default)
    Publishes the files below the URL of the option :yaml:`baseUri`, with each
    path segment URL-encoded. A local storage below the public directory uses
    its path relative to the public directory, for example
    :path:`fileadmin/`, as before. A storage without such a URL is not public.

:yaml:`type: adapter`
    Takes the public URLs from the Flysystem adapter, which must implement
    :php:`PublicUrlGenerator`. Adapters such as the AWS S3 adapter generate
    URLs for private buckets as well, so their URLs are only used with this
    option.

..  code-block:: yaml
    :caption: config/system/file-storages.yaml

    storages:
      1:
        name: fileadmin
        default: true
        configuration:
          basePath: public/fileadmin/
      2:
        name: Media CDN
        configuration:
          basePath: var/media/
          baseUri: 'https://media.example.com/files/'
      3:
        name: Public bucket
        driver: S3
        configuration:
          bucket: my-public-media
          publicUrl:
            type: adapter

A driver extending :php:`\TYPO3\CMS\Core\Resource\Driver\AbstractFlysystemDriver`
can provide its own generator by overriding
:php:`createPublicUrlGenerator()`.

Relation to the system resource publishing API
-----------------------------------------------

The public URL of a storage is independent of the current request: it is
either relative to the public directory, like :path:`fileadmin/file.pdf`, or
an absolute URL. Rendering it for the current request, with the
:typoscript:`absRefPrefix`, as absolute URL or with cache busting, is the job
of
:php:`\TYPO3\CMS\Core\SystemResource\Publishing\SystemResourcePublisherInterface::generateUri()`,
see :ref:`feature-107537-1759136314`, which takes the public URL of a file
from its storage.

On the command line, the publisher no longer puts its prefix in front of the
public URL of a storage that is absolute already, so a file of a storage with
a :yaml:`baseUri` like :samp:`https://media.example.com/files/` no longer
gets a URL starting with :samp:`/https://`.

Resolving a URL to its file
---------------------------

The new :php:`\TYPO3\CMS\Core\Resource\PublicUrl\PublicUrlResolverInterface`
is the reverse of a generator: it maps a requested URI back to the path it
was generated for. The generator of :yaml:`type: prefix` implements it.
:php:`ResourceStorage->resolvePublicUrl()` returns the identifier of the file
or folder a URI is the public URL of, or :php:`null` if the storage is
offline, not public, or its generator cannot map URLs back:

..  code-block:: php

    $identifier = $storage->resolvePublicUrl(
        new Uri('https://media.example.com/files/report%202026.pdf'),
        $request->getAttribute('normalizedParams')->getSitePath()
    );
    // "/report 2026.pdf"

So far, code that only had a URL, for example the path of an incoming request
or a link in content, had to guess the file behind it by comparing strings
with paths like :path:`fileadmin/`. Such guessing fails for storages with a
:yaml:`baseUri` on another host, for nested storages, for installations in a
subdirectory and for URL-encoded file names. As the resolver is the exact
reverse of the generator of the storage, with the same base URI and encoding,
the result stays correct when the configuration of the storage changes.

This allows, for example, to:

*   answer requests for missing files of a storage within TYPO3, or redirect
    them, see :issue:`101942`
*   convert URLs of files in content, imported data or links entered by
    editors to references of the file, like `t3://file?uid=…`
*   deliver files of a storage through TYPO3 and check the access to them,
    based on the requested URL

URLs that would cover every path of a host, for example a :yaml:`baseUri`
pointing to the root of the website, are not resolved, so a page of the
website is never mistaken for a file.

Drivers that do not extend :php:`AbstractFlysystemDriver` can implement
:php:`\TYPO3\CMS\Core\Resource\Driver\PublicUrlResolvingDriverInterface` to
support resolving URLs.

Impact
======

The public URLs of existing storages do not change. Public URLs of Flysystem
adapters can be used with :yaml:`publicUrl.type: adapter`, and the file or
folder of a public URL can be determined, for example to handle requests for
missing files.

..  index:: FAL, PHP-API, ext:core
