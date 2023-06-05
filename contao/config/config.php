<?php

/*
 * This file is part of Contao Theme Compiler Bundle.
 *
 * @package     contao-theme-compiler-bundle
 * @license     MIT
 * @author      Daniele Sciannimanica  <https://github.com/doishub>
 * @copyright   Oveleon                <https://www.oveleon.de/>
 */

// Maintenance
use Oveleon\ContaoThemeCompilerBundle\Compiler\ThemeCompiler;

$GLOBALS['TL_MAINTENANCE'][] = ThemeCompiler::class;

// Theme-Compiler sources
$GLOBALS['TC_SOURCES'] = [];
