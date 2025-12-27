<?php

return [
    'column_toggle' => [
        'heading' => 'Kolumny',
    ],

    'columns' => [
        'text' => [
            'actions' => [
                'collapse_list' => 'Pokaż :count mniej',
                'expand_list' => 'Pokaż :count więcej',
            ],
            'more_list_items' => 'i :count więcej',
        ],
    ],

    'fields' => [
        'bulk_select_page' => [
            'label' => 'Zaznacz/odznacz wszystkie dla akcji zbiorczych',
        ],
        'bulk_select_record' => [
            'label' => 'Zaznacz/odznacz rekord :key dla akcji zbiorczych',
        ],
        'bulk_select_group' => [
            'label' => 'Zaznacz/odznacz grupę :title dla akcji zbiorczych',
        ],
        'search' => [
            'label' => 'Szukaj',
            'placeholder' => 'Szukaj...',
            'indicator' => 'Szukaj',
        ],
    ],

    'summary' => [
        'heading' => 'Podsumowanie',
        'subheadings' => [
            'all' => 'Wszystkie :label',
            'group' => 'Podsumowanie :group',
            'page' => 'Ta strona',
        ],
        'summarizers' => [
            'average' => [
                'label' => 'Średnia',
            ],
            'count' => [
                'label' => 'Liczba',
            ],
            'sum' => [
                'label' => 'Suma',
            ],
        ],
    ],

    'actions' => [
        'disable_reordering' => [
            'label' => 'Zakończ sortowanie',
        ],
        'enable_reordering' => [
            'label' => 'Sortuj rekordy',
        ],
        'filter' => [
            'label' => 'Filtruj',
        ],
        'group' => [
            'label' => 'Grupuj',
        ],
        'open_bulk_actions' => [
            'label' => 'Otwórz akcje zbiorcze',
        ],
        'toggle_columns' => [
            'label' => 'Przełącz kolumny',
        ],
    ],

    'empty' => [
        'heading' => 'Brak rekordów',
        'description' => 'Utwórz nowy, aby rozpocząć.',
    ],

    'filters' => [
        'actions' => [
            'apply' => [
                'label' => 'Zastosuj filtry',
            ],
            'remove' => [
                'label' => 'Usuń filtr',
            ],
            'remove_all' => [
                'label' => 'Usuń wszystkie filtry',
                'tooltip' => 'Usuń wszystkie filtry',
            ],
            'reset' => [
                'label' => 'Resetuj',
            ],
        ],
        'heading' => 'Filtry',
        'indicator' => 'Aktywne filtry',
        'multi_select' => [
            'placeholder' => 'Wszystkie',
        ],
        'select' => [
            'placeholder' => 'Wszystkie',
        ],
        'trinary' => [
            'placeholder' => 'Wszystkie',
            'true' => 'Tak',
            'false' => 'Nie',
        ],
    ],

    'grouping' => [
        'fields' => [
            'group' => [
                'label' => 'Grupuj według',
                'placeholder' => 'Grupuj według',
            ],
            'direction' => [
                'label' => 'Kierunek grupowania',
                'options' => [
                    'asc' => 'Rosnąco',
                    'desc' => 'Malejąco',
                ],
            ],
        ],
    ],

    'reorder_indicator' => 'Przeciągnij i upuść rekordy w odpowiedniej kolejności.',

    'selection_indicator' => [
        'selected_count' => ':count zaznaczono',
        'actions' => [
            'select_all' => [
                'label' => 'Zaznacz wszystkie :count',
            ],
            'deselect_all' => [
                'label' => 'Odznacz wszystkie',
            ],
        ],
    ],

    'sorting' => [
        'fields' => [
            'column' => [
                'label' => 'Sortuj według',
            ],
            'direction' => [
                'label' => 'Kierunek sortowania',
                'options' => [
                    'asc' => 'Rosnąco',
                    'desc' => 'Malejąco',
                ],
            ],
        ],
    ],
];
