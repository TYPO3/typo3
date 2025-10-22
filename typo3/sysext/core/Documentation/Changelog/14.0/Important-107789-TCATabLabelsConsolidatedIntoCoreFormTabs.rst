..  include:: /Includes.rst.txt

..  _important-107789-1729603200:

====================================================================
Important: #107789 - TCA tab labels consolidated into core.form.tabs
====================================================================

See :issue:`107789`

Description
===========

To improve consistency and maintainability of TCA tab labels across TYPO3 Core,
commonly used tab labels from various extensions have been consolidated into the
central :file:`EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf` file.

This consolidation allows for better reusability and ensures consistent
translation of common tab labels across all core extensions and makes it
easier for extension developers to use standardized tab names.

New labels available in :file:`locallang_tabs.xlf`
---------------------------------------------------

The following new tab labels are now available and should be used via the
:php:`core.form.tabs:` prefix:

-   :php:`core.form.tabs:audio` - "Audio"
-   :php:`core.form.tabs:video` - "Video"
-   :php:`core.form.tabs:camera` - "Camera"
-   :php:`core.form.tabs:permissions` - "Permissions"
-   :php:`core.form.tabs:mounts` - "Mounts"
-   :php:`core.form.tabs:personaldata` - "Personal Data"

Previously existing labels (already migrated in core):

-   :php:`core.form.tabs:general` - "General"
-   :php:`core.form.tabs:access` - "Access"
-   :php:`core.form.tabs:categories` - "Categories"
-   :php:`core.form.tabs:notes` - "Notes"
-   :php:`core.form.tabs:language` - "Language"
-   :php:`core.form.tabs:extended` - "Extended"
-   :php:`core.form.tabs:appearance` - "Appearance"
-   :php:`core.form.tabs:behaviour` - "Behavior"
-   :php:`core.form.tabs:metadata` - "Metadata"
-   :php:`core.form.tabs:resources` - "Resources"
-   :php:`core.form.tabs:seo` - "SEO"
-   :php:`core.form.tabs:socialmedia` - "Social Media"
-   :php:`core.form.tabs:options` - "Options"

Migrated extension-specific labels
-----------------------------------

The following extension-specific tab labels have been migrated to the
consolidated labels file and are marked as unused since TYPO3 v14.0 with
the attribute :xml:`x-unused-since="14.0"` in corresponding `XLF` files.

**EXT:filemetadata**

-   :php:`LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:tabs.metadata` → :php:`core.form.tabs:metadata`
-   :php:`LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:tabs.camera` → :php:`core.form.tabs:camera`
-   :php:`LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:tabs.audio` → :php:`core.form.tabs:audio`
-   :php:`LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:tabs.video` → :php:`core.form.tabs:video`

**EXT:seo**

-   :php:`LLL:EXT:seo/Resources/Private/Language/locallang_tca.xlf:pages.tabs.seo` → :php:`core.form.tabs:seo`
-   :php:`LLL:EXT:seo/Resources/Private/Language/locallang_tca.xlf:pages.tabs.socialmedia` → :php:`core.form.tabs:socialmedia`

**EXT:core - Backend Users**

-   :php:`LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.tabs.personal_data` → :php:`core.form.tabs:personaldata`
-   :php:`LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.tabs.mounts_and_workspaces` → :php:`core.form.tabs:mounts`
-   :php:`LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.tabs.options` → :php:`core.form.tabs:options`

**EXT:core - Backend User Groups**

-   :php:`LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.tabs.mounts_and_workspaces` → :php:`core.form.tabs:mounts`
-   :php:`LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.tabs.options` → :php:`core.form.tabs:options`

**EXT:frontend - Frontend Users**

-   :php:`LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:fe_users.tabs.personalData` → :php:`core.form.tabs:personaldata`

Affected installations
======================

Custom extensions using TCA configurations may benefit from using the new
consolidated tab labels instead of creating their own labels for common tab names.

Extensions that were using any of the migrated extension-specific labels
listed above will continue to work in TYPO3 v14.0, but should migrate to
the consolidated labels. The old labels will be removed in TYPO3 v15.0.

Migration
=========

For custom extensions, consider using the consolidated :php:`core.form.tabs:`
labels instead of creating custom labels for common tab names.

Example migration for extensions using old labels:

**File metadata tabs**

.. code-block:: php

    // Before
    '--div--;LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:tabs.metadata'

    // After
    '--div--;core.form.tabs:metadata'

**User and group tabs**

.. code-block:: php

    // Before
    '--div--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.tabs.personal_data'
    '--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:fe_users.tabs.personalData'

    // After
    '--div--;core.form.tabs:personaldata'

**Using consolidated labels in custom extensions**

