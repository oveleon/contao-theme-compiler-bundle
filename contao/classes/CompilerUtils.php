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

namespace Oveleon\ContaoThemeCompilerBundle;

use Contao\Backend;
use Contao\DataContainer;
use Contao\Image;
use Contao\System;

class CompilerUtils extends Backend
{
	/**
	 * Add save and compile button
	 */
	public function addSaveNCompileButton(array $arrButtons, DataContainer $dc): array
	{
		$blnDisabled = $dc->activeRecord && !$dc->activeRecord->tstamp;

		$arrButtons['saveNcompile'] = '<button type="submit" name="saveNcompile" id="saveNcompile" class="tl_submit themeCompileButton" accesskey="t" ' . ($blnDisabled ? "disabled" : "") . '>' . Image::getHtml('bundles/contaothemecompiler/icons/compile.svg') . ($GLOBALS['TL_LANG']['MSC']['saveNcompile'] ?? null) . '</button> ';

		return $arrButtons;
	}

    /**
     * Redirect to maintenance page
     */
	public function redirectMaintenanceAndCompile(DataContainer $dc): void
	{
		if (isset($_POST['saveNcompile']))
		{
			// Generate the link to compile the theme
			$strUrl = System::getContainer()->get('router')->generate('contao_backend', [
                'do'    => 'maintenance',
                'act'   => 'compile',
                'rt'    => REQUEST_TOKEN,
                'theme' => $dc->id
            ]);

			$this->redirect($strUrl);
		}
	}
}
