<?php

declare(strict_types=1);

use Palmtree\PhpCsFixerConfig\Config;

$config = new Config();

$config
    ->setRules(array_merge($config->getRules(), [
        'declare_strict_types'        => false,
        'void_return'                 => false,
        'use_arrow_functions'         => false,
        'trailing_comma_in_multiline' => [
            'after_heredoc' => false,
            'elements'      => ['arrays'],
        ],
        'binary_operator_spaces'      => [
            'operators' => [
                '=>' => 'align',
                '='  => 'align',
                // Prevent spaces being added in PHP8 union types
                '|'  => null,
            ],
        ],
        'visibility_required' => [
            'elements' => ['property', 'method'],
        ],
    ]))
    ->getFinder()
    ->in(__DIR__ . '/src')
    ->append([__FILE__])
;

return $config;
