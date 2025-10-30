..  include:: /Includes.rst.txt

..  _feature-107628-1729026000:

===============================================================
Feature: #107628 - Improved backend module naming and structure
===============================================================

See :issue:`107628`

Description
===========

TYPO3's backend module structure has been modernized with clearer, more
intuitive naming conventions that better align with industry standards
used by enterprise content management systems.

Module names should immediately convey their purpose to editors and
administrators. This series of renamings improves clarity, discoverability,
and reduces the cognitive load for both new and experienced users.

Clear and consistent naming is fundamental to good user experience. When
comparing TYPO3 with other enterprise CMS platforms, it became evident that
some of TYPO3's module names were either too technical, ambiguous, or didn't
clearly communicate their purpose to users.

To summarize, the goals of this restructuring are:

*   **Improved clarity**: Module names immediately convey their purpose
*   **Industry alignment**: Adopt naming conventions familiar to users of other
    enterprise CMS platforms
*   **Better discoverability**: Help new users find functionality without
    extensive training
*   **Reduced cognitive load**: Minimize confusion when navigating the backend


Module Renamings
================

The following modules have been renamed:

Top level modules
-----------------

Web => Content
~~~~~~~~~~~~~~

The top-level :guilabel:`Web` module has been renamed to :guilabel:`Content`.

**Rationale:** TYPO3 is a **content** management system. The primary workspace
where editors create and manage content should be clearly labeled as such.
The term "Web" was ambiguous and didn't communicate the module's purpose -
nobody outside the TYPO3 ecosystem understood what "Web" meant in this context.

**Migration:** Update module parent references from :php:`'web'` to
:php:`'content'`:

..  code-block:: php

    return [
        'my_module' => [
            'parent' => 'content',  // Previously: 'web'
        ],
    ];


File => Media
~~~~~~~~~~~~~

The top-level :guilabel:`File` module has been renamed to :guilabel:`Media`.

**Rationale:** The term "Media" clearly indicates the module's purpose of
managing digital media files (images, videos, documents, audio files, etc.)
within the CMS. The term "File" was too generic and technical, while "Media"
is widely understood and commonly used in content management systems to refer
to digital content assets.

**Migration:** Update module parent references from :php:`'file'` to
:php:`'media'`:

..  code-block:: php

    return [
        'my_module' => [
            'parent' => 'media',  // Previously: 'file'
        ],
    ];


Site Management => Sites
~~~~~~~~~~~~~~~~~~~~~~~~

The top-level :guilabel:`Site Management` module has been renamed to :guilabel:`Sites`.

**Rationale:** The former label "Site Management" was long and formal, and did
not align with TYPO3’s evolving, concise module naming strategy. The
simplified name "Sites" improves scanability in the module menu and matches
the naming of other top-level modules. It also better reflects the purpose
of the module: providing an overview entry point for all configured (web)sites.

**Migration:** The top-level module identifier :php:`site` is kept. No migration
is necessary.

Admin (tools) <=> System
~~~~~~~~~~~~~~~~~~~~~~~~

The top-level module formerly known as :guilabel:`Admin tools` is now called
:guilabel:`Administration`.

The purpose of this top-level module has changed. It now contains those modules
useful to a backend admin, such as user and permission management, the Scheduler,
Integrations, etc.

Most modules formerly found in :guilabel:`Admin tools` are now located in
:guilabel:`System`.

**Rationale:**
The top-level module :guilabel:`Administration` now contains modules that
are used by backend administrators in their daily work. Modules that require
system maintainer permissions are found in the module named :guilabel:`System`.

**Migration:**
Modules generally accessible to backend administrators should be
moved to the top-level module with the identifier `admin`.
Modules that require
system maintainer permissions or are mainly useful to system maintainers and
DevOps should be moved to `system`.

..  code-block:: php

    return [
        'my_administration_module' => [
            'parent' => 'admin',  // Previously: 'system'
            'access' => 'admin',
        ],
        'my_maintainer_module' => [
            'parent' => 'system',  // Previously: 'tools'
            'access' => 'systemMaintainer',
        ],
    ];

Second level modules
--------------------

For modules, where the module identifier changed, the upgrade wizard
"Migrate module permissions" migrates module level group and user permissions.

Page => Layout
~~~~~~~~~~~~~~

The second-level :guilabel:`Page` module has been renamed to :guilabel:`Layout`
to better match its scope.

**Rationale:** The previous module name “Page” did not clearly convey the
module’s purpose or workflow. TYPO3 provides multiple ways to interact with
a page (e.g. structure, properties, preview), and the term “Page” alone did
not describe which aspect was being managed. The renamed module “Layout”
more accurately reflects what editors do inside the module: maintain the
page layout, manage content elements, and organize them into the correct
columns and grids. This provides clearer expectations, improves usability
for new editors, and aligns the module name with modern TYPO3 workflows
and terminology.

