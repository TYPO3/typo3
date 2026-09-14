..  include:: /Includes.rst.txt

..  _feature-110603-1789401735:

========================================================================
Feature: #110603 - Add a dry run option to the redirects:cleanup command
========================================================================

See :issue:`110603`

Description
===========

The console command :bash:`redirects:cleanup` permanently deletes all redirects
matching the given constraints. Until now, there was no way to verify upfront
which redirects a set of constraints actually covers.

The command therefore now provides a :bash:`--dry-run` option, as already known
from :bash:`cleanup:deletedrecords`. If it is set, no redirect is deleted.
Instead, all redirects which would have been deleted are listed:

..  code-block:: bash

    vendor/bin/typo3 redirects:cleanup --domain=example.com --dry-run

..  code-block:: text

    ------ ------------- ------------- ------------------------- ------------- ------
     UID    Source host   Source path   Target                    Status code   Hits
    ------ ------------- ------------- ------------------------- ------------- ------
     12     example.com   /outdated     https://example.com/new   301           0
    ------ ------------- ------------- ------------------------- ------------- ------

    ! [NOTE] 1 redirect would be deleted. Nothing has been deleted, this was a
    !        dry run.

The listing includes disabled and soft-deleted redirects as well as redirects
outside their start and end time, since a cleanup deletes those, too.

Impact
======

The constraints of a redirect cleanup can now be verified with
:bash:`--dry-run` before any redirect is deleted.

..  index:: CLI, ext:redirects
