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
use Contao\BackendTemplate;
use Contao\Environment;
use Contao\Input;
use Contao\StringUtil;
use Contao\ThemeModel;
use executable;

class ThemeCompiler extends Backend implements executable
{
	/**
	 * Return true if the module is active
	 */
	public function isActive(): bool
    {
        return Input::get('act') == 'compile';
	}

	/**
	 * Generate the module
	 */
	public function run(): string
    {
		/** @var BackendTemplate|object $objTemplate */
		$objTemplate = new BackendTemplate('be_theme_compiler');
		$objTemplate->action = StringUtil::ampersand(Environment::get('request'));
		$objTemplate->headline = $GLOBALS['TL_LANG']['tl_maintenance']['themeCompiler'] ?? null;
		$objTemplate->isActive = $this->isActive();

        // Compile files
        if ('compile' == Input::get('act'))
        {
            $intId = Input::get('theme');

            if ($intId)
            {
                try
                {
                    $compiler = new FileCompiler($intId);
                    $compiler->compileAll();

                    $arrMessages = $compiler->getMessages();
                    $messages = array();

                    foreach ($arrMessages as $arrMessage) {
                        $messages[] = '<div class="'. $arrMessage['type'] .'">' . $arrMessage['message'] . '</div>';
                    }

                    $objTemplate->logs = implode("", $messages);
                }
                catch(\Exception $e)
                {
                    $objTemplate->class = 'tl_error';
                    $objTemplate->explain = $e->getMessage();
                }
            }
            else
            {
                $objTemplate->class= 'tl_error';
                $objTemplate->explain = $GLOBALS['TL_LANG']['tl_maintenance']['themeCompilerNoTheme'] ?? null;
            }

            $objTemplate->indexContinue = $GLOBALS['TL_LANG']['MSC']['continue'] ?? null;
            $objTemplate->isRunning = true;

            return $objTemplate->parse();
        }

        $objTheme = ThemeModel::findAll();
        $arrThemes = array();

        if ($objTheme !== null)
        {
            while ($objTheme->next())
            {
                $arrThemes[ $objTheme->id ] = $objTheme->name;
            }
        }

        $objTemplate->themes = $arrThemes;
        $objTemplate->themesDescription = $GLOBALS['TL_LANG']['tl_maintenance']['themeCompilerThemePicker'] ?? null;
        $objTemplate->submit = $GLOBALS['TL_LANG']['tl_maintenance']['themeCompilerCompile'] ?? null;

		return $objTemplate->parse();
	}
}
