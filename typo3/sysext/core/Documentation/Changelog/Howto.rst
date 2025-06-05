..  include:: /Includes.rst.txt

.. _changelog_howto_index:

===================
Documenting Changes
===================

.. _changelog_howto_goals:

Motivation and goals
====================

.. sidebar:: Forger

    If you want to save yourself some time you can use the
    `ReST-File-Generator <https://forger.typo3.com/utilities/rst>`_.

    Select the type of ReST snippet you want to create, enter your issue number
    and click the search button.

A dedicated changelog of important changes is established to inform users,
developers and other Core related teams or projects:

-   **Notify extension developers, integrators and project developers** about
    changes in the Core, like breaking changes, deprecations, database updates,
    and security-related changes.

-   Inform the **documentation team** about changes that must trigger documentation changes.
    A dedicated GitHub-Actions-based workflow has been set-up for this on
    `the Changelog-To-Doc repository <https://github.com/TYPO3-Documentation/Changelog-To-Doc/>`_.

-   Allow **release managers** and other "Spread the word" / marketing-related people to catch up
    on important changes.

-   Rely on a **standardized format** for easy usage in scripts like a Core migration or
    release notes system.


.. _changelog_howto_types:

Different changelog types
=========================

A changelog handles one of these change types:

Breaking change
    A patch moved or removed a specific part of **Core functionality that may
    break or affect third-party code** (custom extensions, sitepackages, integrations).
    Removal of previously deprecated code or an interface / API change are good examples
    of this type.

Deprecation
    A patch deprecates a certain Core functionality for a **planned removal**. This may
    also involve possible TCA migrations and related database changes.

Feature
    A patch adds **new functionality**.

Important
    Any other important message which **may require manual action**. This should be
    regarded as a last-resort, if a patch cannot be related to any of the prior types.
    This type is vital for giving important information on LTS releases,
    as they are not allowed to have new features, deprecations or breaking changes.

Casual bug fixes do not need changelog files (their information is covered through
well-maintained commit messages), but every change that may be of
special interest for extension developers or documentation writers should
receive an entry. The changelog file must be provided as part of the patch that
introduces the change.

.. _changelog_howto_location:

Location
========

New changelog files are added to the directory of the minor version that they
will be released in, for example
:file:`typo3/sysext/core/Documentation/Changelog/13.1` directory.

This way, it can be easily seen which change has been introduced in which
released TYPO3 version.

In rare cases, patches worth a changelog file need to be backported to stable
LTS and / or old stable LTS versions. Those should be put into a different
directory, depending on lowest target LTS versions. We'll explain this by example:

Suppose Core is currently developing v14, a first 14.0 has been released, so
the Git branch `main` will become version 14.1.0 with the
next sprint release. So new Changelog entries so far were saved in folder
:file:`typo3/sysext/core/Documentation/Changelog/14.0`.
The stable LTS version is currently 13.4.y, so the Git branch `13.4` will
become version 13.4.y+1 with the next stable LTS patch level release.
The old stable LTS version is currently 12.4.y, so the Git branch `12.4`
will become version 12.4.y+1 with next old stable LTS
patch level release.

Example scenarios:

*   **A patch is only added to main:** Put the :file:`.rst` file into the
    :file:`typo3/sysext/core/Documentation/Changelog/14.1`
    directory in the `main` branch. The Core and documentation team may
    re-review files in this directory shortly before the 14.1 release.

*   **A patch is not only added to main, but also backported to v13:**
    Put the :file:`.rst` file into the
    :file:`typo3/sysext/core/Documentation/Changelog/13.4.x` directory in
    the `main` branch.
    The backport to `13.4` branch includes the changelog file into
    :file:`13.4.x` directory, too.
    Users upgrading to latest patch level release of 13.4 will then see the
    new file in the :file:`13.4.x` directory (and on the `published changelog
    website <https://docs.typo3.org/c/typo3/cms-core/main/en-us/Index.html>`_).

