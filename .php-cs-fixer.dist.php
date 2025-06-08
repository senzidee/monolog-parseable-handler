<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PHP84Migration' => true,
        '@PSR12' => true,
    ])
    ->setFinder($finder)
    ;
