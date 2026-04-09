<?php

declare(strict_types=1);

namespace Nkfire\RescueReports\Updates;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

final class InitialDataWizard implements UpgradeWizardInterface
{
    /**
     * @var array<int,array{name:string,abbreviation:string,icon:string}>
     */
    private array $defaultOrganisations = [
        ['name' => 'Freiwillige Feuerwehr', 'abbreviation' => 'FF', 'icon' => '🚒'],
        ['name' => 'Rettungsdienst', 'abbreviation' => 'RD', 'icon' => '🚑'],
        ['name' => 'Polizei', 'abbreviation' => 'POL', 'icon' => '🚓'],
    ];

    /**
     * @var array<int,array{name:string,organisation:string}>
     */
    private array $defaultCars = [
        ['name' => 'HLF 20', 'organisation' => 'FF'],
        ['name' => 'TLF 3000', 'organisation' => 'FF'],
        ['name' => 'RTW', 'organisation' => 'RD'],
        ['name' => 'NEF', 'organisation' => 'RD'],
        ['name' => 'Streifenwagen', 'organisation' => 'POL'],
    ];

    public function getIdentifier(): string
    {
        return 'rescueReportsInitialDataWizard';
    }

    public function getTitle(): string
    {
        return 'Rescue Reports: Initialdaten anlegen';
    }

    public function getDescription(): string
    {
        return 'Legt für Neuinstallationen Standard-Organisationen und Fahrzeugtypen in PID 0 an.';
    }

    public function executeUpdate(): bool
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $organisationConnection = $connectionPool->getConnectionForTable('tx_rescuereports_domain_model_organisation');
        $carConnection = $connectionPool->getConnectionForTable('tx_rescuereports_domain_model_car');

        $pid = 0;
        $now = $GLOBALS['EXEC_TIME'] ?? time();

        $organisationMap = $this->loadOrganisationMap($organisationConnection, $pid);

        foreach ($this->defaultOrganisations as $organisation) {
            $keyByName = mb_strtolower($organisation['name']);
            $keyByAbbreviation = mb_strtolower($organisation['abbreviation']);

            if (isset($organisationMap[$keyByName]) || isset($organisationMap[$keyByAbbreviation])) {
                continue;
            }

            $organisationConnection->insert('tx_rescuereports_domain_model_organisation', [
                'pid' => $pid,
                'name' => $organisation['name'],
                'abbreviation' => $organisation['abbreviation'],
                'icon' => $organisation['icon'],
                'tstamp' => $now,
                'crdate' => $now,
            ]);

            $uid = (int)$organisationConnection->lastInsertId('tx_rescuereports_domain_model_organisation');
            $organisationMap[$keyByName] = $uid;
            $organisationMap[$keyByAbbreviation] = $uid;
        }

        foreach ($this->defaultCars as $car) {
            $organisationUid = (int)($organisationMap[mb_strtolower($car['organisation'])] ?? 0);
            if ($this->carExists($carConnection, $pid, $car['name'], $organisationUid)) {
                continue;
            }

            $carConnection->insert('tx_rescuereports_domain_model_car', [
                'pid' => $pid,
                'name' => $car['name'],
                'organization' => $organisationUid,
                'tstamp' => $now,
                'crdate' => $now,
                'sys_language_uid' => 0,
            ]);
        }

        return true;
    }

    public function updateNecessary(): bool
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $organisationCount = (int)$connectionPool
            ->getConnectionForTable('tx_rescuereports_domain_model_organisation')
            ->count('uid', 'tx_rescuereports_domain_model_organisation', ['deleted' => 0]);

        $carCount = (int)$connectionPool
            ->getConnectionForTable('tx_rescuereports_domain_model_car')
            ->count('uid', 'tx_rescuereports_domain_model_car', ['deleted' => 0]);

        return $organisationCount === 0 || $carCount === 0;
    }

    /**
     * @return array<int,string>
     */
    public function getPrerequisites(): array
    {
        return [DatabaseUpdatedPrerequisite::class];
    }

    /**
     * @return array<string,int>
     */
    private function loadOrganisationMap(Connection $connection, int $pid): array
    {
        $queryBuilder = $connection->createQueryBuilder();
        $rows = $queryBuilder
            ->select('uid', 'name', 'abbreviation')
            ->from('tx_rescuereports_domain_model_organisation')
            ->where(
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAllAssociative();

        $map = [];
        foreach ($rows as $row) {
            $uid = (int)$row['uid'];
            $name = mb_strtolower(trim((string)$row['name']));
            $abbreviation = mb_strtolower(trim((string)$row['abbreviation']));
            if ($name !== '') {
                $map[$name] = $uid;
            }
            if ($abbreviation !== '') {
                $map[$abbreviation] = $uid;
            }
        }

        return $map;
    }

    private function carExists(Connection $connection, int $pid, string $name, int $organisationUid): bool
    {
        $queryBuilder = $connection->createQueryBuilder();
        $count = (int)$queryBuilder
            ->count('uid')
            ->from('tx_rescuereports_domain_model_car')
            ->where(
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('name', $queryBuilder->createNamedParameter($name)),
                $queryBuilder->expr()->eq('organization', $queryBuilder->createNamedParameter($organisationUid, \PDO::PARAM_INT))
            )
            ->executeQuery()
            ->fetchOne();

        return $count > 0;
    }
}