*   **A patch is not only added to main, but backported to v13 and v12:**
    Put the :file:`.rst` file into
    :file:`typo3/sysext/core/Documentation/Changelog/13.4.x` and a duplicate into
    :file:`typo3/sysext/core/Documentation/Changelog/12.4.x` directories in the
    `main` branch.
    The backport to the `13.4` branch will have the two identical files in both
    directories, too.
    The `12.4 branch backport contains only the
    :file:`typo3/sysext/core/Documentation/Changelog/13.4.x`, because the `13.4.x`
    directory does not exist in that branch.

    Users upgrading to latest 12.4 patch level or the latest 13.4 patch level
    will then see the new file in their matching version directories respectively.

    Bear in mind that backports to older LTS releases only are made for "very
    important fixes", so conceptually this will only ever apply to "Important"
    type ReST files, and only in rare exemptions to "Breaking" types.


The main goal of this approach is to have a consistent state of changelog files
across branches. Changelog files are added to the oldest release branch where
a change has been backported to, thus basically the first TYPO3 version where
a change is visible. Changelog files from older releases are never deleted in
younger branches.

Old changelog files are still rendered in
:guilabel:`Admin Tools > Upgrade > View Upgrade Documentation` and are
connected to the extension scanner at
:guilabel:`Admin Tools > Upgrade > View Upgrade Documentation`. In our example
above, the `main` branch contains all changelog files for any prior TYPO3 LTS
release (13.4.x, 12.4.x).

Only changelog files in actively maintained LTS and sprint
releases will be revised/maintained, and always kept with the same content
across all branches.


.. _changelog_howto_filename_convention:

Filename convention
===================

.. code-block:: none

    <type>-<forgeIssueNumber>-<UpperCamelCaseDescription>.rst

For example

.. code-block:: none

    Deprecation-95800-GeneratingPublicURLForPrivateAssetFiles.rst

.. _changelog_howto_file_content:

File content
============

Like other documentation, changelog files are done in ReST, see
:ref:`h2document:rest-cheat-sheet` for more details.

-   A **headline** and a **unique identifier** (UNIX timestamp by convention) needs
    to be present

-   A link to an **issue number** (forge) needs to be present

-   All types contain a **"Description"** section that should give a short summary
    on which Core part was affected by the change.

-   All types apart from "Important" contain an **"Impact"** section that describes
    the possible impact of a change. An example is "Frontend output may change",
    "Configuration of xy is easier" or "Backend will throw a fatal error".

-   Types "Deprecation" and "Breaking" contain an **"Affected installations"**
    section that describes when and if a TYPO3 instance is affected by a change.
    Example: "Extension xy is in use" or "TypoScript functionality xy is used"
    or "System is based on PHP 8.2".

-   Types "Deprecation" and "Breaking" contain a **"Migration"** section to
    describe best practices and code examples (before/after) on how to cope with
    a specific change.

-   All types contain a **list of tags**, see below.

.. _changelog_howto_tagging:

Tagging
=======

To provide the possibility to filter ReST files by topics, it is mandatory to
equip every :file:`.rst` file with at least two tags. As a rule of thumb a file
should have no more than five tags. Please limit yourself to the list
provided below. If you are in dearly need to introduce a new tag, you must
also add it to the list (and explain it) in this file as a reference
for everyone.

The tag list should be located at the end of a ReST file prefixed with the
index keyword, example: `.. index:: Backend, JavaScript, NotScanned`.

List of all possible tags:

Backend
    Affects behavior or rendering of the TYPO3 Backend.

CLI
    Affects CommandLine Interface (Commands/Shell) functionality.

Database
    Modifies behavior of the database abstraction or introduces or remove/add new fields.

FAL
    Affects the File Abstraction Layer.

FlexForm
    Affects FlexForm functionality.

Fluid
    Affects behavior of Fluid, like introducing new tags or
    modify already established ones (attributes, special processing).

Frontend
    Affect the behavior or rendering of the TYPO3 Frontend.

LocalConfiguration
    Affects the :file:`system/settings.php` or the subordinated
    :file:`system/additional.php`.

JavaScript
    Affects changes in the JavaScript-API (mostly Backend-related).

PHP-API
    Affects implementations of mandatory changes of the TYPO3 API available in PHP.

RTE
    Affects the RTE functionality (CKEditor).

TCA
    Affects Table Configuration Array processing or new/changed options.

TSConfig
    Affects the PageTS or UserTS (mind the exact casing of this tag, not "TSconfig"),
    like new or changed options, or changes in processing.

TypoScript
    Affects alterations to TypoScript settings or modifies the behavior of TypoScript
    itself. Frontend TypoScript only.

YAML
    Affects YAML configuration syntax, like new or changed configuration keys or handling
    of YAML-specific features.

ext:xyz
    Changes on extension xyz. Please refer to this tag only when changing system extensions.

Furthermore, exactly one of the following tags **must** be added for all
`Deprecation` and `Breaking` ReST files:

NotScanned
    If this ReST file is not covered by the extension scanner at all.

PartiallyScanned
    If some parts of the deprecated / removed functionality can be found by
    the extension scanner.

FullyScanned
    If usages of all deprecated / removed functionality this ReST file is
    about can be found by the extension scanner. This tag is used by
    the extension scanner to mark a ReST file as "You are not affected by this
    in your codebase" if it does not find a match in extensions.

The automatic CI/commithook integration (see :file:`Build/Scripts/validateRstFiles.php`
in the TYPO3 GIT repository) will flag/report some common errors in ReST files, like:

*   Some (basic) errors in ReST syntax
*   Missing title or structural errors (missing issue number)
*   Missing unique identifier
*   Missing tags

.. _changelog_howto_rendering:

Local rendering
===============

The documentation can be locally rendered with the Docker container of the
documentation team (hint: use a bash alias for this locally):

..  code-block:: bash

    # Execute this from the root of the TYPO3 GIT repository
    docker run --rm --pull always -v ./:/project/ \
      ghcr.io/typo3-documentation/render-guides:latest \
      --config=typo3/sysext/core/Documentation typo3/sysext/core/Documentation

As of now, you can only render the full changelog documentation, not a single changelog
file on its own. The rendered HTML docs will be placed in directory :file:`./Documentation-GENERATED-temp/`
for manual verification.

Details can be found on:
`Rendering the Documentation folder locally with Docker <https://docs.typo3.org/permalink/h2document:render-documentation-with-docker>`_

