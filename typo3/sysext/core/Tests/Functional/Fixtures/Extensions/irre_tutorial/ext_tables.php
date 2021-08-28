<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

ExtensionManagementUtility::allowTableOnStandardPages(
    'tx_irretutorial_1nff_hotel,tx_irretutorial_1nff_offer,tx_irretutorial_1nff_price,tx_irretutorial_mnasym_hotel,tx_irretutorial_mnasym_hotel_offer_rel,tx_irretutorial_1ncsv_hotel,tx_irretutorial_1ncsv_offer'
);
ExtensionManagementUtility::allowTableOnStandardPages(
    'tx_irretutorial_mnasym_offer,tx_irretutorial_mnasym_price,tx_irretutorial_mnmmasym_hotel,tx_irretutorial_mnmmasym_offer,tx_irretutorial_mnattr_offer,tx_irretutorial_1ncsv_price'
);
ExtensionManagementUtility::allowTableOnStandardPages(
    'tx_irretutorial_mnmmasym_price,tx_irretutorial_mnsym_hotel,tx_irretutorial_mnsym_hotel_rel,tx_irretutorial_mnattr_hotel,tx_irretutorial_mnattr_hotel_offer_rel'
);
