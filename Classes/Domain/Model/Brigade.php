<?php

namespace In2code\RescueReports\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Annotation\ORM\Transient;

class Brigade extends AbstractEntity
{
    protected string $name = '';
    protected int $priority = 0;

    /**
     * @var ObjectStorage<\In2code\RescueReports\Domain\Model\Station>
     * @Transient
     */
    protected ObjectStorage $stations;

    public function __construct()
    {
        $this->stations = new ObjectStorage();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    public function getStations(): ObjectStorage
    {
        return $this->stations;
    }

    public function setStations(ObjectStorage $stations): void
    {
        $this->stations = $stations;
    }
}
