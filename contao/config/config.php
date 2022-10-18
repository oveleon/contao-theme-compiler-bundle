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
$GLOBALS['TL_MAINTENANCE'][] = '\\Oveleon\\ContaoThemeCompilerBundle\\ThemeCompiler';

// Theme-Compiler sources
$GLOBALS['TC_SOURCES'] = [];

// Add backend stylesheet
$request = System::getContainer()->get('request_stack')->getCurrentRequest();

if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
{
    $GLOBALS['TL_CSS'][] = 'bundles/contaothemecompiler/backend.css|static';
}
