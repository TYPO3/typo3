..  include:: /Includes.rst.txt

..  _important-109312-1792744069:

==================================================================================
Important: #109312 - Include FAIR-compatible endpoint adapter in Extension Manager
==================================================================================

See :issue:`109312`

Description
===========

The TYPO3 Extension Repository publishes a `FAIR <https://fair.pm>`__
metadata document for every extension at
`https://extensions.typo3.org/fair/v1/packages/<extension-key>`. It lists
all releases of the extension with their requirements, the URL of the zip
archive and its SHA-256 checksum.

The Extension Manager now ships an adapter for that endpoint and resolves
the package to download from the document when an extension is installed or
updated in classic mode. The archive is still fetched from TER and verified
against the checksum. The locally imported extension list answers when the
document cannot be read, when TER does not list the requested version, when
a release carries no SHA-256 checksum or a version constraint that cannot be
converted, or when the Extension Manager is in offline mode.

Which versions an update offers is not taken from the document: it names
neither the review state of a release nor its upload comment, so insecure
releases could not be excluded. The newest version and the versions within
a range are still answered as before.

The resolving step sits behind the new, internal
:php:`\TYPO3\CMS\Extensionmanager\Domain\PackageResolverInterface`, next
to the catalogue contract browsing uses. The two are kept apart because their
callers are: the list views never resolve, and the download code never
browses.

A version of an extension that the "Get Extensions" module shows can
therefore be installed right away. Previously the module resolved the
download against the imported extension list only, so a release published
after the last "Update from TER" was listed but could not be installed until
the list was refreshed. Installations in offline mode, and installations that
cannot reach extensions.typo3.org, keep working exactly as before: everything
is answered from the imported list.

..  index:: Backend, ext:extensionmanager
