.. include:: ../../Includes.txt

=============================================================
Feature: #83631 - System Extension "redirects" has been added
=============================================================

See :issue:`83631`


Description
===========

A new system extension "redirects" has been added, which ships flexible handling of HTTP redirects,
useful both for marketers and site administrators.

It adds a new module called "Redirects" (under a new main module called "Site Management").

A new DB table "sys_redirect" has been added, which allows to configure a redirect from a source
(host+path) to a destination target. The destination target can be any kind of Uri (used by the LinkService).

Any time a redirect is added or modified, a list of all redirects is added to the cache management,
allowing to fetch all redirects at once, reducing the number of queries to the DB in the frontend to 1 query
(or to one query to the file system, as the power lies in the caching framework).

A simple hit statistics counter is implemented as well.


Impact
======

A system extension "Redirects" was added with the following features:

* A new sub module "Redirects"
* Possibility to add redirects with the following caveats

  * Source may be a specific domain, domain with port or "any" domain
  * Source Path may be an absolute path (`/foo/bar/`) or a regular expression (`#f(.*?)#`)
  * Target may be selected with the link wizard (and may be a page, file, folder or external URL)

* The target can be forced to HTTPS only
* The status code of the redirect can be configured per redirect
* Existing GET variables can be kept through the redirect
* Redirects can be set up for specific time frames or indefinitely
* An `X-Redirect-By: TYPO3` header is added to each redirect initiated by the module
* A simple database based hit counter shows how often a redirect was executed and may be manually reset

The default settings of TYPO3s cache backend allow for about 65.000 redirects.

If you have more (or in the future might have more) than 65.000 redirects it is advised to switch to a different cache
backend like redis.

.. index:: Backend, Frontend
