<?php

return [
    'sidebar-menu' => [
        // Header
        [
            'class' => 'header',
            'text'  => 'MAIN NAVIGATION',
        ],

        // Home
        [
            'link'  => [
                'href'  => URL_MODFIX,
                'icon'  => 'fa fa-dashboard',
                'text'  => 'Home',
                'wrap'  => 'span',
            ],
        ],

        // Controllers
        [
            'class'   => 'treeview',
            'submenu' => [
                'link'  => [
                    'href'  => URL_MODFIX . 'controllers',
                    'icon'  => 'fa fa-cubes',
                    'text'  => 'Controllers',
                    'wrap'  => 'span',
                    'caret' => 'fa fa-angle-left pull-right',
                ],
                'class' => 'treeview-menu',
                'items' => [
                    [
                        'link'  => [
                            'href' => URL_MODFIX . 'controllers/generate',
                            'text' => 'Generate',
                            'wrap' => 'span',
                            'icon' => 'fa fa-plus',
                        ],
                    ],
                    [
                        'link'  => [
                            'href' => URL_MODFIX . 'controllers/list',
                            'text' => 'List all',
                            'wrap' => 'span',
                            'icon' => 'fa fa-reorder',
                        ],
                    ],
                ],
            ],
        ],

        // Models
        [
            'class'   => 'treeview',
            'submenu' => [
                'link'  => [
                    'href'  => URL_MODFIX . 'models',
                    'icon'  => 'fa fa-database',
                    'text'  => 'Models',
                    'wrap'  => 'span',
                    'caret' => 'fa fa-angle-left pull-right',
                ],
                'class' => 'treeview-menu',
                'items' => [
                    [
                        'link'  => [
                            'href' => URL_MODFIX . 'models/generate',
                            'text' => 'Generate',
                            'wrap' => 'span',
                            'icon' => 'fa fa-plus',
                        ],
                    ],
                    [
                        'link'  => [
                            'href' => URL_MODFIX . 'models/list',
                            'text' => 'List all',
                            'wrap' => 'span',
                            'icon' => 'fa fa-reorder',
                        ],
                    ],
                ],
            ],
        ],

        // Scaffold
        [
            'class'   => 'treeview',
            'submenu' => [
                'link'  => [
                    'href'  => URL_MODFIX . 'scaffold',
                    'icon'  => 'fa fa-file-code-o',
                    'text'  => 'Scaffold',
                    'wrap'  => 'span',
                    'caret' => 'fa fa-angle-left pull-right',
                ],
                'class' => 'treeview-menu',
                'items' => [
                    [
                        'link'  => [
                            'href' => URL_MODFIX . 'scaffold/generate',
                            'text' => 'Generate',
                            'wrap' => 'span',
                            'icon' => 'fa fa-plus',
                        ],
                    ],
                ],
            ],
        ],

        // Migrations
        [
            'class'   => 'treeview',
            'submenu' => [
                'link'  => [
                    'href'  => URL_MODFIX . 'migrations',
                    'icon'  => 'fa fa-magic',
                    'text'  => 'Migrations',
                    'wrap'  => 'span',
                    'caret' => 'fa fa-angle-left pull-right',
                ],
                'class' => 'treeview-menu',
                'items' => [
                    [
                        'link'  => [
                            'href' => URL_MODFIX . 'migrations/generate',
                            'text' => 'Generate',
                            'wrap' => 'span',
                            'icon' => 'fa fa-plus',
                        ],
                    ],
                    [
                        'link'  => [
                            'href' => URL_MODFIX . 'migrations/list',
                            'text' => 'List all',
                            'wrap' => 'span',
                            'icon' => 'fa fa-reorder',
                        ],
                    ],
                    [
                        'link'  => [
                            'href' => URL_MODFIX . 'migrations/run',
                            'text' => 'Run',
                            'wrap' => 'span',
                            'icon' => 'fa fa-play',
                        ],
                    ],
                ],
            ],
        ],

        // System Info
        [
            'link'  => [
                'href'  => URL_MODFIX . 'info',
                'icon'  => 'fa fa-info',
                'text'  => 'System Info',
                'wrap'  => 'span',
            ],
        ],

        [
            'class' => 'header',
            'text'  => 'LINKS',
        ],

        // DevTools
        [
            'link'  => [
                'href'  => 'https://github.com/phalcon/phalcon-devtools',
                'icon'  => 'fa fa-book',
                'local' => false,
                'target' => '_blank',
                'text'  => 'Phalcon DevTools',
                'wrap'  => 'span',
            ],
        ],

        // Incubator
        [
            'link'  => [
                'href'  => 'https://github.com/phalcon/incubator',
                'icon'  => 'fa fa-book',
                'local' => false,
                'target' => '_blank',
                'text'  => 'Phalcon Incubator',
                'wrap'  => 'span',
            ],
        ],

        // Phalcon Docs
        [
            'link'  => [
                'href'  => 'https://docs.phalconphp.com/',
                'icon'  => 'fa fa-book',
                'local' => false,
                'target' => '_blank',
                'text'  => 'Phalcon Docs',
                'wrap'  => 'span',
            ],
        ],

        // Zephir
        [
            'link'  => [
                'href'  => 'https://zephir-lang.com/',
                'icon'  => 'fa fa-book',
                'local' => false,
                'target' => '_blank',
                'text'  => 'Zephir',
                'wrap'  => 'span',
            ],
        ],

        // Awesome Phalcon
        [
            'link'  => [
                'href'  => 'https://github.com/phalcon/awesome-phalcon',
                'icon'  => 'fa fa-book',
                'local' => false,
                'target' => '_blank',
                'text'  => 'Awesome Phalcon',
                'wrap'  => 'span',
            ],
        ],
    ],
];
