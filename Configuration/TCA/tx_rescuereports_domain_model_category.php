<?php

return [
    'ctrl' => [
        'title'                    => 'Einsatz-Kategorie',
        'label'                    => 'title',
        'tstamp'                   => 'tstamp',
        'crdate'                   => 'crdate',
        'cruser_id'                => 'cruser_id',
        'delete'                   => 'deleted',
        'enablecolumns'            => ['disabled' => 'hidden'],
        'searchFields'             => 'title',
        'iconfile'                 => 'EXT:rescue_reports/Resources/Public/Icons/tx_rescuereports_domain_model_type.svg',
        'sortby'                   => 'sorting',
    ],
    'types' => [
        '1' => ['showitem' => 'title, color, --div--;Access, hidden'],
    ],
    'columns' => [
        'hidden' => [
            'label'  => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => ['type' => 'check', 'items' => [['', 1]]],
        ],
        'title' => [
            'label'  => 'Bezeichnung',
            'config' => ['type' => 'input', 'eval' => 'trim,required'],
        ],
        'color' => [
            'label' => 'Farbe (Tortendiagramm)',
            'config' => [
                'type'       => 'input',
                'renderType' => 'colorpicker',
                'default'    => '#3498db',
                'size'       => 10,
            ],
        ],
    ],
];
