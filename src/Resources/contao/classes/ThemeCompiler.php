<?php

namespace Oveleon\ContaoThemeCompilerBundle;

/**
 * Theme Compiler
 *
 * @author Daniele Sciannimanica <https://github.com/doishub>
 */
class ThemeCompiler extends \Backend implements \executable
{
	/**
	 * Return true if the module is active
	 *
	 * @return boolean
	 */
	public function isActive()
	{
        return \Input::get('act') == 'compile';
	}

	/**
	 * Generate the module
	 *
	 * @return string
	 */
	public function run()
	{
		/** @var BackendTemplate|object $objTemplate */
		$objTemplate = new \BackendTemplate('be_theme_compiler');
		$objTemplate->action = ampersand(\Environment::get('request'));
		$objTemplate->headline = $GLOBALS['TL_LANG']['tl_maintenance']['themeCompiler'];
		$objTemplate->isActive = $this->isActive();

        // Compile files
        if (\Input::get('act') == 'compile')
        {
            $intId = \Input::get('theme');

            if($intId)
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
                    $objTemplate->class= 'tl_error';
                    $objTemplate->explain = $e->getMessage();
                }
            }
            else
            {
                $objTemplate->class= 'tl_error';
                $objTemplate->explain = $GLOBALS['TL_LANG']['tl_maintenance']['themeCompilerNoTheme'];
            }

            $objTemplate->indexContinue = $GLOBALS['TL_LANG']['MSC']['continue'];
            $objTemplate->isRunning = true;

            return $objTemplate->parse();
        }

        $objTheme = \ThemeModel::findAll();
        $arrThemes = array();

        if($objTheme !== null)
        {
            while($objTheme->next())
            {
                $arrThemes[ $objTheme->id ] = $objTheme->name;
            }
        }

        $objTemplate->themes = $arrThemes;
        $objTemplate->themesDescription = $GLOBALS['TL_LANG']['tl_maintenance']['themeCompilerThemePicker'];
        $objTemplate->submit = $GLOBALS['TL_LANG']['tl_maintenance']['themeCompilerCompile'];

		return $objTemplate->parse();
	}
}
