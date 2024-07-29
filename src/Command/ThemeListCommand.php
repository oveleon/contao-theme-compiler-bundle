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

namespace Oveleon\ContaoThemeCompilerBundle\Command;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FilesModel;
use Contao\ThemeModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Converts the StyleManager object to the new schema.
 *
 * @internal
 */
class ThemeListCommand extends Command
{
    protected static $defaultName = 'contao:themecompiler:list';

    protected static $defaultDescription = 'Gets a list of all themes';

    public function __construct(protected ContaoFramework $framework)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->framework->initialize();

        $objTheme = ThemeModel::findAll();

        $io->title('Themes');

        if (null !== $objTheme)
        {
            while ($objTheme->next())
            {
                $outputDir = FilesModel::findByUuid($objTheme->outputFilesTargetDir)->path ?? '{{empty}}';

                $io->block(
                    $objTheme->name.' [Target directory: '.$outputDir.' ]',
                    (string) $objTheme->id,
                    'fg=yellow',
                    ' ',
                );
            }
        }
        else
        {
            $io->warning('No themes have been found');
        }

        return Command::SUCCESS;
    }
}
