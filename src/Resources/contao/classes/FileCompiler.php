<?php

namespace Oveleon\ContaoThemeCompilerBundle;

use Contao\Config;
use Contao\File;
use Contao\FilesModel;
use Contao\StringUtil;
use Contao\System;
use Contao\ThemeModel;
use ScssPhp\ScssPhp\CompilationResult;
use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\Exception\SassException;
use ScssPhp\ScssPhp\OutputStyle;

/**
 * File Compiler
 *
 * @author Daniele Sciannimanica <https://github.com/doishub>
 */
class FileCompiler
{
    /**
     * The message info type
     * @var string
     */
    const MSG_INFO = 'tc_info';

    /**
     * The message info type
     * @var string
     */
    const MSG_SUCCESS = 'tc_success';

    /**
     * The message warn type
     * @var string
     */
    const MSG_WARN = 'tc_warn';

    /**
     * The message error type
     * @var string
     */
    const MSG_ERROR = 'tc_error';

    /**
     * The file extension for generated files
     * @var string
     */
    const FILE_EXT = '.css';

    /**
     * Theme Object
     * @var \ThemeModel $objTheme
     */
    protected $objTheme;

    /**
     * Source path to config
     * @var array
     */
    protected $configFiles = null;

    /**
     * Configuration fields from custom table field in tl_theme
     * @var string
     */
    protected $config;

    /**
     * Files to combine
     * @var array
     */
    protected $files = null;

    /**
     * Import paths
     * @var array
     */
    protected $importPaths = null;

    /**
     * Web dir relative to TL_ROOT
     * @var string
     */
    protected $webDir;

    /**
     * Target dir
     * @var string
     */
    protected $targetDir;

    /**
     * Messages
     * @var array
     */
    protected $messages;

    /**
     * FileCompiler constructor.
     *
     * @param $themeId
     */
    public function __construct($themeId)
    {
        $this->objTheme = ThemeModel::findById($themeId);

        // Set web dir
        $this->webDir = StringUtil::stripRootDir(System::getContainer()->getParameter('contao.web_dir'));

        // Set target directory
        $objFile = FilesModel::findByUuid($this->objTheme->outputFilesTargetDir);

        if($objFile !== null)
        {
            $this->targetDir = $objFile->path;
        }
        else
        {
            trigger_error('Missing settings for Theme ' . $this->objTheme->name . ': No target directory could be found.', E_USER_ERROR);
        }

        // Collect data from config
        foreach ($GLOBALS['TC_SOURCES'] as $type => $var)
        {
            switch($type)
            {
                case 'configFiles':
                    $this->setConfigFiles($var);
                    break;

                case 'configField':
                    $this->parseConfig($var);
                    break;

                case 'files':
                    $this->addMultiple($var);
                    break;

                case 'importPaths':
                    $this->addImportPath($var);
                    break;
            }
        }
    }

    /**
     * Compile all files from global array
     */
    public function compileAll()
    {
        $this->compileConfigFiles();
        $this->compileSkinFiles();
    }

    /**
     * Compile and save files from config: $GLOBALS['TC_SOURCES']['files']
     */
    public function compileConfigFiles()
    {
        if($this->files !== null)
        {
            $this->msg('Extension files', self::MSG_INFO . ' head');

            foreach ($this->files as $arrFile)
            {
                $filename = StringUtil::standardize($arrFile['name']);
                $content  = $this->getFileContent($arrFile['path']);

                $this->msg('Compile file: ' . $arrFile['path']);

                // Add default import path
                $this->importPaths[] = TL_ROOT . '/' . \dirname($arrFile['path']);

                // Compile content and save file
                $this->saveFile($this->compile($content)->getCss(), $filename);
            }
        }
    }

