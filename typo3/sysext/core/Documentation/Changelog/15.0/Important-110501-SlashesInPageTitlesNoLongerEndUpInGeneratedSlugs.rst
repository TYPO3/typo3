..  include:: /Includes.rst.txt

..  _important-110501-1787666881:

===============================================================================
Important: #110501 - Slashes in page titles no longer end up in generated slugs
===============================================================================

See :issue:`110501`

Description
===========

The :php:`slug` field of the :sql:`pages` table is generated from the page
title. Until now, a slash contained in the title was kept as-is.

As a result, a page titled `Terms / Conditions` was given the slug
:samp:`/terms-/-conditions`, and a page titled `Support 24/7` was given the
slug :samp:`/support-24/7`. The latter additionally introduces a path
segment that does not exist as a page: the URL looks as if the page were a
sub page :samp:`7` below a page :samp:`/support-24`.

The TCA of :sql:`pages` now configures a slug generator replacement that
turns a slash into the fallback character :samp:`-`:

..  code-block:: php
    :caption: typo3/sysext/core/Configuration/TCA/pages.php

    'slug' => [
        'config' => [
            'type' => 'slug',
            'generatorOptions' => [
                'fields' => ['title'],
                'fieldSeparator' => '/',
                'prefixParentPageSlug' => true,
                'replacements' => [
                    '/' => '-',
                ],
            ],
            'fallbackCharacter' => '-',
            // ...
        ],
    ],

The generated slugs therefore change as follows:

+---------------------+----------------------+---------------------+
| Page title          | Slug (before)        | Slug (now)          |
+=====================+======================+=====================+
| Terms / Conditions  | /terms-/-conditions  | /terms-conditions   |
+---------------------+----------------------+---------------------+
| Terms/Conditions    | /terms/conditions    | /terms-conditions   |
+---------------------+----------------------+---------------------+
| Support 24/7        | /support-24/7        | /support-24-7       |
+---------------------+----------------------+---------------------+

Every slug generated from now on is affected, no matter whether it is built
when a new page is created or when the slug is recreated from the title in
the page properties. Slugs of existing pages are only changed once they are
regenerated.

Manually entered slugs keep their slashes as before: a slash is a valid
character in a page slug, it separates the path segments of a page.

Sites relying on the previous behaviour can restore it by unsetting the
new option in :file:`Configuration/TCA/Overrides/pages.php`:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/TCA/Overrides/pages.php

    <?php

    unset($GLOBALS['TCA']['pages']['columns']['slug']['config']['generatorOptions']['replacements']);

..  index:: TCA, ext:core
