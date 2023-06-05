<?php

declare(strict_types=1);

/*
 * This file is part of Contao Theme Compiler Bundle.
 *
 * @package     contao-theme-compiler-bundle
 * @license     MIT
 * @author      Daniele Sciannimanica  <https://github.com/doishub>
 * @copyright   Oveleon                <https://www.oveleon.de/>
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Oveleon\ContaoThemeCompilerBundle\Utils\CompilerUtils;

$GLOBALS['TL_DCA']['tl_theme']['edit']['buttons_callback'][]    = [CompilerUtils::class, 'addSaveNCompileButton'];
$GLOBALS['TL_DCA']['tl_theme']['config']['onsubmit_callback'][] = [CompilerUtils::class, 'redirectMaintenanceAndCompile'];

// Add operation
$GLOBALS['TL_DCA']['tl_theme']['list']['operations']['compileConfig'] = [
    'label'           => &$GLOBALS['TL_LANG']['tl_theme']['compileConfig'],
    'href'            => 'act=compile&do=maintenance',
    'icon'            => 'bundles/contaothemecompiler/icons/compile.svg',
    'button_callback' => [CompilerUtils::class, 'addCompileThemeButton']
];

// Add fields
$GLOBALS['TL_DCA']['tl_theme']['fields']['skinSourceFiles'] = [
    'label'           => &$GLOBALS['TL_LANG']['tl_theme']['skinSourceFiles'],
    'exclude'         => true,
    'inputType'       => 'fileTree',
    'eval'            => ['multiple'=>true, 'fieldType'=>'checkbox', 'filesOnly'=>true, 'extensions'=>'css,scss,less'],
    'sql'             => "blob NULL"
];

$GLOBALS['TL_DCA']['tl_theme']['fields']['combineSkinFiles'] = [
    'label'           => &$GLOBALS['TL_LANG']['tl_theme']['combineSkinFiles'],
    'exclude'         => true,
    'inputType'       => 'checkbox',
    'sql'             => "char(1) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_theme']['fields']['outputFilesTargetDir'] = [
    'label'           => &$GLOBALS['TL_LANG']['tl_theme']['outputFilesTargetDir'],
    'exclude'         => true,
    'inputType'       => 'fileTree',
    'eval'            => ['fieldType'=>'radio', 'mandatory'=>true],
    'sql'             => "blob NULL"
];

$GLOBALS['TL_DCA']['tl_theme']['fields']['backupFiles'] = [
    'label'           => &$GLOBALS['TL_LANG']['tl_theme']['backupFiles'],
    'exclude'         => true,
    'inputType'       => 'checkbox',
    'sql'             => "char(1) NOT NULL default ''"
];

// Extend the default palette
PaletteManipulator::create()
    ->addLegend('compiler_legend', 'vars_legend', PaletteManipulator::POSITION_BEFORE)
    ->addField(['skinSourceFiles', 'outputFilesTargetDir', 'combineSkinFiles', 'backupFiles'], 'compiler_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_theme');