    /**
     * Compile and save skin files
     */
    public function compileSkinFiles()
    {
        $arrContent = array();
        $skinFiles  = StringUtil::deserialize($this->objTheme->skinSourceFiles);

        if($skinFiles !== null)
        {
            $this->msg('Skin files', self::MSG_INFO . ' head');

            foreach ($skinFiles as $fileUuid)
            {
                $file = FilesModel::findByUuid($fileUuid);

                if($file === null)
                {
                    continue;
                }

                $this->msg('Compile file: ' . $file->path . '/' . $file->name);

                $filename = StringUtil::standardize(basename($file->name, $file->extension));
                $content  = $this->getFileContent($file->path);

                // Add default import path
                $this->importPaths[] = TL_ROOT . '/' . \dirname($file->path);

                // Compile content
                $strCompiled =  $this->compile($content)->getCss();

                if(!!$this->objTheme->combineSkinFiles)
                {
                    $arrContent[$filename] = $strCompiled;
                }
                else
                {
                    $this->saveFile($strCompiled, $filename);
                }
            }

            if(!!$this->objTheme->combineSkinFiles)
            {
                $this->saveFile(
                    implode("\n", array_values($arrContent)),
                    implode("_", array_keys($arrContent))
                );
            }
        }
    }

    /**
     * Compile SCSS file content
     *
     * @param $content
     *
     * @return CompilationResult
     */
    public function compile($content)
    {
        // Create compiler
        $objCompiler = new Compiler();

        // Set compiler formatter
        $objCompiler->setOutputStyle((Config::get('debugMode') ? OutputStyle::EXPANDED : OutputStyle::COMPRESSED));

        // Set import paths
        if($this->importPaths !== null)
        {
            $objCompiler->setImportPaths($this->importPaths);
        }

        if($this->configFiles !== null)
        {
            $tableConfigContent = '';
            $scssConfigContent = '';

            // Use config file
            if($this->config)
            {
                $tableConfigContent = $this->config;
            }

            foreach ($this->configFiles as $configFile)
            {
                $scssConfigContent .= $this->getFileContent($configFile);
            }

            // First the default configuration is added, then the theme configuration
            // which can override the defaults, and then all other files are added.
            $content = $scssConfigContent .
                       $tableConfigContent .
                       $content;
        }
        else
        {
            // Use global config variables
            $arrVars    = StringUtil::deserialize($this->objTheme->vars);
            $globalVars = null;

            foreach ($arrVars as $var)
            {
                $globalVars[ $var['key'] ] = $var['value'];
            }

            if($globalVars !== null)
            {
                $objCompiler->addVariables($globalVars);
            }

            //$objCompiler->getVariables();
        }

        try
        {
            $content = $objCompiler->compileString($content);
        }
        catch (\Exception $e)
        {
            trigger_error($e->getMessage(), E_USER_ERROR);
        } catch (SassException $e) {
            trigger_error($e->getMessage(), E_USER_ERROR);
        }

        return $content;
    }

    /**
     * Create and save a File
     *
     * @param string $content The file content
     * @param string $filename The filename
     * @param string $ext
     *
     * @throws \Exception
     */
    private function saveFile($content, $filename, $ext = self::FILE_EXT)
    {
        $objFile = new File($this->targetDir . '/' . $filename . $ext);

        if($this->objTheme->backupFiles && $objFile->exists())
        {
            $objFile->copyTo($this->targetDir . '/compiler_backup/' . $filename . '_' . date('Y-m-d_H-i') . $ext);
        }

        $objFile->truncate();
        $objFile->write($content . "\n/** Compiled with Theme Compiler */");

        unset($content);
        $objFile->close();

        $this->msg('File saved: ' . $this->targetDir . '/' . $filename . $ext, self::MSG_SUCCESS);
    }

    /**
     * Add a file
     *
     * @param string $strFile  The file to be added
     */
    public function add($strFile)
    {
        // Check the source file
        $strPath = $this->fileExists($strFile);

        // Prevent duplicates
        if (isset($this->files[$strFile]) || !$strPath)
        {
            return;
        }

        $strType = strrchr($strFile, '.');

        // Store the file
        $this->files[] = array
        (
            'name'      => basename($strPath, $strType),
            'path'      => $strPath,
            'extension' => $strType
        );
    }

    /**
     * Add multiple files from an array
     *
     * @param array  $arrFiles   An array of files to be added
     */
    public function addMultiple(array $arrFiles)
    {
        foreach ($arrFiles as $strFile)
        {
            $this->add($strFile);
        }
    }

    /**
     * Add scss import paths
     *
     * @param array  $arrImportPaths   An array of paths
     */
    public function setImportPaths(array $arrImportPaths)
    {
        foreach ($arrImportPaths as $path) {
            $this->addImportPath($path);
        }
    }

