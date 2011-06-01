*******************************************************************************
System Extensions for TYPO3
*******************************************************************************

This document is a part of the TYPO3 project. TYPO3 is an open source web
content management system released under the GNU GPL. TYPO3 is copyright
(c) 1999-2011 by Kasper Skaarhoj.

This document provides information about the content of the typo3/sysext
folder.

===============================================================================
Content
===============================================================================

This is the repository for extensions which are shipped with the core. They are
regular extensions with the difference that they are not available on the TYPO3
Extension Repository (ter), but instead are part of the Core packages.

Since they are shipped with the TYPO3 core (typo3_src package), their content
will change when updating the core. They are packaged together with the core
by the release team, so updating the core will update these extensions too.

===============================================================================
Git Repositories
===============================================================================

Most of these extensions are developed by the core team itself and are
maintained directly in the main Core Git repository:

http://git.typo3.org/TYPO3v4/Core.git

Some of the extensions are developed by external teams (e.g. dbal, version,
workspaces) and are thus maintained in separate Git repositories and linked
into the core as submodules.

In order to retrieve them together with the core, issue these commands:

git pull
git submodule update --init