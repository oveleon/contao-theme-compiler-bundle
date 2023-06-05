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
use Oveleon\ContaoThemeCompilerBundle\FileCompiler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Converts the StyleManager object to the new schema.
 *
 * @internal
 */
class ThemeCompileCommand extends Command
{
    protected static $defaultName = 'contao:themecompiler:compile';
    protected static $defaultDescription = 'Compiles themes that are registered with contao-theme-compiler-bundle.';

    protected ContaoFramework $framework;

    public function __construct(ContaoFramework $contaoFramework)
    {
        $this->framework = $contaoFramework;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('id', InputArgument::REQUIRED, 'The id of the theme.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (null === $input->getArgument('id'))
        {
            return Command::FAILURE;
        }

        $io = new SymfonyStyle($input, $output);
        $this->framework->initialize();

        try
        {
            $compiler = (new FileCompiler($input->getArgument('id')));
            $compiler->compileAll();

            $arrMessages = $compiler->getMessages();

            if (empty($arrMessages))
            {
                $io->error('No configurations could be found');
            }
            else
            {
                foreach ($arrMessages as $arrMessage) {

                    $type    = $arrMessage['type'];
                    $message =  $arrMessage['message'];

                    switch ($type)
                    {
                        case FileCompiler::MSG_HEAD:
                            $io->title($message);
                            break;

                        case FileCompiler::MSG_ERROR:
                        case FileCompiler::MSG_WARN:
                            $io->warning($message);
                            break;

                        case FileCompiler::MSG_NOTE:
                            $io->note($message);
                            break;

                        case FileCompiler::MSG_SUCCESS:
                            $io->success($message);
                            break;

                        default:
                            $io->block($message, 'INFO', 'fg=yellow', ' ');
                    }
                }
            }
        }
        catch(\Exception $e)
        {
            $io->error($e->getMessage());
        }

        return Command::SUCCESS;
    }
}
