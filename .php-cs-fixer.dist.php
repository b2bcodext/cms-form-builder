<?php

// Finder path per layout (the skill adapts the ->in() line):
//   src layout  → ->in(__DIR__ . '/src')
//   root layout → ->in(__DIR__)->exclude('vendor')
$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->files()
    ->name('*.php');

return (new PhpCsFixer\Config())
    // single-shot phase runs — no cache file to pollute the project tree / git add -A commits
    ->setUsingCache(false)
    ->setRiskyAllowed(true)   // declare_strict_types / psr_autoloading / php_unit_* are risky fixers
    ->setRules([
        '@PSR1' => true,
        '@PSR2' => true,
        '@PSR12' => true,
        'psr_autoloading' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],   // sort `use` alphabetically
        'no_unused_imports' => true,                          // drop unused `use`
        'declare_strict_types' => true,   // MANDATORY for Oro 6.x and 7.x — never gate by version

        'php_unit_namespaced' => ['target' => '6.0'],
        'php_unit_expectation' => true,
    ])
    ->setFinder($finder);
