<?php
namespace In2code\RescueReports\Controller\Backend;

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;

class MigrationController extends ActionController
{
    public function indexAction(): void {}

    public function runAction(): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_rescuereports_einsatz');

        $rows = $connection->select(['uid', 'cars', 'type', 'station'], 'tx_rescuereports_einsatz')->fetchAllAssociative();

        $mmTables = [
            'cars' => 'tx_rescuereports_event_car_mm',
            'type' => 'tx_rescuereports_event_type_mm',
            'station' => 'tx_rescuereports_event_station_mm',
        ];

        $relationCount = ['cars' => 0, 'type' => 0, 'station' => 0];

        foreach ($rows as $row) {
            foreach (['cars', 'type', 'station'] as $field) {
                $values = GeneralUtility::trimExplode(',', $row[$field], true);
                $sorting = 0;
                foreach ($values as $foreignUid) {
                    GeneralUtility::makeInstance(ConnectionPool::class)
                        ->getConnectionForTable($mmTables[$field])
                        ->insert($mmTables[$field], [
                            'uid_local' => (int)$row['uid'],
                            'uid_foreign' => (int)$foreignUid,
                            'tablenames' => '',
                            'sorting' => $sorting++
                        ]);
                    $relationCount[$field]++;
                }
            }
        }

        $this->addFlashMessage('Migration abgeschlossen:<br>' .
            'Fahrzeuge: ' . $relationCount['cars'] . '<br>' .
            'Typen: ' . $relationCount['type'] . '<br>' .
            'Stationen: ' . $relationCount['station']);

        $this->redirect('index');
    }

    public function resetConfirmAction(): void {}

    public function resetAction(): void
    {
        foreach ([
            'tx_rescuereports_event_car_mm',
            'tx_rescuereports_event_type_mm',
            'tx_rescuereports_event_station_mm'
        ] as $table) {
            GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable($table)
                ->truncate($table);
        }

        $this->addFlashMessage('Alle MM-Einträge wurden zurückgesetzt.');
        $this->redirect('index');
    }
}