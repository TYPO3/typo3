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
