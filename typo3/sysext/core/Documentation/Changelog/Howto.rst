
.. include:: ../Includes.txt

===================
Documenting Changes
===================

Motivation and goals
====================

.. sidebar:: Forger

   If you want to save yourself some time you can use the ReST-File-Generator at https://forger.typo3.org/utility/rst

   Select the type of ReST snippet you want to create, enter your issue number and click the search button.

Spreading information about important core changes has been a problematic issue ever since. With the switch to the
new release cycle with version 7 of the TYPO3 CMS core this situation should be improved.

A dedicated changelog of important changes is established to inform users, developers and other core related
teams or projects:

- Overview for documentation team which changes trigger documentation changes.

- Overview for Release Managers and other "Spread the word" related works on important changes.

- Hints for extension developers, integrators and project developers whose own code areas may need adjustments on core updates.

- Standardized format for easy usage in scripts like a core migration or release notes system.

This structure replaces the old `NEWS.md` file.


Different changelog types
=========================

A changelog handles one of these change types:

- **Breaking change**: A patch moved or removed a specific part of core functionality that may break extensions if they use
  this part. Removal of deprecated code or an interface change are good examples of this type.

- **Deprecation**: A patch deprecates a certain core functionality for a planned removal.

- **Feature**: A patch adds new functionality.

- **Important**: Any other important message which may require manual action.

Casual bug fixes do not need changelog files, but every change that may be of special interest for extension developers
or documentation writers should receive an entry. The changelog file must be provided as part of the patch that
introduces the change.


Location
========

New changelog files should usually be added to the :file:`typo3/sysext/core/Documentation/Changelog/master` directory. If a
version is to be released, all files in this directory will be moved to a directory that is named after the release number.
This way it can be easily seen which change has been introduced in which released TYPO3 version.

In rare cases, patches worth a changelog file need to be backported to stable LTS and / or old stable LTS versions. Those
should be put into a different directory, depending on target LTS versions. We'll explain this by example:

Suppose core is currently developing v9, a first 9.0 has been released, so the Git branch `master` will become version 9.1.0 with the
next sprint release.
The stable LTS version is currently 8.7.9, so the Git branch `TYPO3_8-7` will become version 8.7.10 with the next stable LTS patch level release.
The old stable LTS version is currently 7.6.23, so the Git branch `TYPO3_7-6` will become version 7.6.24 with next old stable LTS
patch level release.

Example scenarios:

* **A patch is only added to master:** Put the :file:`.rst` file into the :file:`typo3/sysext/core/Documentation/Changelog/master`
  directory in the `master` branch. The core team will re-review files in this directory 
  shortly before the 9.1.0 release and will move all files from the :file:`master` into the :file:`9.1` directory.

* **A patch is not only added to master, but also backported to v8:** Put the :file:`.rst` file into the
  :file:`typo3/sysext/core/Documentation/Changelog/8.7.x` directory in the `master` branch. 
  The backport to `TYPO3_8-7` branch includes the changelog file into :file:`8.7.x` directory, too.
  Users upgrading to latest patch level release of 8.7 will then see the new file in the :file:`8.7.x` directory.

* **A patch is not only added to master, but backported to v8 and v7:** Put the .rst
  into :file:`typo3/sysext/core/Documentation/Changelog/8.7.x` and a duplicate into
  :file:`typo3/sysext/core/Documentation/Changelog/7.6.x` directories in the `master` branch.
  The backport to the `TYPO3_8-7` branch have the two identical files in both directories, too.
  The `TYPO3_7-6` branch backport contains only the :file:`typo3/sysext/core/Documentation/Changelog/7.6.x`, the
  `8.7.x` directory does not exist in the version 7 branch.
  Users upgrading to latest 7.6 patch level or the latest 8.7 patch level will then see
  the new file in :file:`7.6.x` directory or in :file:`8.7.x` directory, respectively.

