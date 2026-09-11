..  include:: /Includes.rst.txt

..  _important-109582-1789042648:

=========================================================
Important: #109582 - Extension browsing asks TER directly
=========================================================

See :issue:`109582`

Description
===========

The "Get Extensions" module answered every listing, search and detail view from
a local mirror of the TER catalogue: the :file:`extensions.xml.gz` dump, several
megabytes of it, imported into a database table whenever the editor pressed
"Update from TER". Everything the module showed was therefore as old as the last
import, and a freshly released extension could not be found at all until someone
refreshed the list.

The module now asks the TER REST API at `https://extensions.typo3.org/api/v1`
for what it displays. Listings are fetched one page at a time, searches are
answered by TER itself, and the catalogue is filtered to the running TYPO3
version, which the imported list could not do. As long as nothing has been
published for the running major version yet, extensions for the previous major
version are listed instead.

Should TER not answer - the installation is offline, a proxy blocks it, the
service is down or a rate limit was reached - the imported list answers instead.
Browsing then behaves exactly as it did before, only with older data, and the
failure is recorded in the log rather than shown as an error. TER is then not
asked again for five minutes, so an installation without internet access does
not wait for a timeout on every page. Importing the list therefore keeps working
and keeps being worthwhile.

Requests carry a :html:`TYPO3/<version>` user agent, and responses are cached
for an hour in the new :php:`extensionmanager_ter_api` cache, so paging back and
forth does not produce a request per click.

Downloading an extension is unaffected: the archive is still fetched from the
file TER stores for it and verified against its SHA-256 hash. Resolving which
package to download still uses the imported list, so installing an extension
continues to require it - the module says so when the record is missing.

..  index:: Backend, ext:extensionmanager, NotScanned
