<?php //-->
return [
    'disable' => '1',
    'singular' => 'Comment',
    'plural' => 'Comments',
    'name' => 'comment',
    'icon' => 'fas fa-lock',
    'detail' => 'comment Package',
    'fields' => [
        [
            'disable' => '1',
            'label' => 'Title',
            'name' => 'title',
            'field' => [
                'type' => 'text',
                'attributes' => [
                    'placeholder' => 'Comment Title',
                ]
            ],
            'type' => 'title',
            'validation' => [
                [
                    'method' => 'unique',
                    'message' => 'Title is already taken'
                ]
            ],
            'list' => [
                'format' => 'none',
            ],
            'detail' => [
                'format' => 'none',
            ],
            'default' => '',
            'searchable' => '1',
            'filterable' => '1'
        ],
        [
            'disable' => '1',
            'label' => 'Author',
            'name' => 'author',
            'field' => [
                'type' => 'author',
                'attributes' => [
                    'placeholder' => 'Comment Author',
                ]
            ],
            'sql' => [
                'type' => 'varchar',
                'length' => 255,
                'index' => true
            ],
            'validation' => [
                [
                    'method' => 'required',
                    'message' => 'Author is Required'
                ]
            ],
            'list' => [
                'format' => 'hide',
            ],
            'detail' => [
                'format' => 'hide',
            ],
            'default' => '',
            'filterable' => '1',
            'sortable' => '1'
        ],
        [
            'disable' => '1',
            'label' => 'Type',
            'name' => 'type',
            'field' => [
                'type' => 'text',
            ],
            'list' => [
                'format' => 'hide',
            ],
            'detail' => [
                'format' => 'hide',
            ],
            'default' => '',
            'filterable' => '1',
            'sortable' => '1'
        ],
        [
            'disable' => '1',
            'label' => 'Active',
            'name' => 'active',
            'field' => [
                'type' => 'active',
            ],
            'list' => [
                'format' => 'hide',
            ],
            'detail' => [
                'format' => 'hide',
            ],
            'default' => '1',
            'filterable' => '1',
            'sortable' => '1'
        ],
        [
            'disable' => '1',
            'label' => 'Created',
            'name' => 'created',
            'field' => [
                'type' => 'created',
            ],
            'list' => [
                'format' => 'none',
            ],
            'detail' => [
                'format' => 'none',
            ],
            'default' => 'NOW()',
            'sortable' => '1'
        ],
        [
            'disable' => '1',
            'label' => 'Updated',
            'name' => 'updated',
            'field' => [
                'type' => 'updated',
            ],
            'list' => [
                'format' => 'none',
            ],
            'detail' => [
                'format' => 'none',
            ],
            'default' => 'NOW()',
            'sortable' => '1'
        ]
    ],
    'fixtures' => [
        [
            'comment_title'     => 'Some comment',
            'comment_author' => 'Charles Zamora',
            'comment_type'     => 'public',
            'comment_created'  => '2018-02-03 01:45:16',
            'comment_updated'  => '2018-02-03 01:45:16'
        ]
    ],
];
