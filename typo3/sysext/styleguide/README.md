![tests](https://github.com/TYPO3/styleguide/workflows/tests/badge.svg)

TYPO3 CMS Backend Styleguide
============================

![](Documentation/styleguide_index.png)


# What is it?

Styleguide is a TYPO3 extension. It provides a backend module that hooks
into the 'Help' menu of the top toolbar of the TYPO3 Backend. It can also create a
page tree to show examples.

This extension in maintained in the official [TYPO3 github organization.](https://github.com/TYPO3/styleguide)

Styleguide is developed "core-near": When TYPO3 core adds or deprecates features
covered by this extension, core developers strive to keep it updated, reflecting
these changes.

Styleguide is a reference to show a lot of TYPO3 backend features, often relevant
for own extensions:

* A set of snippets showing how to use default backend functionality like
  tables, buttons, boxes or notifications.
* A huge set of 'TCA' examples, showing "all" features of the backend editing forms.


# Usages

* The extension is interesting for **backend extension developers** as a reference
  to see how casual stuff like buttons and other HTML related things are solved or
  used in the backend, and to copy+paste solutions. Additionally, the TCA examples
  is a near-complete show-case of [FormEngine](https://docs.typo3.org/m/typo3/reference-coreapi/10.4/en-us/ApiOverview/FormEngine/Index.html)
  (editing records in the backend). Developers will see new things they did not
  know yet. Guaranteed!

* The extension can be interesting for **technical project managers** to get an idea
  of what the backend editing is capable of out-of-the-box and which parts can be
  sold to customers without adding expensive implementation burdens to developers.

* Styleguide is a "require-dev" dependency of the [TYPO3 CMS core mono repository](https://github.com/typo3/typo3).
  It is used by **core developers** to test and verify changes to JavaScript, HTML
  and PHP code do not break layout or functionality of backend modules. The extension
  is also used in core backend acceptance tests to verify FormEngine details do not
  break when developing core patches.

* Styleguide is used within the official core documentation to provide examples, screenshots
  and possible usages of core functionality. Especially the [TCA reference](https://docs.typo3.org/m/typo3/reference-tca/master/en-us/)
  heavily relies on it.

* Styleguide comes with a simple set up of unit, functional and acceptance tests that
  are executed by github action workflow "tests.yml" - or locally if desired. This setup
  is documented as a working test set up example within the official [TYPO3 explained testing section](https://docs.typo3.org/m/typo3/reference-coreapi/10.4/en-us/Testing/Index.html)
  and can be used as a copy+paste boilerplate in own extensions.

# Installation

Styleguide comes as a TYPO3 extension for the TYPO3 backend. It appears as backend module
within the "Help" section of the top toolbar. After initial installation, it is advisable
to let styleguide create an example page tree with records by clicking the
"TCA / records -> Create styleguide page tree with data", and waiting for a couple of
seconds for the system to crunch the data.

## Composer
With [composer based](https://docs.typo3.org/m/typo3/tutorial-getting-started/main/en-us/Installation/Install.html)
TYPO3 installations, styleguide is easily added to the project.

TYPO3 v11 based project:

```
composer require --dev typo3/cms-styleguide:^11
```

TYPO3 v10 based project:

```
composer require --dev typo3/cms-styleguide:^10
bin/typo3 extension:activate styleguide
```

## TYPO3 Extension Repository
For non-composer projects, the extension is available in TER as extension key `styleguide` and can
be installed using the extension manager.

# Initialization

With styleguide, it is possible to automatically create sample pages and content for 2 purposes:

1. "styleguide TCA demo" to showcase various TCA features
2. "styleguide frontend demo" to create pages which can be used for Frontend testing

These pages can be created either in the backend or on the command line:

```
# show help
bin/typo3 styleguide:generate -h

# create pages
bin/typo3 styleguide:generate -c
```

Alternatively, the pages can be created in the TYPO3 backend:

1. Access the styleguide module by clicking on the question mark in the top bar:
   ? | Styleguide | TCA / Records / Frontend
2. Click the available buttons

# Usage

Styleguide comes with a module which is available by clicking on the question mark in the top bar:
? | Styleguide.

You can also peruse through the TCA demo by selecting the pages in the page tree. Use the list module to
get access to the records.

The TYPO3 TCA reference documentation often uses examples from the styleguide.

# Running tests

Styleguide comes with a simple demo set of unit, functional and acceptance tests. It relies
on the runTests.sh script which is a simplified version of a similar script from the TYPO3 core.
Find detailed usage examples by executing `Build/Scripts/runTests.sh -h` and have a look at
`.github/workflows/tests.yml` to see how this is used in CI.

Example usage:

```
Build/Scripts/runTests.sh -s composerUpdate
Build/Scripts/runTests.sh -s unit
```


# Tagging and releasing

[packagist.org](https://packagist.org/packages/typo3/cms-styleguide) is enabled via the casual github hook.
TER releases are created by the "publish.yml" github workflow when tagging versions
using [tailor](https://github.com/TYPO3/tailor). The commit message of the commit a tag points to is
used as TER upload comment.

Example:

```
composer install
.Build/bin/tailor set-version 11.0.3
git commit -am "[RELEASE] 11.0.3 Bug fixes and improved core v11 compatibility"
git tag 11.0.3
git push
git push --tags
```


# Legal
This project is released under GPLv2 license. See LICENSE.txt for details.

* The "tree" icon is from [Yusuke Kamiyamane](http://p.yusukekamiyamane.com/)
* Placeholder texts are from [Bacon Ipsum](http://baconipsum.com/)
