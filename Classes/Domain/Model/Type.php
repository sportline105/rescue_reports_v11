<?php

namespace In2code\RescueReports\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class Type extends AbstractEntity {
    protected string $title = '';
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): void { $this->title = $title; }
}