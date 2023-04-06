<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude([])
    ->in([__DIR__ . '/app', __DIR__ . '/tests']);

$config = new PhpCsFixer\Config();

return $config->setRules([
        /* List should be kept alphabetical */
        '@Symfony'               => TRUE,
        '@PSR12'                 => FALSE,
        'array_syntax'           => ['syntax' => 'short'],
        'binary_operator_spaces' => [
                'operators' => ['=>' => 'align_single_space_minimal'],
        ],
        'braces'                            => TRUE,
        'concat_space'                      => ['spacing' => 'one'],
        'constant_case'                     => ['case' => 'upper'],
        'global_namespace_import'           => TRUE,
        'heredoc_indentation'               => ['indentation' => 'start_plus_one'],
        'lowercase_keywords'                => TRUE,
        'method_argument_space'             => TRUE,
        'method_chaining_indentation'       => TRUE,
        'no_unused_imports'                 => FALSE,
        'no_useless_else'                   => TRUE,
        'no_useless_return'                 => TRUE,
        'not_operator_with_successor_space' => TRUE,
        'php_unit_method_casing'            => ['case' => 'snake_case'],
        'phpdoc_align'                      => ['align' => 'vertical'],
        'phpdoc_indent'                     => TRUE,
        'phpdoc_separation'                 => FALSE,
        'phpdoc_summary'                    => FALSE,
        'single_import_per_statement'       => ['group_to_single_imports' => FALSE],
        'single_quote'                      => FALSE,
        'trailing_comma_in_multiline'       => FALSE,
        'yoda_style'                        => FALSE
    ])
    ->setRiskyAllowed(FALSE)
    ->setFinder($finder);
