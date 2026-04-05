<?php
declare(strict_types=1);

namespace In2code\RescueReports\Hooks;

use PDO;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DataHandlerHook
{
    public function processDatamap_preProcessFieldArray(array &$incomingFieldArray, $table, $id, $parentObject): void
    {
        if ($table !== 'tx_rescuereports_domain_model_event') {
            return;
        }

        $title = trim((string)($incomingFieldArray['title'] ?? ''));
        if ($title === '') {
            $title = 'einsatz';
        }

        $start = (string)($incomingFieldArray['start'] ?? '');
        $typeTitle = $this->resolveTypeTitle($incomingFieldArray, $id);

        $titleSlug = $this->slugify($title);
        $typeSlug = $this->slugify($typeTitle);

        $slugParts = [];

        if ($start !== '') {
            $timestamp = strtotime($start);
            if ($timestamp !== false) {
                $slugParts[] = date('m', $timestamp);
                $slugParts[] = date('d', $timestamp);
            }
        }

        $lastPart = '';
        if ($typeSlug !== '') {
            $lastPart .= $typeSlug;
        }
        if ($titleSlug !== '') {
            $lastPart .= ($lastPart !== '' ? '/' : '') . $titleSlug;
        }

        if ($lastPart === '') {
            $lastPart = 'einsatz';
        }

        $slugParts[] = $lastPart;

        $incomingFieldArray['slug_source'] = implode('/', $slugParts);

        // nur wenn du automatische Neugenerierung willst
        $incomingFieldArray['slug'] = '';
    }

    protected function resolveTypeTitle(array $incomingFieldArray, $id): string
    {
        $typeUid = 0;

        // Wenn types direkt im aktuellen Speichervorgang mitkommt
        if (!empty($incomingFieldArray['types'])) {
            $typeUid = (int)$incomingFieldArray['types'];
        }

        // Falls nichts mitkommt: aus bestehender MM-Zuordnung lesen
        if ($typeUid <= 0 && (int)$id > 0) {
            $typeUid = $this->getTypeUidFromMm((int)$id);
        }

        if ($typeUid <= 0) {
            return '';
        }

        return $this->getTypeTitleByUid($typeUid);
    }

    protected function getTypeUidFromMm(int $eventUid): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_rescuereports_event_type_mm');

        $row = $queryBuilder
            ->select('uid_foreign')
            ->from('tx_rescuereports_event_type_mm')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($eventUid, PDO::PARAM_INT)
                )
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        return (int)($row['uid_foreign'] ?? 0);
    }

    protected function getTypeTitleByUid(int $typeUid): string
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_rescuereports_domain_model_type');

        $queryBuilder->getRestrictions()->removeAll();

        $row = $queryBuilder
            ->select('title')
            ->from('tx_rescuereports_domain_model_type')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($typeUid, PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'deleted',
                    $queryBuilder->createNamedParameter(0, PDO::PARAM_INT)
                )
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        return trim((string)($row['title'] ?? ''));
    }

    protected function slugify(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^[:alnum:]]+/u', '-', $text);
        $text = trim((string)$text, '-');

        return $text !== '' ? $text : '';
    }
}