**Migration:** Since the module is just renamed, there are no migrations
necessary.

View => Preview
~~~~~~~~~~~~~~~

The second-level :guilabel:`View` module has been renamed to :guilabel:`Preview`
to better match its scope. It has also been moved one position down after :guilabel:`List`,
as that module is considered more important for daily work.

**Rationale:** The term "Preview" is a more precise term, because it triggers
a frontend preview, and cannot be misunderstood as "viewing" a page in the backend page
context.

**Migration:** Since the module is already internally referred to as `page_preview`, no
changes in referencing modules are required.

Workspaces => Publish
~~~~~~~~~~~~~~~~~~~~~

The second-level :guilabel:`Workspaces` module has been renamed to
:guilabel:`Publish` to better match its current scope.

**Rationale:** The initially introduced "Workspaces administration" tool
has been reworked to move content through a publishing process in past
versions. For this reason, it is now renamed to "Publish" and
is only visible when inside a workspace.

**Migration:** The module has internally been renamed to `workspaces_publish`.
A module alias is in place, so references to the old `workspaces_admin`
identifier keep working as before, but it is recommended to adapt usages.

The upgrade wizard "Migrate module permissions" migrates backend user and
group-level permissions for this module.

Info, Indexing, Check Links => Status
--------------------------------------

The second-level :guilabel:`Info` module has been renamed to :guilabel:`Status`
to better match its scope. It has also been moved into EXT:backend so it is
always available and displayed if it contains at least one module.

**Rationale:** The module was moved to EXT:backend to make it always available
and to provide a common place for page and site status information.

The renaming to :guilabel:`Status` better reflects its purpose and removes
unnecessary dependencies between informational extensions.

**Migration:** The module identifier has been renamed from `web_info` to
`content_status` an alias is in place. Use the new identifier to place custom
modules.

..  code-block:: diff

     return [
         'my_page_information' => [
    -        'parent' => 'web_info',
    +        'parent' => 'content_status',
         ],
     ];

Extensions placing third level modules into the module now called
:guilabel:`Status` do not need to require :composer:`typo3/cms-info` anymore.

Filelist => Media
~~~~~~~~~~~~~~~~~

The second-level :guilabel:`Filelist` module has been renamed to :guilabel:`Media`
to more accurately reflect its current functionality and scope.

**Rationale:** The former “Filelist” no longer reflected what the module
actually does. Over the years, its scope has evolved from simply listing files
to offering a full set of media-management capabilities. Today, the module is
used to upload and create files and folders, manage metadata, organize assets,
handle online media, and prepare files for use across the CMS.

To make its purpose clearer and more intuitive for editors and integrators,
the module has been renamed to "Media". The new name better represents its
broader functionality, aligns with modern CMS terminology, and makes the
module easier to understand for new users.

**Migration:** Since the module is already internally referred to as `media_management`,
no changes in referencing modules are required.

Sites => Setup
~~~~~~~~~~~~~~~

The second-level :guilabel:`Sites` module has been renamed to :guilabel:`Setup`.

**Rationale:** The old submodule name "Sites" would duplicate the new top-level
name and cause confusion. The new name "Setup" therefore makes the purpose
clearer: It is the place where integrators set up their sites. "Setup"
emphasizes the technical nature of the module and better communicates that
this section defines behavior (languages, domains, routes), not content.

**Migration:** Since the module identifier `site_configuration` is kept, no
changes in referencing modules are required.

Settings => Setup
-----------------

The second level :guilabel:`Settings` module has been integrated into :guilabel:`Setup`.

**Rationale:** Combining the "Setup" and "Settings" gives a more concise view,
since managing sites and site settings are often done as one task.

**Migration:** The module identifier `site_settings` has been removed, the existing
actions :php:`edit`, :php:`save` and :php:`dump` have been renamed to
:php:`editSettings`, :php:`saveSettings` and :php:`dumpSettings` as part of the
`site_configuration` module identifier.


System > Backend Users => Administration > Users
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The second-level :guilabel:`System > Backend Users` module has been renamed
to :guilabel:`Administration > Users` to better match its scope.

It has also been moved to the top of the to :guilabel:`Administration` top level
menu as it is frequently used by administrators.

**Rationale:** The new name "Users" is shorter and easier to recognize in the
module menu. While "Backend Users" was technically precise, the simpler term
improves readability and usability, making the module easier to find for
administrators performing common user management tasks.

**Migration:** The identifier `backend_user_management` is kept unchanged, no
migration needed.


Impact
======

All renamed module identifiers maintain their previous names as aliases,
ensuring full backward compatibility. Existing code, configurations, and
third-party extensions continue to work without modification.

Developers are encouraged to update their code to use the new identifiers
for consistency and clarity.

The modernized naming improves the overall user experience by making the
backend more intuitive and easier to navigate, particularly for users familiar
with other enterprise CMS platforms.

..  index:: Backend, PHP-API, ext:backend, ext:core
