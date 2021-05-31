<?php
return [
    'view_manager' => [
        'template_path_stack' => [
            OMEKA_PATH . '/modules/Sitemaps/view'
        ]
    ],
    'translator' => [
            'translation_file_patterns' => [
                    [
                        'type' => 'gettext',
                        'base_dir' => OMEKA_PATH . '/modules/Sitemaps/language',
                        'pattern' => '%s.mo',
                        'text_domain' => null,
                    ],
            ],
    ],
    'controllers' => [
        'factories' => [
            'Sitemaps\Controller\Site\SitemapsController' => Sitemaps\Service\Controller\Site\SitemapsControllerFactory::class,
        ]
    ],
    'router' => [
        'routes' => [
            'site' => [
                'child_routes' => [
                    'sitemap' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/sitemap.xml',
                            'defaults' => [
                                '__NAMESPACE__' => 'Sitemaps\Controller\Site',
                                '__SITE__' => true,
                                'controller' => Sitemaps\Controller\Site\SitemapsController::class,
                                'action' => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                    ],
                ],
            ],
        ],
    ],
];

