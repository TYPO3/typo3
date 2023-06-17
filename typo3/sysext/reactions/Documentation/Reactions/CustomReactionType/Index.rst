..  include:: /Includes.rst.txt

..  _custom-reaction-type:

====================
Custom reaction type
====================

A custom reaction type may be useful, if the
:ref:`create database record <create-database-record>` type is not sufficient.

As an example the following scenario is used:

We want to synchronize products from an external system into TYPO3. As the
synchronization may be time-consuming (think of synchronizing images), the
real work is done by a command. Therefore, the reaction receives an ID from
a product which was added or changed. This ID is stored in the
:sql:`sys_registry` table.

A command (which is not part of this example) runs regularly and synchronizes
every product ID stored in the registry entry into TYPO3.

You can find the implemented reaction type in the :t3ext:`examples` extension.

Create the reaction type
========================

To create a custom reaction type, we add a class which implements the
:t3src:`reactions/Classes/Reaction/ReactionInterface.php`:

..  literalinclude:: _ExampleReactionType.php
    :language: php
    :caption: EXT:examples/Classes/Reaction/ExampleReactionType.php

You can use :ref:`constructor injection <t3coreapi:Constructor-injection>` to
inject necessary dependencies.

Add reaction type to select list in backend module
==================================================

In a next step we add the newly created reaction type to the list of
reaction types in the backend:

..  literalinclude:: _sys_reaction.php
    :language: php
    :caption: EXT:examples/Configuration/TCA/Overrides/sys_reaction.php

Now, our newly created type is displayed and can be selected:

..  figure:: /Images/CustomReactionType.png
    :alt: Selecting our custom reaction type
    :class: with-shadow

    Selecting our custom reaction type