    /**
     * Add scss import path
     *
     * @param string $strImportPath
     */
    public function addImportPath($strImportPath)
    {
        $path = $this->dirExists($strImportPath);

        if($path)
        {
            $this->importPaths[] = $path;
        }
    }

    /**
     * Set / overwrite the path to the config file
     *
     * @param $arrSourcePaths
     */
    public function setConfigFiles($arrSourcePaths)
    {
        $this->msg('CSS-Config files', self::MSG_INFO . ' head');

        foreach ($arrSourcePaths as $sourcePath)
        {
            if($strPath = $this->fileExists($sourcePath))
            {
                $this->configFiles[] = $strPath;

                $this->msg('Read file: ' . $strPath);
            }
            else
            {
                $this->msg('Could not found: ' . $strPath, self::MSG_ERROR);
            }
        }
    }

    /**
     * Parse config fields by configField
     *
     * @param $sourceField
     */
    public function parseConfig($sourceField)
    {
        if($sourceField)
        {
            $configVars = $this->objTheme->{$sourceField};

            if($configVars)
            {
                $configVars = StringUtil::deserialize($configVars, true);
                $strConfig  = '';

                foreach ($configVars as $key => $varValue)
                {
                    $configVal = $this->parseVariableValue($varValue);

                    if($configVal || is_bool($configVal))
                    {
                        $strConfig .= sprintf("$%s:%s;\n", $key, is_bool($configVal) ? ($configVal ? 'true' : 'false')  : $configVal);
                    }
                }

                $this->config = $strConfig;

                $this->msg('CSS-Config variables', self::MSG_INFO . ' head');
                $this->msg('Column: ' . $sourceField);
            }
        }
    }

    /**
     * Return the parsed value
     *
     * @param $varValue
     *
     * @return string
     */
    public function parseVariableValue($varValue)
    {
        if($varValue=='')
        {
            return $varValue;
        }

        $varUnserialized = @unserialize($varValue);

        // handle empty or variable values
        list($blnValid, $val) = $this->isValidValue(\is_array($varUnserialized) ? $varUnserialized : $varValue);

        if(!$blnValid)
        {
            return $val;
        }

        // parse values
        if (\is_array($varUnserialized))
        {
            // handle a four sizes field with unit
            if(isset($varUnserialized['top']))
            {
                $strValue = '';

                if($varUnserialized['top'] != '')
                {
                    $strValue .= $varUnserialized['top'] . $varUnserialized['unit'] . ' ';
                }

                if($varUnserialized['top'] != '' && $varUnserialized['right'] != '')
                {
                    $strValue .= $varUnserialized['right'] . $varUnserialized['unit'] . ' ';
                }

                if($varUnserialized['top'] != '' && $varUnserialized['right'] != '' && $varUnserialized['bottom'] != '')
                {
                    $strValue .= $varUnserialized['bottom'] . $varUnserialized['unit'] . ' ';
                }

                if($varUnserialized['top'] != '' && $varUnserialized['right'] != '' && $varUnserialized['bottom'] != '' && $varUnserialized['left'] != '')
                {
                    $strValue .= $varUnserialized['left'] . $varUnserialized['unit'];
                }

                return trim($strValue);
            }

            // handle a single field with unit
            elseif(isset($varUnserialized['unit']))
            {
                return $varUnserialized['value'] . $varUnserialized['unit'];
            }

            // handle a keyValue field
            elseif(isset($varUnserialized[0]['key']) && isset($varUnserialized[0]['value']))
            {
                $arrList = array();

                foreach ($varUnserialized as $ind => $opts)
                {
                    if($opts['key'] && $opts['value'])
                    {
                        $arrList[] = $opts['key'] . ':' . $opts['value'];
                    }
                }

                return '(' . implode(',', $arrList) . ')';
            }

            // handle two-size color fields
            elseif(count($varUnserialized) === 2)
            {
                if(ctype_xdigit($varUnserialized[0]))
                {
                    if($varUnserialized[1])
                    {
                        return 'rgba(' . implode(',', $this->convertHexColor($varUnserialized[0])) . ',' . ($varUnserialized[1] / 100) . ')';
                    }
                }

                $varValue = $varUnserialized[0];
            }
        }

        // handle colors
        if (ctype_xdigit($varValue) && strpos($varValue, '#') !== 0 && \strlen($varValue) == 6)
        {
            return '#' . $varValue;
        }

        if (isset($GLOBALS['TL_HOOKS']['compilerParseVariableValue']) && \is_array($GLOBALS['TL_HOOKS']['compilerParseVariableValue']))
        {
            foreach ($GLOBALS['TL_HOOKS']['compilerParseVariableValue'] as $callback)
            {
                $this->import($callback[0]);
                $varValue = $this->{$callback[0]}->{$callback[1]}($varValue);
            }
        }

        return $varValue;
    }

