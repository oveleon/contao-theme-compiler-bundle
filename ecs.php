<?php

declare(strict_types=1);

use Contao\EasyCodingStandard\Fixer\CommentLengthFixer;
use Contao\EasyCodingStandard\Set\SetList;
use PhpCsFixer\Fixer\Basic\BracesPositionFixer;
use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use PhpCsFixer\Fixer\ControlStructure\ControlStructureContinuationPositionFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Whitespace\MethodChainingIndentationFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Option;

return ECSConfig::configure()
    ->withSets([SetList::CONTAO])
    ->withPaths([
        __DIR__.'/src',
    ])
    ->withRules([
        NoUnusedImportsFixer::class,
    ])
    ->withConfiguredRule(BracesPositionFixer::class, [
        'control_structures_opening_brace' => 'next_line_unless_newline_at_signature_end',
        'functions_opening_brace' => 'next_line_unless_newline_at_signature_end',
        'anonymous_functions_opening_brace' => 'next_line_unless_newline_at_signature_end',
        'classes_opening_brace' => 'next_line_unless_newline_at_signature_end',
        'anonymous_classes_opening_brace' => 'next_line_unless_newline_at_signature_end',
        'allow_single_line_empty_anonymous_classes' => false,
        'allow_single_line_anonymous_functions' => false,
    ])
    ->withConfiguredRule(ControlStructureContinuationPositionFixer::class, [
        'position' => 'next_line',
    ])
    ->withConfiguredRule(HeaderCommentFixer::class, [
        'header' => "This file is part of Contao Theme Compiler Bundle.\n\n@package     contao-theme-compiler-bundle\n@license     MIT\n@author      Daniele Sciannimanica  <https://github.com/doishub>\n@copyright   Oveleon                <https://www.oveleon.de/>",
        'comment_type' => 'comment',
        'location' => 'after_declare_strict',
    ])
    ->withSkip([
        '*/languages/*',
        CommentLengthFixer::class,
        MethodChainingIndentationFixer::class,
    ])
    ->withParallel()
    ->withSpacing(Option::INDENTATION_SPACES, "\n")
;
