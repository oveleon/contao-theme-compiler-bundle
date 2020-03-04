<?php
// Add operation
$GLOBALS['TL_DCA']['tl_theme']['list']['operations']['compileConfig'] = array
(
    'label'               => &$GLOBALS['TL_LANG']['tl_theme']['compileConfig'],
    'href'                => 'act=compile&do=maintenance',
    'icon'                => 'bundles/contaothemecompiler/icons/compile.svg',
    'button_callback'     => array('tl_theme_compiler', 'compileThemeStyles')
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

$GLOBALS['TL_DCA']['tl_theme']['fields']['backupFiles'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_theme']['backupFiles'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'sql'                     => "char(1) NOT NULL default ''"
);

// Extend the default palette
Contao\CoreBundle\DataContainer\PaletteManipulator::create()
    ->addLegend('compiler_legend', 'vars_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_BEFORE)
    ->addField(array('skinSourceFiles', 'outputFilesTargetDir', 'combineSkinFiles', 'backupFiles'), 'compiler_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_theme');

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Daniele Sciannimanica <daniele@oveleon.de>
 */
class tl_theme_compiler extends \Backend
{
    /**
     * Import the back end user object
     */
    public function __construct()
    {
        parent::__construct();
        $this->import('BackendUser', 'User');
    }

    /**
     * Return the "import theme" link
     *
     * @param string $row
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $icon
     * @param string $attributes
     *
     * @return string
     */
    public function compileThemeStyles($row, $href, $label, $title, $icon, $attributes)
    {
        return '<a href="' . $this->addToUrl($href)  . '&amp;theme=' . $row['id'] . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ';
    }
}
