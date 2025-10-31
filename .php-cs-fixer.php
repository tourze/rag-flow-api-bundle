<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        '@PhpCsFixer' => true,
        '@Symfony' => true,
        'concat_space' => ['spacing' => 'one'],
        'phpdoc_summary' => false,
        'fully_qualified_strict_types' => [
            'import_symbols' => true,
        ],
        'class_attributes_separation' => [
            'elements' => [
                'property' => 'one',
                'method' => 'one',
                'trait_import' => 'none',
            ],
        ],
        'phpdoc_types_order' => [
            'null_adjustment' => 'always_last',
            'sort_algorithm' => 'none',
        ],
        'phpdoc_no_alias_tag' => false,
        'phpdoc_types' => false,
        'phpdoc_to_comment' => false,
        'phpdoc_var_without_name' => false,
        'no_superfluous_phpdoc_tags' => false,
        'phpdoc_separation' => false,
        'phpdoc_align' => false,
        'no_unused_imports' => false,
        'php_unit_data_provider_method_order' => false,
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setIndent('    ')
    ->setLineEnding("\n")
    ;