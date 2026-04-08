<?php
declare(strict_types=1);

defined('TYPO3') or die();

use Nkfire\RescueReports\Controller\EventController;
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

    // Statistik-Plugin
    ExtensionUtility::configurePlugin(
        'RescueReports',
        'Statistics',
        [
            EventController::class => 'statistics',
        ],
        [
            EventController::class => '',
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

    // RSS-Feed-Plugin
    ExtensionUtility::configurePlugin(
        'RescueReports',
        'Rss',
        [
            EventController::class => 'rss',
        ],
        [
            EventController::class => '',
        ]
    );

})();

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][] = [
    'nodeName' => 'eventVehicleAssignment',
    'priority' => 40,
    'class' => \Nkfire\RescueReports\Form\Element\EventVehicleAssignmentElement::class,
];

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] =
    \Nkfire\RescueReports\Hooks\VehicleNameAutoFill::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] =
    \Nkfire\RescueReports\Hooks\DataHandlerHook::class;