.. code-block:: php

    // Example: Custom TCA using consolidated labels
    'types' => [
        '1' => [
            'showitem' => '
                --div--;core.form.tabs:general,
                    title, description,
                --div--;core.form.tabs:metadata,
                    author, keywords,
                --div--;core.form.tabs:access,
                    hidden, starttime, endtime,
                --div--;core.form.tabs:categories,
                    categories,
                --div--;core.form.tabs:extended,
            ',
        ],
    ],

New labels available in :file:`palettes.xlf`
------------------------------------------------------

In addition to tab labels, commonly used palette labels have also been consolidated
into the central :file:`EXT:core/Resources/Private/Language/Form/palettes.xlf` file.

The following palette labels are now available via the :php:`core.form.palettes:` prefix:

-   :php:`core.form.palettes:general` - "General"
-   :php:`core.form.palettes:account` - "Account"
-   :php:`core.form.palettes:authentication` - "Authentication"
-   :php:`core.form.palettes:permission_languages` - "Language permissions"
-   :php:`core.form.palettes:permission_general` - "General permissions"
-   :php:`core.form.palettes:permission_specific` - "Specific permissions"
-   :php:`core.form.palettes:standard` - "Page"
-   :php:`core.form.palettes:title` - "Title"
-   :php:`core.form.palettes:visibility` - "Visibility"
-   :php:`core.form.palettes:access` - "Publish Dates and Access Rights"
-   :php:`core.form.palettes:abstract` - "Abstract"
-   :php:`core.form.palettes:metatags` - "Meta Tags"
-   :php:`core.form.palettes:editorial` - "Editorial"
-   :php:`core.form.palettes:page_layout` - "Page Layout"
-   :php:`core.form.palettes:use_as_container` - "Use as Container"
-   :php:`core.form.palettes:replace` - "Replace Content"
-   :php:`core.form.palettes:links_page` - "Links to this Page"
-   :php:`core.form.palettes:caching` - "Caching"
-   :php:`core.form.palettes:language` - "Language"
-   :php:`core.form.palettes:miscellaneous` - "Miscellaneous"
-   :php:`core.form.palettes:media` - "Files"
-   :php:`core.form.palettes:storage` - "Storage Page"
-   :php:`core.form.palettes:config` - "Configuration"
-   :php:`core.form.palettes:headers` - "Headlines"
-   :php:`core.form.palettes:header` - "Header"
-   :php:`core.form.palettes:content_layout` - "Content Element Layout"
-   :php:`core.form.palettes:media_behaviour` - "Media Behaviour"
-   :php:`core.form.palettes:accessibility` - "Accessibility"
-   :php:`core.form.palettes:downloads_layout` - "Downloads Layout"
-   :php:`core.form.palettes:table_layout` - "Table Layout"
-   :php:`core.form.palettes:links_appearance` - "Links"
-   :php:`core.form.palettes:settings_gallery` - "Gallery Settings"
-   :php:`core.form.palettes:media_adjustments` - "Media Adjustments"
-   :php:`core.form.palettes:metrics` - "Metrics"
-   :php:`core.form.palettes:geolocation` - "Geo Location"
-   :php:`core.form.palettes:contentdate` - "Content Date"
-   :php:`core.form.palettes:gps` - "GPS"
-   :php:`core.form.palettes:seo` - "General SEO settings"
-   :php:`core.form.palettes:robots` - "Robot instructions"
-   :php:`core.form.palettes:opengraph` - "Open Graph (Facebook)"
-   :php:`core.form.palettes:twittercards` - "X / Twitter Cards"
-   :php:`core.form.palettes:canonical` - "Canonical"
-   :php:`core.form.palettes:sitemap` - "Sitemap"
-   :php:`core.form.palettes:additional` - "Additional configuration"

Migrated palette labels
-----------------------

The following palette labels have been migrated to use :php:`core.form.palettes:*`
and are marked as unused since TYPO3 v14.0 (attribute :php:`x-unused-since="14.0"` in XLF files):

**EXT:core - Backend Users & Groups:**

-   be_users.palettes.account, be_users.palettes.authentication, be_users.palettes.permissionLanguages
-   be_groups.palettes.authentication, be_groups.palettes.permissionGeneral, be_groups.palettes.permissionLanguages, be_groups.palettes.permissionSpecific

**EXT:frontend - Pages & Content Elements:**

-   pages.palettes.* (17 labels: standard, title, visibility, access, abstract, metatags, editorial, layout, module, replace, links, caching, language, miscellaneous, media, storage, config)
-   palette.* in tt_content (13 labels: general, headers, header, visibility, access, frames, imagelinks, image_accessibility, uploads_layout, table_layout, appearanceLinks, gallerySettings, mediaAdjustments)

**EXT:filemetadata:**

-   palette.* (6 labels: metrics, geo_location, visibility, content_date, accessibility, gps)

**EXT:seo:**

-   pages.palettes.* (6 labels: seo, robots, opengraph, twittercards, canonical, sitemap)

..  index:: TCA, NotScanned, ext:core
