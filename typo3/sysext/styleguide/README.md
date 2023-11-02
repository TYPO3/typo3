TYPO3 CMS Backend Styleguide
============================

![](Documentation/styleguide_index.png)


# What is it?

Styleguide is a TYPO3 extension. It provides a backend module in the
"System" section of the backend. It can create a page tree to show examples.

When TYPO3 core adds or deprecates features  covered by this extension, core developers
strive to keep it updated, reflecting  these changes.

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

* Styleguide is used by **core developers** to test and verify changes to JavaScript, HTML
  and PHP code do not break layout or functionality of backend modules. The extension
  is also used in core backend acceptance tests to verify FormEngine details do not
  break when developing core patches.

* Styleguide is used within the official core documentation to provide examples, screenshots
  and possible usages of core functionality. Especially the [TCA reference](https://docs.typo3.org/m/typo3/reference-tca/master/en-us/)
  heavily relies on it.

# Installation

Styleguide comes as a TYPO3 extension for the TYPO3 backend. After initial installation, it is
advisable  to let styleguide create an example page tree with records by clicking the
"TCA / records -> Create styleguide page tree with data", and waiting for a couple of
seconds for the system to crunch the data.

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

# Legal

This project is released under GPLv2 license. See LICENSE.txt for details.

* Placeholder texts are from [Bacon Ipsum](http://baconipsum.com/)
