<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:rescue_reports/Resources/Private/Language/locallang_db.xlf:tx_rescuereports_domain_model_deployment',
        'label' => 'brigade',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'iconfile' => 'EXT:rescue_reports/Resources/Public/Icons/tx_rescuereports_domain_model_deployment.svg',
        'hideTable' => true, // ✅ das verhindert Anzeige im Seitenmodul
    ],
    'types' => [
        '1' => ['showitem' => 'event, brigade, stations, --div--;Access, hidden, starttime, endtime'],
    ],
    'columns' => [
        'sys_language_uid' => ['exclude' => true, 'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language', 'config' => ['type' => 'language']],
        'l18n_parent' => ['displayCond' => 'FIELD:sys_language_uid:>:0', 'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent', 'config' => ['type' => 'select', 'renderType' => 'selectSingle', 'items' => [['', 0]], 'foreign_table' => 'tx_rescuereports_domain_model_deployment', 'foreign_table_where' => 'AND {#tx_rescuereports_domain_model_deployment}.{#pid}=###CURRENT_PID### AND {#tx_rescuereports_domain_model_deployment}.{#sys_language_uid} IN (-1,0)', 'default' => 0]],
        'l18n_diffsource' => ['config' => ['type' => 'passthrough']],
        'hidden' => ['label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden', 'config' => ['type' => 'check', 'items' => [['', 1]]]],
        'starttime' => ['exclude' => true, 'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime', 'config' => ['type' => 'input', 'renderType' => 'inputDateTime', 'eval' => 'datetime', 'default' => 0]],
        'endtime' => ['exclude' => true, 'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime', 'config' => ['type' => 'input', 'renderType' => 'inputDateTime', 'eval' => 'datetime', 'default' => 0, 'range' => ['upper' => mktime(0, 0, 0, 1, 1, 2038)]]],
        'event' => [
            'label' => 'Einsatz',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_rescuereports_domain_model_event',
                'minitems' => 0,
                'maxitems' => 1,
            ]
        ],
        'brigade' => [
            'label' => 'Stadtfeuerwehr',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_rescuereports_domain_model_brigade',
                'minitems' => 0,
                'maxitems' => 1,
            ]
        ],
        'stations' => [
            'label' => 'Ortsfeuerwehren im Einsatz',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_rescuereports_domain_model_station',
                'MM' => 'tx_rescuereports_deployment_station_mm', // MM-Tabelle angeben
                'size' => 10,
                'minitems' => 0,
                'maxitems' => 9999,
                ]
            ],

    ]
];
