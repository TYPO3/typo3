..  include:: /Includes.rst.txt

..  _important-110629-1788539646:

======================================================================
Important: #110629 - Extension manager downloads ZIP archives from TER
======================================================================

See :issue:`110629`

Description
===========

When installing an extension from TER, the Extension Manager fetched the
:file:`.t3x` file: a serialized PHP array holding every file's contents,
verified with an MD5 hash taken from the extension index. The extension
directory was then rebuilt from that array, and its :file:`ext_emconf.php`
regenerated from the embedded :php:`EM_CONF` block.

TER stores the ZIP archive the extension author uploaded next to the
:file:`.t3x` file, and now publishes its SHA-256 hash in the extension index
as :xml:`<artifactsha256>`. Whenever such a hash is available, the Extension
Manager downloads that archive instead, verifies it against the hash and
extracts it.

What this changes for an installed extension: it is now the archive the author
uploaded rather than a copy reassembled from serialized file contents. Files
that the t3x rebuild could not reproduce faithfully - most importantly
:file:`composer.json`, which classic mode relies on since :issue:`109783` -
are installed unchanged, and :file:`ext_emconf.php` is the author's own file
instead of a generated one.

The :file:`.t3x` file remains the fallback. Remotes that do not publish an
artifact hash, and versions uploaded before TER recorded one, are downloaded
and verified exactly as before, so nothing has to be re-uploaded.

Extension authors do not need to change anything. TER keeps publishing both
artifacts and the upload itself is unaffected.

Custom remotes implementing
:php:`\TYPO3\CMS\Extensionmanager\Remote\ExtensionDownloaderRemoteInterface`
now receive a hash of a known algorithm prefixed with its name, for example
:php:`sha256:<hash>`. The method signature is unchanged, and an unprefixed
value keeps its previous meaning, so a remote that only ever supplies MD5
hashes of :file:`.t3x` files continues to work untouched.

..  index:: PHP-API, ext:extensionmanager, NotScanned
