<?php

/*
 * This file is part of Contao Theme Compiler Bundle.
 *
 * @package     contao-theme-compiler-bundle
 * @license     MIT
 * @author      Daniele Sciannimanica  <https://github.com/doishub>
 * @copyright   Oveleon                <https://www.oveleon.de/>
 */

namespace Oveleon\ContaoThemeCompilerBundle\Compiler;

use Contao\Config;
use Contao\File;
use Contao\FilesModel;
use Contao\StringUtil;
use Contao\System;
use Contao\ThemeModel;
use Exception;
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
    const MSG_INFO = 'tc_info';
    const MSG_HEAD = 'tc_info head';
    const MSG_SUCCESS = 'tc_success';
    const MSG_WARN = 'tc_warn';
    const MSG_ERROR = 'tc_error';
    const MSG_NOTE = 'tc_note';
    const FILE_EXT = '.css';

    /**
     * Theme Object
     */
    public null|ThemeModel $objTheme;

    /**
     * Source path to config
     */
    protected ?array $configFiles = null;

    /**
     * Configuration fields from custom table field in tl_theme
     */
    protected ?string $config = null;

    /**
     * Files to combine
     */
    protected ?array $files = null;

    /**
     * Import paths
     */
    protected ?array $importPaths = null;

    /**
     * Web dir relative to root dir
     */
    protected string $webDir;

    /**
     * Target dir
     */
    protected string $targetDir;

    /**
     * Messages
     */
    protected ?array $messages = [];

    /**
     * Root directory
     */
    protected string $rootDir;

    /**
     * Custom skin files
     */
    public array $customSkinFiles = [];

    /**
     * FileCompiler constructor.
     */
    public function __construct($themeId)
    {
        $container = System::getContainer();

        $this->rootDir  = $container->getParameter('kernel.project_dir');
        $this->objTheme = ThemeModel::findById($themeId);

        // Set web dir
        $this->webDir = StringUtil::stripRootDir($container->getParameter('contao.web_dir'));

        // Set target directory
        $objFile = FilesModel::findByUuid($this->objTheme->outputFilesTargetDir);

        if (null !== $objFile)
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
            switch ($type)
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

        // Hook for custom methods
        if (isset($GLOBALS['TC_HOOKS']['beforeCompile']) && \is_array($GLOBALS['TC_HOOKS']['beforeCompile']))
        {
            foreach ($GLOBALS['TC_HOOKS']['beforeCompile'] as $callback)
            {
                System::importStatic($callback[0])->{$callback[1]}($this);
            }
        }
    }

    /**
     * Compile all files from global array
     */
    public function compileAll(): void
    {
        $this->compileConfigFiles();
        $this->compileSkinFiles();
    }

    /**
     * Compile and save files from config: $GLOBALS['TC_SOURCES']['files']
     */
    public function compileConfigFiles(): void
    {
        if (null !== $this->files)
        {
            $this->msg('Files', self::MSG_HEAD);

            foreach ($this->files as $arrFile)
            {
                $filename = StringUtil::standardize($arrFile['name']);
                $content  = $this->getFileContent($arrFile['path']);

                $this->msg('Compile file: ' . $arrFile['path']);

                // Add default import path
                $this->importPaths[] = $this->rootDir . '/' . \dirname($arrFile['path']);

                // Compile content and save file
                $this->saveFile($this->compile($content)->getCss(), $filename);
            }
        }
    }

    /**
     * Compile and save skin files
     */
    public function compileSkinFiles(): void
    {
        $arrContent = [];
        $skinFiles = array_merge($this->customSkinFiles, StringUtil::deserialize($this->objTheme->skinSourceFiles, true));

        if (!empty($skinFiles))
        {
            // reverse loading order so following skin files will override configurations from previous ones
            $skinFiles = array_reverse($skinFiles);

            $this->msg('Skin files', self::MSG_HEAD);

            foreach ($skinFiles as $fileUuid)
            {
                $file = FilesModel::findByUuid($fileUuid);

                if ($file === null)
                {
                    continue;
                }

                $this->msg('Compile file: ' . $file->path . '/' . $file->name);

                $filename = StringUtil::standardize(basename($file->name, $file->extension));
                $content  = $this->getFileContent($file->path);

                // Add default import path
                $this->importPaths[] = $this->rootDir . '/' . \dirname($file->path);

                // Compile content
                $strCompiled =  $this->compile($content)->getCss();

                if (!!$this->objTheme->combineSkinFiles)
                {
                    $arrContent[$filename] = $strCompiled;
                }
                else
                {
                    $this->saveFile($strCompiled, $filename);
                }
            }

            if (!!$this->objTheme->combineSkinFiles)
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
     */
    public function compile($content): CompilationResult|string
    {
        // Create compiler
        $objCompiler = new Compiler();

        // Set compiler formatter
        $objCompiler->setOutputStyle((Config::get('debugMode') ? OutputStyle::EXPANDED : OutputStyle::COMPRESSED));

        // Set import paths
        if ($this->importPaths !== null)
        {
            $objCompiler->setImportPaths($this->importPaths);
        }

        if ($this->configFiles !== null)
        {
            $tableConfigContent = '';
            $scssConfigContent = '';

            // Use config file
            if ($this->config)
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

            if ($globalVars !== null)
            {
                $objCompiler->addVariables($globalVars);
            }

            //$objCompiler->getVariables();
        }

        try
        {
            $content = $objCompiler->compileString($content);
        }
        catch (Exception|SassException $e)
        {
            trigger_error($e->getMessage(), E_USER_ERROR);
        }

        return $content;
    }

    /**
     * Create and save a File
     *
     * @throws Exception
     */
    private function saveFile(string $content, string $filename, string $ext = self::FILE_EXT): void
    {
        $objFile = new File($this->targetDir . '/' . $filename . $ext);

        if ($this->objTheme->backupFiles && $objFile->exists())
        {
            $objFile->copyTo($this->targetDir . '/compiler_backup/' . $filename . '_' . date('Y-m-d_H-i') . $ext);
        }

        $objFile->truncate();

        if ($objFile->write($content . "\n/** Compiled with Theme Compiler */"))
        {
            $this->msg('File saved: ' . $this->targetDir . '/' . $filename . $ext, self::MSG_SUCCESS);
        }
        else
        {
            $this->msg('File could not be saved: ' . $this->targetDir . '/' . $filename . $ext, self::MSG_ERROR);
        }

        unset($content);
        $objFile->close();
    }

    /**
     * Add a file
     */
    public function add(string $strFile): void
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
        $this->files[] = [
            'name'      => basename($strPath, $strType),
            'path'      => $strPath,
            'extension' => $strType
        ];
    }

    /**
     * Add multiple files from an array
     */
    public function addMultiple(array $arrFiles): void
    {
        foreach ($arrFiles as $strFile)
        {
            $this->add($strFile);
        }
    }

    /**
     * Add scss import paths
     */
    public function setImportPaths(array $arrImportPaths): void
    {
        foreach ($arrImportPaths as $path) {
            $this->addImportPath($path);
        }
    }

    /**
     * Add scss import path
     */
    public function addImportPath(string $strImportPath): void
    {
        $path = $this->dirExists($strImportPath);

        if ($path)
        {
            $this->importPaths[] = $path;
        }
    }

    /**
     * Set / overwrite the path to the config file
     */
    public function setConfigFiles($arrSourcePaths): void
    {
        $this->msg('Config', self::MSG_HEAD);

        foreach ($arrSourcePaths as $sourcePath)
        {
            if ($strPath = $this->fileExists($sourcePath))
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
     */
    public function parseConfig($sourceField): void
    {
        if ($sourceField)
        {
            $configVars = $this->objTheme->{$sourceField};

            if ($configVars)
            {
                $configVars = StringUtil::deserialize($configVars, true);

                if (isset($GLOBALS['TC_HOOKS']['compilerParseConfig']) && \is_array($GLOBALS['TC_HOOKS']['compilerParseConfig']))
                {
                    foreach ($GLOBALS['TC_HOOKS']['compilerParseConfig'] as $callback)
                    {
                        System::importStatic($callback[0])->{$callback[1]}($this, $configVars);
                    }
                }

                $strConfig  = '';

                foreach ($configVars as $key => $varValue)
                {
                    $configVal = $this->parseVariableValue($varValue);

                    if ($configVal || is_bool($configVal))
                    {
                        $strConfig .= sprintf("$%s:%s;\n", $key, is_bool($configVal) ? ($configVal ? 'true' : 'false')  : $configVal);
                    }
                }

                $this->config = $strConfig;

                $this->msg('Config Variables', self::MSG_HEAD);
                $this->msg('Theme: ' . $this->objTheme->name);
                $this->msg('Column: ' . $sourceField);
            }
        }
    }

    /**
     * Return the parsed value
     */
    public function parseVariableValue($varValue): ?string
    {
        if ('' == $varValue)
        {
            return $varValue;
        }

        $varUnserialized = @unserialize($varValue);

        // handle empty or variable values
        list($blnValid, $val) = $this->isValidValue(\is_array($varUnserialized) ? $varUnserialized : $varValue);

        if (!$blnValid)
        {
            return $val;
        }

        // parse values
        if (\is_array($varUnserialized))
        {
            // handle a four sizes field with unit
            if (isset($varUnserialized['top']))
            {
                $strValue = '';

                if ($varUnserialized['top'] != '')
                {
                    $strValue .= $varUnserialized['top'] . $varUnserialized['unit'] . ' ';
                }

                if ($varUnserialized['top'] != '' && $varUnserialized['right'] != '')
                {
                    $strValue .= $varUnserialized['right'] . $varUnserialized['unit'] . ' ';
                }

                if ($varUnserialized['top'] != '' && $varUnserialized['right'] != '' && $varUnserialized['bottom'] != '')
                {
                    $strValue .= $varUnserialized['bottom'] . $varUnserialized['unit'] . ' ';
                }

                if ($varUnserialized['top'] != '' && $varUnserialized['right'] != '' && $varUnserialized['bottom'] != '' && $varUnserialized['left'] != '')
                {
                    $strValue .= $varUnserialized['left'] . $varUnserialized['unit'];
                }

                return trim($strValue);
            }

            // handle a single field with unit
            elseif (isset($varUnserialized['unit']))
            {
                return $varUnserialized['value'] . $varUnserialized['unit'];
            }

            // handle a keyValue field
            elseif (isset($varUnserialized[0]['key']) && isset($varUnserialized[0]['value']))
            {
                $arrList = [];

                foreach ($varUnserialized as $ind => $opts)
                {
                    if ($opts['key'] && $opts['value'])
                    {
                        $arrList[] = $opts['key'] . ':' . $opts['value'];
                    }
                }

                return '(' . implode(',', $arrList) . ')';
            }

            // handle two-size color fields
            elseif (count($varUnserialized) === 2)
            {
                if (ctype_xdigit($varUnserialized[0]))
                {
                    if ($varUnserialized[1])
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

        if (isset($GLOBALS['TC_HOOKS']['compilerParseVariableValue']) && \is_array($GLOBALS['TC_HOOKS']['compilerParseVariableValue']))
        {
            foreach ($GLOBALS['TC_HOOKS']['compilerParseVariableValue'] as $callback)
            {
                System::importStatic($callback[0])->{$callback[1]}($this, $varValue);
            }
        }

        return $varValue;
    }

    /**
     * Validate Value
     */
    protected function isValidValue($varValue): array
    {
        if (\is_array($varValue))
        {
            // empty lists
            if (!count($varValue))
            {
                return [false, ''];
            }

            // top,right,bottom,left / unit
            if (array_key_exists('top', $varValue) && (!$varValue['unit'] || $varValue['top']==''))
            {
                if (strpos($varValue['top'], '$') === 0)
                {
                    return [false, $varValue['top']];
                }

                return [false, ''];
            }

            // value / unit
            if (array_key_exists('unit', $varValue) && !array_key_exists('top', $varValue) && (!$varValue['unit'] || $varValue['value']==''))
            {
                if (strpos($varValue['value'], '$') === 0)
                {
                    return [false, $varValue['value']];
                }

                return [false, ''];
            }

            // empty color values (with transparency)
            if (count($varValue) === 2)
            {
                if (!count(array_filter($varValue))){
                    return [false, ''];
                }

                if (strpos($varValue[0] ?? '', '$') === 0)
                {
                    return [false, $varValue[0]];
                }
            }
        }

        return [true, $varValue];
    }

    /**
     * Convert hex colors to rgb
     * @see https://www.php.net/manual/de/function.hexdec.php
     */
    protected function convertHexColor(string $color): array
    {
        $rgb = [];

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
     */
    public function getFileContent($filePath): string
    {
        return file_get_contents($this->rootDir . '/' . $filePath);
    }

    /**
     * Check if a file exists and return the full path
     */
    private function fileExists(string $strFilePath): bool|string
    {
        // Check the source file
        if (!file_exists($this->rootDir . '/' . $strFilePath))
        {
            // Handle public bundle resources in web/
            if (file_exists($this->rootDir . '/' .  $this->webDir . '/' . $strFilePath))
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
     */
    private function dirExists(string $strDirPath): bool|string
    {
        // Check the source file
        if (!is_dir($this->rootDir . '/' . $strDirPath))
        {
            // Handle public bundle resources in web/
            if (is_dir($this->rootDir . '/' .  $this->webDir . '/' . $strDirPath))
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

    /** Add messages
     *
     * @param $message
     * @param string $type
     */
    public function msg($message, string $type = self::MSG_INFO): void
    {
        $this->messages[] = [
            'message'  => $message,
            'type'     => $type
        ];
    }

    /**
     * Returns all messages
     */
    public function getMessages(): array
    {
        return $this->messages;
    }
}
