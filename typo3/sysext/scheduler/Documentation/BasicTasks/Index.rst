:navigation-title: Basic tasks

..  include:: /Includes.rst.txt
..  _base-tasks:

==========================================
The basic tasks provided by the TYPO3 Core
==========================================

The Scheduler comes by default with several tasks:

-   **Caching framework garbage collection** : some cache backends do not
    have an automatic garbage collection process. For these it is useful
    to run this Scheduler task to regularly free some space.

-   **Fileadmin garbage collection** : empties :file:`_recycler_` folders in
    the fileadmin.

-   **Table garbage collection** : cleans up old records from any table in
    the database. See related section below for more information on
    configuration.

Most TYPO3 console command can also be executed via scheduler.

The following tasks provide configuration options that need dedicated chapters:

..  toctree::
    :glob:
    :titlesonly:

    *

..  _other-tasks:

Providing custom tasks from your extension
==========================================

More tasks are provided by system extensions, such as the Extension
Manager, which defines one for updating the available extensions list.

The base tasks are also there to serve as examples for task developers
(see :ref:`developer-guide`).
