<?php
declare(strict_types=1);

namespace Nkfire\RescueReports\Controller\Backend;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SnippetAjaxController
{
    public function listAction(ServerRequestInterface $request): ResponseInterface
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_rescuereports_domain_model_snippet');

        $rows = $queryBuilder
            ->select('uid', 'title', 'category', 'content')
            ->from('tx_rescuereports_domain_model_snippet')
            ->orderBy('sorting', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        $snippets = [];
        foreach ($rows as $row) {
            $snippets[] = [
                'id'       => 'snippet_' . (int)$row['uid'],
                'title'    => $row['title'],
                'category' => $row['category'],
                'html'     => $row['content'],
            ];
        }

        return new JsonResponse($snippets);
    }
}
