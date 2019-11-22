<?php
// Maintenance
$GLOBALS['TL_MAINTENANCE'][] = '\\Oveleon\\ContaoThemeCompilerBundle\\ThemeCompiler';

// Theme-Compiler sources
$GLOBALS['TC_SOURCES'] = array();

// Add backend stylesheet
if (TL_MODE == 'BE')
{
    $GLOBALS['TL_CSS'][] = 'bundles/contaothemecompiler/backend.css|static';
}
