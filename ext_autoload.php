<?php
$extensionPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('rescue_reports');

return array(
    'tx_rescuereports_pi1' => $extensionPath . 'pi1/class.tx_rescuereports_pi1.php',
    'In2code\\RescueReports\\Controller\\EventController' => $extensionPath . 'Classes/Controller/EventController.php',
    'In2code\\RescueReports\\Hooks\\VehicleNameAutoFill' => $extensionPath . 'Classes/Hooks/VehicleNameAutoFill.php',
    'In2code\\RescueReports\\Domain\\Model\\Organisation' => $extensionPath . 'Classes/Domain/Model/Organisation.php',
    // ggf. weitere manuell registrieren
);