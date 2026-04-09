<?php
declare(strict_types=1);

return [
    'rescue_reports_snippets' => [
        'path'   => '/rescue-reports/snippets',
        'target' => \Nkfire\RescueReports\Controller\Backend\SnippetAjaxController::class . '::listAction',
    ],
];
