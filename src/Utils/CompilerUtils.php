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

namespace Oveleon\ContaoThemeCompilerBundle\Utils;

use Contao\Backend;
use Contao\DataContainer;
use Contao\Image;
use Contao\StringUtil;
use Contao\System;
use Contao\ThemeModel;

class CompilerUtils extends Backend
{
    public function addCompileThemeButton($row, string $href, string $label, string $title, string $icon, string $attributes): string
    {
        return '<a href="'.$this->addToUrl($href).'&amp;theme='.$row['id'].'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
    }

    /**
     * Add save and compile button.
     */
    public function addSaveNCompileButton(array $arrButtons, DataContainer $dc): array
    {
        $arrButtons['saveNcompile'] = '<button type="submit" name="saveNcompile" id="saveNcompile" class="tl_submit themeCompileButton" accesskey="t">'.Image::getHtml('bundles/contaothemecompiler/icons/compile.svg').($GLOBALS['TL_LANG']['MSC']['saveNcompile'] ?? null).'</button> ';

        return $arrButtons;
    }

    /**
     * Redirect to maintenance page.
     */
    public function redirectMaintenanceAndCompile(DataContainer $dc): void
    {
        if (isset($_POST['saveNcompile']))
        {
            if (
                $dc->activeRecord
                && !$dc->activeRecord->tstamp
                && null !== ($objTheme = ThemeModel::findById($dc->id))
            ) {
                $objTheme->tstamp = time();
                $objTheme->save(); // Set timestamp to save new record
            }

            $container = System::getContainer();

            // Generate the link to compile the theme
            $strUrl = $container->get('router')->generate('contao_backend', [
                'do' => 'maintenance',
                'act' => 'compile',
                'rt' => $container->get('contao.csrf.token_manager')->getDefaultTokenValue(),
                'theme' => $dc->id,
            ]);

            $this->redirect($strUrl);
        }
    }
}
