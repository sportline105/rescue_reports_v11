<?php
namespace Nkfire\RescueReports\Domain\Repository;

use PDO;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

class EventRepository extends Repository
{
    protected $objectManager;

    public function injectObjectManager(ObjectManager $objectManager): void
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Holt ein einzelnes Event inkl. Relationen
     */
    public function findByUid($uid)
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching($query->equals('uid', (int)$uid));
        $query->setLimit(1);

        $event = $query->execute()->getFirst();

        if ($event instanceof \Nkfire\RescueReports\Domain\Model\Event) {
            $event->getStations();
            $event->getVehicles();
        }

        return $event;
    }

    /**
     * Liefert alle Events (optional: mit Relationen)
     */
    public function findAllWithRelations(): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->setOrderings($this->getDefaultOrderings());

        return $query->execute();
    }

    /**
     * Suche mit optionalen Filtern (Datum & Limit)
     */
    public function search(string $searchWord = '', $dateFrom = null, $dateTo = null, int $limit = 0): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);

        $constraints = [];

        if (trim($searchWord) !== '') {
            $constraints[] = $query->logicalOr([
                $query->like('title', '%' . $searchWord . '%'),
                $query->like('description', '%' . $searchWord . '%'),
                $query->like('location', '%' . $searchWord . '%'),
                $query->like('types.title', '%' . $searchWord . '%'),
                $query->like('number', '%' . $searchWord . '%'),
            ]);
        }

        $dateConstraints = $this->buildDateConstraints($query, $dateFrom, $dateTo);
        if (!empty($dateConstraints)) {
            $constraints = array_merge($constraints, $dateConstraints);
        }

        if (!empty($constraints)) {
            $query->matching($query->logicalAnd($constraints));
        }

        if ($limit > 0) {
            $query->setLimit($limit);
        }

        $query->setOrderings($this->getDefaultOrderings());

        return $query->execute();
    }

    /**
     * Suche gefiltert nach Station
     */
    public function searchByStation(
        int $stationUid,
        string $searchWord = '',
        $dateFrom = null,
        $dateTo = null,
        int $limit = 0
    ): QueryResultInterface {
        $uids = $this->findEventUidsByStation($stationUid, $dateFrom, $dateTo, $searchWord, $limit);

        return $this->findByUids($uids);
    }

    /**
     * Liefert Events gefiltert nach Datum & Limit
     */
    public function findFiltered($dateFrom = null, $dateTo = null, int $limit = 0): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);

        $constraints = $this->buildDateConstraints($query, $dateFrom, $dateTo);

        if (!empty($constraints)) {
            $query->matching($query->logicalAnd($constraints));
        }

        if ($limit > 0) {
            $query->setLimit($limit);
        }

        $query->setOrderings($this->getDefaultOrderings());

        return $query->execute();
    }

    /**
     * Liefert Events gefiltert nach Station, Datum & Limit
     */
    public function findFilteredByStation(int $stationUid, $dateFrom = null, $dateTo = null, int $limit = 0): QueryResultInterface
    {
        $uids = $this->findEventUidsByStation($stationUid, $dateFrom, $dateTo, '', $limit);

        return $this->findByUids($uids);
    }

    /**
     * Gemeinsame Sortierung
     */
    protected function getDefaultOrderings(): array
    {
        return [
            'start' => QueryInterface::ORDER_DESCENDING,
            'number' => QueryInterface::ORDER_DESCENDING,
            'uid' => QueryInterface::ORDER_DESCENDING,
        ];
    }

    /**
     * Baut Datums-Constraints für den Einsatzbeginn auf
     *
     * dateFrom => start ab 00:00:00 dieses Tages
     * dateTo   => start bis 23:59:59 dieses Tages
     */
    protected function buildDateConstraints($query, $dateFrom = null, $dateTo = null): array
    {
        $constraints = [];

        $fromDate = $this->convertToDateTime($dateFrom);
        if ($fromDate instanceof \DateTimeInterface) {
            $fromDate = (clone $fromDate)->setTime(0, 0, 0)->format('Y-m-d H:i:s');
            $constraints[] = $query->greaterThanOrEqual('start', $fromDate);
        }

        $toDate = $this->convertToDateTime($dateTo);
        if ($toDate instanceof \DateTimeInterface) {
            $toDate = (clone $toDate)->setTime(23, 59, 59)->format('Y-m-d H:i:s');
            $constraints[] = $query->lessThanOrEqual('start', $toDate);
        }

        return $constraints;
    }

    /**
     * Hilfsfunktion: String oder Timestamp -> DateTime
     */
    protected function convertToDateTime($value): ?\DateTime
    {
        if ($value instanceof \DateTime) {
            return clone $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return new \DateTime($value->format('Y-m-d H:i:s'));
        }

        if ($value === null || $value === '' || $value === '0') {
            return null;
        }

        if (is_numeric($value)) {
            return (new \DateTime())->setTimestamp((int)$value);
        }

        if (is_string($value) && strtotime($value) !== false) {
            return new \DateTime($value);
        }

        return null;
    }

    /**
     * Liefert Event-UIDs über die MM-Tabelle gefiltert nach Station.
     */
    protected function findEventUidsByStation(
        int $stationUid,
        $dateFrom = null,
        $dateTo = null,
        string $searchWord = '',
        int $limit = 0
    ): array {
        if ($stationUid <= 0) {
            return [];
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_rescuereports_domain_model_event');

        $queryBuilder->getRestrictions()->removeAll();

        $queryBuilder
            ->select('e.uid')
            ->from('tx_rescuereports_domain_model_event', 'e')
            ->innerJoin(
                'e',
                'tx_rescuereports_event_station_mm',
                'mm',
                $queryBuilder->expr()->eq(
                    'mm.uid_local',
                    $queryBuilder->quoteIdentifier('e.uid')
                )
            )
            ->where(
                $queryBuilder->expr()->eq(
                    'e.deleted',
                    $queryBuilder->createNamedParameter(0, PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'e.hidden',
                    $queryBuilder->createNamedParameter(0, PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'mm.uid_foreign',
                    $queryBuilder->createNamedParameter($stationUid, PDO::PARAM_INT)
                )
            );

        $fromDate = $this->convertToDateTime($dateFrom);
        if ($fromDate instanceof \DateTimeInterface) {
            $fromDate = (clone $fromDate)->setTime(0, 0, 0)->format('Y-m-d H:i:s');
            $queryBuilder->andWhere(
                $queryBuilder->expr()->gte(
                    'e.start',
                    $queryBuilder->createNamedParameter($fromDate)
                )
            );
        }

        $toDate = $this->convertToDateTime($dateTo);
        if ($toDate instanceof \DateTimeInterface) {
            $toDate = (clone $toDate)->setTime(23, 59, 59)->format('Y-m-d H:i:s');
            $queryBuilder->andWhere(
                $queryBuilder->expr()->lte(
                    'e.start',
                    $queryBuilder->createNamedParameter($toDate)
                )
            );
        }

        if (trim($searchWord) !== '') {
            $like = '%' . $queryBuilder->escapeLikeWildcards($searchWord) . '%';

            $queryBuilder->andWhere(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->like(
                        'e.title',
                        $queryBuilder->createNamedParameter($like)
                    ),
                    $queryBuilder->expr()->like(
                        'e.description',
                        $queryBuilder->createNamedParameter($like)
                    ),
                    $queryBuilder->expr()->like(
                        'e.location',
                        $queryBuilder->createNamedParameter($like)
                    ),
                    $queryBuilder->expr()->like(
                        'e.number',
                        $queryBuilder->createNamedParameter($like)
                    )
                )
            );
        }

        $queryBuilder
            ->groupBy('e.uid')
            ->orderBy('e.start', 'DESC')
            ->addOrderBy('e.number', 'DESC')
            ->addOrderBy('e.uid', 'DESC');

        if ($limit > 0) {
            $queryBuilder->setMaxResults($limit);
        }

        $uids = $queryBuilder->executeQuery()->fetchFirstColumn();

        return array_map('intval', $uids ?: []);
    }

    /**
     * Baut aus einer UID-Liste ein Extbase-QueryResult.
     */
    protected function findByUids(array $uids): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);

        if ($uids === []) {
            $query->matching($query->equals('uid', 0));
            return $query->execute();
        }

        $query->matching($query->in('uid', $uids));
        $query->setOrderings($this->getDefaultOrderings());

        return $query->execute();
    }

    /**
     * Zählt die Einsätze einer Station innerhalb eines Jahres bis zum aktuellen Einsatzzeitpunkt.
     * Bei gleichem Startzeitpunkt entscheidet die UID.
     */
    public function countByStationAndYearUntil(\DateTime $date, int $stationUid, int $currentEventUid = 0): int
    {
        if ($stationUid <= 0) {
            return 0;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_rescuereports_domain_model_event');

        $queryBuilder->getRestrictions()->removeAll();

        $yearStart = new \DateTime($date->format('Y-01-01 00:00:00'));

        $count = $queryBuilder
            ->count('e.uid')
            ->from('tx_rescuereports_domain_model_event', 'e')
            ->innerJoin(
                'e',
                'tx_rescuereports_event_station_mm',
                'mm',
                $queryBuilder->expr()->eq(
                    'mm.uid_local',
                    $queryBuilder->quoteIdentifier('e.uid')
                )
            )
            ->where(
                $queryBuilder->expr()->eq(
                    'e.deleted',
                    $queryBuilder->createNamedParameter(0, PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'e.hidden',
                    $queryBuilder->createNamedParameter(0, PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'mm.uid_foreign',
                    $queryBuilder->createNamedParameter($stationUid, PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->gte(
                    'e.start',
                    $queryBuilder->createNamedParameter($yearStart->format('Y-m-d H:i:s'))
                ),
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->lt(
                        'e.start',
                        $queryBuilder->createNamedParameter($date->format('Y-m-d H:i:s'))
                    ),
                    $queryBuilder->expr()->and(
                        $queryBuilder->expr()->eq(
                            'e.start',
                            $queryBuilder->createNamedParameter($date->format('Y-m-d H:i:s'))
                        ),
                        $queryBuilder->expr()->lte(
                            'e.uid',
                            $queryBuilder->createNamedParameter($currentEventUid, PDO::PARAM_INT)
                        )
                    )
                )
            )
            ->executeQuery()
            ->fetchOne();

        return (int)$count;
    }

    /**
     * Liefert Jahresstatistiken nach Kategorie.
     *
     * Optional: Filterung auf eine Ortsfeuerwehr (stationUid > 0).
     *
     * Rückgabe: [
     *   2025 => [
     *     'total' => 150,
     *     'categories' => [
     *       ['uid' => 1, 'title' => 'Brand', 'color' => '#e74c3c', 'count' => 45, 'percent' => 30.0],
     *       ...
     *     ],
     *   ],
     *   ...
     * ]
     */
    public function getYearlyStatistics(int $stationUid = 0): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_rescuereports_domain_model_event');

        $queryBuilder->getRestrictions()->removeAll();

        $queryBuilder
            ->select('cat.uid AS cat_uid', 'cat.title AS cat_title', 'cat.color AS cat_color')
            ->addSelectLiteral('YEAR(e.start) AS year', 'COUNT(DISTINCT e.uid) AS cnt')
            ->from('tx_rescuereports_domain_model_event', 'e')
            ->leftJoin('e', 'tx_rescuereports_event_type_mm', 'tmm', 'e.uid = tmm.uid_local')
            ->leftJoin('tmm', 'tx_rescuereports_domain_model_type', 't', 'tmm.uid_foreign = t.uid')
            ->leftJoin('t', 'tx_rescuereports_domain_model_category', 'cat', 't.category = cat.uid')
            ->where(
                $queryBuilder->expr()->eq('e.deleted', $queryBuilder->createNamedParameter(0, PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('e.hidden', $queryBuilder->createNamedParameter(0, PDO::PARAM_INT)),
                $queryBuilder->expr()->isNotNull('e.start')
            );

        if ($stationUid > 0) {
            $queryBuilder
                ->innerJoin('e', 'tx_rescuereports_event_station_mm', 'smm', 'e.uid = smm.uid_local')
                ->andWhere(
                    $queryBuilder->expr()->eq('smm.uid_foreign', $queryBuilder->createNamedParameter($stationUid, PDO::PARAM_INT))
                );
        }

        $rows = $queryBuilder
            ->groupBy('year', 'cat.uid')
            ->orderBy('year', 'DESC')
            ->addOrderBy('cat.title', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        // Aufbau: year -> categories[] + total
        $raw = [];
        foreach ($rows as $row) {
            $year = (int)$row['year'];
            if (!isset($raw[$year])) {
                $raw[$year] = [];
            }
            $raw[$year][] = [
                'uid'   => (int)$row['cat_uid'],
                'title' => (string)($row['cat_title'] ?: '– ohne Kategorie –'),
                'color' => (string)($row['cat_color'] ?: '#95a5a6'),
                'count' => (int)$row['cnt'],
            ];
        }

        // Gesamtzahl + Prozentwerte + conic-gradient berechnen
        $statistics = [];
        foreach ($raw as $year => $categories) {
            $total = array_sum(array_column($categories, 'count'));
            $cumulative = 0.0;
            $gradientParts = [];
            foreach ($categories as &$cat) {
                $cat['percent'] = $total > 0 ? round($cat['count'] / $total * 100, 1) : 0.0;
                $startDeg = round($cumulative, 2);
                $cumulative += $total > 0 ? ($cat['count'] / $total * 360) : 0;
                $endDeg = round($cumulative, 2);
                $gradientParts[] = $cat['color'] . ' ' . $startDeg . 'deg ' . $endDeg . 'deg';
            }
            unset($cat);
            $statistics[$year] = [
                'total'      => $total,
                'categories' => $categories,
                'gradient'   => 'conic-gradient(' . implode(', ', $gradientParts) . ')',
            ];
        }

        return $statistics;
    }
}