    /**
     * Validate Value
     *
     * @param $varValue
     *
     * @return array
     */
    protected function isValidValue($varValue)
    {
        if(\is_array($varValue))
        {
            // empty lists
            if(!count($varValue))
            {
                return [false, ''];
            }

            // top,right,bottom,left / unit
            if(array_key_exists('top', $varValue) && (!$varValue['unit'] || $varValue['top']==''))
            {
                if(strpos($varValue['top'], '$') === 0)
                {
                    return [false, $varValue['top']];
                }

                return [false, ''];
            }

            // value / unit
            if(array_key_exists('unit', $varValue) && !array_key_exists('top', $varValue) && (!$varValue['unit'] || $varValue['value']==''))
            {
                if(strpos($varValue['value'], '$') === 0)
                {
                    return [false, $varValue['value']];
                }

                return [false, ''];
            }

            // empty color values (with transparency)
            if(count($varValue) === 2)
            {
                if(!count(array_filter($varValue))){
                    return [false, ''];
                }

                if(strpos($varValue[0] ?? '', '$') === 0)
                {
                    return [false, $varValue[0]];
                }
            }
        }

        return [true, $varValue];
    }

    /**
     * Convert hex colors to rgb
     *
     * @param string  $color
     *
     * @return array
     *
     * @see https://www.php.net/manual/de/function.hexdec.php
     */
    protected function convertHexColor($color)
    {
        $rgb = array();

        // Try to convert using bitwise operation
        if (\strlen($color) == 6)
        {
            $dec = hexdec($color);
            $rgb['red'] = 0xFF & ($dec >> 0x10);
            $rgb['green'] = 0xFF & ($dec >> 0x8);
            $rgb['blue'] = 0xFF & $dec;
        }

        // Shorthand notation
        elseif (\strlen($color) == 3)
        {
            $rgb['red'] = hexdec(str_repeat(substr($color, 0, 1), 2));
            $rgb['green'] = hexdec(str_repeat(substr($color, 1, 1), 2));
            $rgb['blue'] = hexdec(str_repeat(substr($color, 2, 1), 2));
        }

        return $rgb;
    }

    /**
     * Return the file content
     *
     * @param $filePath
     *
     * @return string
     */
    public function getFileContent($filePath)
    {
        return file_get_contents(TL_ROOT . '/' . $filePath);
    }

    /**
     * Check if a file exists and return the full path
     *
     * @param $strFilePath
     *
     * @return string
     */
    private function fileExists($strFilePath)
    {
        // Check the source file
        if (!file_exists(TL_ROOT . '/' . $strFilePath))
        {
            // Handle public bundle resources in web/
            if (file_exists(TL_ROOT . '/' .  $this->webDir . '/' . $strFilePath))
            {
                return $this->webDir . '/' . $strFilePath;
            }
            else
            {
                return false;
            }
        }

        return $strFilePath;
    }

    /**
     * Check if a directory exists and return the full path
     *
     * @param $strDirPath
     *
     * @return string
     */
    private function dirExists($strDirPath)
    {
        // Check the source file
        if (!is_dir(TL_ROOT . '/' . $strDirPath))
        {
            // Handle public bundle resources in web/
            if (is_dir(TL_ROOT . '/' .  $this->webDir . '/' . $strDirPath))
            {
                return $this->webDir . '/' . $strDirPath;
            }
            else
            {
                return false;
            }
        }

        return $strDirPath;
    }

    /** Add an message
     *
     * @param $message
     * @param string $type
     */
    private function msg($message, $type = self::MSG_INFO)
    {
        $this->messages[] = array(
            'message'  => $message,
            'type'     => $type
        );
    }

    /**
     * Returns all messages
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }
}