The main goal of this approach is to have a consistent state of changelog file across branches. 
Changelog files are added to the oldest release branch where a change has been backported to, thus basically 
the first TYPO3 version where a change is visible. Changelog files from older releases are never deleted in younger branches. 
They are still rendered in the install tool
"View Upgrade Documentation" and are connected to the "Extension scanner". In our example above, the `master`branch contains
all changelog files for TYPO3 v9, v8 and v7, the branch `TYPO3_8-7` contains all files for TYPO3 v8 and v7, and the branch
`TYPO3_7-6` contains all v7 files.

Furthermore, documentation files from older releases should be identical in all branches. If a patch improves some
documentation file from a v7 directory, this change should be put into all branches: `master`, `TYPO3_8-7`
and `TYPO3_7-6` for consistency. The core team will check for differences of files between branches occasionally
and will align them in case they diverged.


Filename convention
===================

<type>-<forgeIssueNumber>-<UpperCamelCaseDescription>.rst


File content
============

Like other documentation, changelog files are done in ReST, see `TYPO3 wiki ReST syntax`_ for more details.

- All types contain a "Description" section that should give a short summary on which core part was affected by the change.

- All types contain an "Impact" section that describes the possible impact of a change. An example is "Frontend output
  may change", "Configuration of xy is easier" or "Backend will throw a fatal error".

- Types "Deprecation" and "Breaking" contain an "Affected installations" section that describes when and if a TYPO3 instance
  is affected by a change. Example: "Extension xy is in use" or "TypoScript functionality xy is used" or "System is based on PHP 5.3".

- Types "Deprecation" and "Breaking" contain a "Migration" section to describe best practices on how to cope with a specific change.

- All types contain a list of tags, see below.

.. _TYPO3 wiki ReST syntax: http://wiki.typo3.org/ReST_Syntax


Tagging
=======

To provide the possibility to filter ReST files by topics, it is mandatory to equip every RST file with at least two tags.
As a rule of thumb a file should have no more than five tags. Please limit yourself to the list provided below. If you
are in dearly need to introduce a new tag, you must also add it to the list (and explain it) in this file as a reference
for everyone.

The tag list should be located at the end of a ReST file prefixed with the index keyword,
example:: ``.. index:: Backend, JavaScript, NotScanned``.

List of all possible tags:

- TypoScript - Changes that imply or introduce alterations to some TypoScript settings or modify the behavior of TypoScript
  itself. Frontend TypoScript only.

- TSConfig - Changes or modifications on the PageTS or UserTS or the behavior of this field.

- TCA - Every change related to TCA.

- FlexForm - Changes affecting FlexForm functionality.

- LocalConfiguration - Changes that affect the LocalConfiguration.php or the subordinated AdditionalConfiguration.php

- Fluid - Changes that alter behavior of Fluid like introducing new tags or modifying already established ones.

- FAL - Changes to File Abstraction Layer.

- Database - Changes that modify behavior of the database abstraction or introduces or removes new fields.

- JavaScript - Modifying or introducing JavaScript.

- PHP-API - Implementations of mandatory changes of the PHP-API.

- Frontend - Changes that will affect the behavior or rendering of the TYPO3 Frontend.

- Backend - Changes that will affect the behavior or rendering of the TYPO3 Backend.

- CLI - Changes affecting CLI functionality.

- RTE - Changes to RTE functionality.

- ext:xyz - Changes on extension xyz. Please refer to this tag only when changing system extensions.

Furthermore, exactly one of the following tags *must* be added for all "Deprecation" and "Breaking" ReST files since TYPO3 v9 and above:

- NotScanned - If this ReST file is not covered by the extension scanner at all.

- PartiallyScanned - If some parts of the deprecated / removed functionality can be found by the extension scanner.

- FullyScanned - If usages of all deprecated / removed functionality this ReST file is about can be found by the
  extension scanner. This tag is used by the extension scanner to mark a ReST file as "You are not affected by this in your codebase"
  if it does not find a match in extensions.
