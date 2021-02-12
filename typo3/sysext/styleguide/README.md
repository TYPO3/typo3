![tests](https://github.com/TYPO3/styleguide/workflows/tests/badge.svg)

TYPO3 CMS Backend Styleguide
============================

Welcome to the living Styleguide for TYPO3 CMS backend.
Presents supported styles for TYPO3 backend modules.

![](Documentation/styleguide_index.png)

[Official repository for TYPO3 CMS extension "styleguide" with changelog.](https://github.com/TYPO3/styleguide)

# Table of content

1. Typography
2. TCA / Records
3. Trees
4. Tab
5. Tables
6. Avatar
7. Buttons
8. Infobox
9. FlashMessages / Notification
10. Icons
11. Debug
12. Helpers

# Installation
This Styleguide comes as a TYPO3 extension for the TYPO3 backend. It appears as backend module within the Help/Manuals navigation section.

## Composer
With composer based [TYPO3 installation](https://wiki.typo3.org/Composer) add this Styleguide by running the following command on shell within project root (where the root composer.json file resides):

```
composer require --dev typo3/cms-styleguide
```

Composer will automatically find, download and extract the appropriate version into extension manager.
After that, activate Styleguide extension from TYPO3 backend in Extension Manager, or via cli:

```
bin/typo3 extension:activate styleguide
```

## TYPO3 Extension Repository
The extension is available in TER with extension key `styleguide` and can be installed using the
extension manager.

# Usage
Once loaded, the extension will hook into the '?' menu in the toolbar of your TYPO3 backend. See
above screenshot. It will show a happy little menu with tons of functionality. Have fun to explore!

Menu section 'TCA / Records' allows to create a set of demo data. Clicking 'Create ...' over there, your
system will be busy with some record crunching for a while. Give it some time until a 'Data created' message
shows up. Going to the Web -> List modules afterwards, a new page tree 'styleguide TCA demo' has been created
with lots of sub pages. 'elements basic' is a good start, just open the 'English' default language record
to be impressed by all the capabilities of just the 'simple' TCA types. If you want to use something like
that in own extension, have a look at the Configuration/TCA folder for details.

# Legal
This project is released under GPLv2 license. See LICENSE.txt for details.

* The "tree" icon is from [Yusuke Kamiyamane](http://p.yusukekamiyamane.com/)
* Placeholder texts are from [Bacon Ipsum](http://baconipsum.com/)
