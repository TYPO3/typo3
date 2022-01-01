<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

ExtensionManagementUtility::allowTableOnStandardPages(
    'tx_irretutorial_mnasym_hotel,tx_irretutorial_mnasym_hotel_offer_rel'
);
ExtensionManagementUtility::allowTableOnStandardPages(
    'tx_irretutorial_mnasym_offer,tx_irretutorial_mnasym_price,tx_irretutorial_mnattr_offer'
);
ExtensionManagementUtility::allowTableOnStandardPages(
    'tx_irretutorial_mnattr_hotel,tx_irretutorial_mnattr_hotel_offer_rel'
);
