<?php
namespace In2code\RescueReports\Domain\Repository;

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

        if ($event instanceof \In2code\RescueReports\Domain\Model\Event) {
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
}