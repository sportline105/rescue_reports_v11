<?php

namespace Nkfire\RescueReports\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Repository;

class CategoryRepository extends Repository
{
    protected $defaultOrderings = [
        'title' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING,
    ];
}
