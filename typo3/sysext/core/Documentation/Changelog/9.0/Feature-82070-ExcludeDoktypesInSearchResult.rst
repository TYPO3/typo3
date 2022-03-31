.. include:: /Includes.rst.txt

============================================================================
Feature: #82070 - Exclude doktypes in path of search result (indexed_search)
============================================================================

See :issue:`82070`

Description
===========
After submitting the search form in Indexed Search, the search results are displayed.
Each search result displays a "path" and contains the page tree structure.
In the structure, system folders can be used, which actually can't be excluded when the path is rendered.
Similar to "hide in menu" or "RealUrl exclude from path segment", there should be a configuration to exclude doktypes
from the path render business logic.

Page tree structure:
[SysFolder] Footer -> [SysFolder] Navigation -> [Page] Imprint

Output in Indexed Search without :php:`pathExcludeDoktypes` settings:
:php:`/Footer/Navigation/Imprint`

Output in Indexed Search with :php:`pathExcludeDoktypes` settings:
:php:`/Imprint`


Examples
~~~~~~~~

Exclude single doktype
**********************

sys_folder (doktype: 254)

.. code-block:: typoscript

    plugin.tx_indexedsearch {
        settings {
            results {
                pathExcludeDoktypes = 254
            }
        }
    }

Exclude multiple doktypes
*************************

sys_folder (doktype: 254) and shortcuts (doktype:4)

.. code-block:: typoscript

    plugin.tx_indexedsearch {
        settings {
            results {
                pathExcludeDoktypes = 254,4
            }
        }
    }

.. index:: Frontend, ext:indexed_search, TypoScript
