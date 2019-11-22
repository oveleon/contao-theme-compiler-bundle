<?php

// ToDO: Quick Link for Theme each theme to compile

// Add operation
$GLOBALS['TL_DCA']['tl_theme']['list']['operations']['themeCompiler'] = array
(
    'label'               => &$GLOBALS['TL_LANG']['tl_theme']['themeCompiler'],
    'href'                => 'table=tl_theme_compiler',
    'icon'                => 'bundles/contaothemecompiler/icons/wand.svg',
);

// Add fields
$GLOBALS['TL_DCA']['tl_theme']['fields']['skinSourceFiles'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_theme']['skinSourceFiles'],
    'exclude'                 => true,
    'inputType'               => 'fileTree',
    'eval'                    => array('multiple'=>true, 'fieldType'=>'checkbox', 'filesOnly'=>true, 'extensions'=>'css,scss,less'),
    'sql'                     => "blob NULL"
);

$GLOBALS['TL_DCA']['tl_theme']['fields']['combineSkinFiles'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_theme']['combineSkinFiles'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('fieldType'=>'radio'),
    'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_theme']['fields']['outputFilesTargetDir'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_theme']['outputFilesTargetDir'],
    'exclude'                 => true,
    'inputType'               => 'fileTree',
    'eval'                    => array('fieldType'=>'radio', 'mandatory'=>true),
    'sql'                     => "blob NULL"
);

// Extend the default palette
Contao\CoreBundle\DataContainer\PaletteManipulator::create()
    ->addLegend('compiler_legend', 'vars_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_BEFORE)
    ->addField(array('skinSourceFiles', 'outputFilesTargetDir', 'combineSkinFiles'), 'compiler_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_theme');
