<?php
declare(strict_types=1);

return [
    'ctrl' => [
        'title'      => 'LLL:EXT:rescue_reports/Resources/Private/Language/locallang_db.xlf:tx_rescuereports_domain_model_snippet',
        'label'      => 'title',
        'sortby'     => 'sorting',
        'delete'     => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'tstamp'     => 'tstamp',
        'crdate'     => 'crdate',
        'cruser_id'  => 'cruser_id',
        'iconfile'   => 'EXT:rescue_reports/Resources/Public/Icons/_tx_rescuereports_domain_model_event.png',
    ],
    'columns' => [
        'hidden' => [
            'label'  => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.visible',
            'config' => [
                'type'       => 'check',
                'renderType' => 'checkboxToggle',
                'items'      => [
                    [0 => '', 1 => '', 'invertStateDisplay' => true],
                ],
                'default' => 0,
            ],
        ],
        'title' => [
            'label'  => 'LLL:EXT:rescue_reports/Resources/Private/Language/locallang_db.xlf:tx_rescuereports_domain_model_snippet.title',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim,required',
                'max'  => 255,
            ],
        ],
        'category' => [
            'label'  => 'LLL:EXT:rescue_reports/Resources/Private/Language/locallang_db.xlf:tx_rescuereports_domain_model_snippet.category',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'max'  => 100,
            ],
        ],
        'content' => [
            'label'  => 'LLL:EXT:rescue_reports/Resources/Private/Language/locallang_db.xlf:tx_rescuereports_domain_model_snippet.content',
            'config' => [
                'type' => 'text',
                'rows' => 8,
                'cols' => 80,
                'eval' => 'trim',
            ],
        ],
    ],
    'types' => [
        '1' => ['showitem' => 'hidden, title, category, content'],
    ],
];
