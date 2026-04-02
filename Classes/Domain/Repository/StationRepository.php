<?php
namespace In2code\RescueReports\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

class StationRepository extends Repository
{
    public function findAllGroupedByBrigade(): array
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(true);

        $query->setOrderings([
            'brigade.priority' => QueryInterface::ORDER_ASCENDING,
            'brigade.name' => QueryInterface::ORDER_ASCENDING,
            'name' => QueryInterface::ORDER_ASCENDING
        ]);

        return $query->execute()->toArray();
    }
}