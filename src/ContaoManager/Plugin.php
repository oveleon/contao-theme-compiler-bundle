<?php

declare(strict_types=1);

/*
 * This file is part of Contao Theme Compiler Bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoThemeCompilerBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Oveleon\ContaoThemeCompilerBundle\ContaoThemeCompilerBundle;

class Plugin implements BundlePluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(ContaoThemeCompilerBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class])
                ->setReplace(['theme-compiler']),
        ];
    }
}
