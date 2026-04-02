<?php
declare(strict_types=1);

defined('TYPO3') or die();

use In2code\RescueReports\Controller\EventController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

(function (): void {

    // Hauptplugin
    ExtensionUtility::configurePlugin(
        'RescueReports',
        'Eventlist',
        [
            EventController::class => 'list,show',
        ],
        [
            EventController::class => 'list',
        ]
    );

    // Sidebar-Plugin
    ExtensionUtility::configurePlugin(
        'RescueReports',
        'Sidebar',
        [
            EventController::class => 'list',
        ],
        [
            EventController::class => '',
        ]
    );

})();

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][] = [
    'nodeName' => 'eventVehicleAssignment',
    'priority' => 40,
    'class' => \In2code\RescueReports\Form\Element\EventVehicleAssignmentElement::class,
];

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] =
    \In2code\RescueReports\Hooks\VehicleNameAutoFill::class;