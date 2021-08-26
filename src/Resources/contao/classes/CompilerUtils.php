<?php

namespace Oveleon\ContaoThemeCompilerBundle;

use Contao\Backend;
use Contao\DataContainer;
use Contao\Image;
use Contao\System;

class CompilerUtils extends Backend
{
	/**
	 * Add save n compile button
	 *
	 * @param array $arrButtons
	 * @param DataContainer $dc
	 *
	 * @return array
	 */
	public function addSaveNCompileButton(array $arrButtons, DataContainer $dc)
	{
		$blnDisabled = $dc->activeRecord && !$dc->activeRecord->tstamp;

		$arrButtons['saveNcompile'] = '<button type="submit" name="saveNcompile" id="saveNcompile" class="tl_submit themeCompileButton" accesskey="t" ' . ($blnDisabled ? "disabled" : "") . '>' . Image::getHtml('bundles/contaothemecompiler/icons/compile.svg') . $GLOBALS['TL_LANG']['MSC']['saveNcompile'] . '</button> ';

		return $arrButtons;
	}

	public function redirectMaintenanceAndCompile(DataContainer $dc)
	{
		if (isset($_POST['saveNcompile']))
		{
			// Generate the link to compile the theme
			$strUrl = System::getContainer()->get('router')->generate('contao_backend', array
			(
				'do' => 'maintenance',
				'act' => 'compile',
				'rt' => REQUEST_TOKEN,
				'theme' => $dc->id
			));

			$this->redirect($strUrl);
		}
	}
}
