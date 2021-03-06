<?php
/**
 * This file is part of the init-cms-sandbox  package.
 *
 * (c) net working AG <info@networking.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Networking\InitCmsBundle\DependencyInjection\Compiler;

use Networking\InitCmsBundle\Provider\VimeoProvider;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Networking\InitCmsBundle\Provider\FileProvider;
use Networking\InitCmsBundle\Provider\ImageProvider;
use Networking\InitCmsBundle\Provider\YouTubeProvider;

class AddProviderCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds('sonata.media.provider') as $id => $attributes) {
            $definition = $container->getDefinition($id);

            if ('sonata.media.provider.image' == $id) {
                $definition->setClass(ImageProvider::class);
                $definition->addMethodCall('addFormat', ['admin', [
                    'quality' => 100,
                    'width' => 190,
                    'height' => null,
                    'constraint' => true,
                    'resizer' => false,

                ]]);
            }

            if ('sonata.media.provider.file' == $id) {
                $definition->setClass(FileProvider::class);
            }

            if ('sonata.media.provider.youtube' == $id) {
                $definition->setClass(YouTubeProvider::class);
                $definition->addMethodCall('addFormat', ['admin', [
                    'quality' => 100,
                    'width' => 190,
                    'height' => null,
                    'constraint' => true,
                ]]);
            }

            if ('sonata.media.provider.vimeo' == $id) {
                $definition->setClass(VimeoProvider::class);
                $definition->addMethodCall('addFormat', ['admin', [
                    'quality' => 100,
                    'width' => 190,
                    'height' => null,
                    'constraint' => true,
                ]]);
            }

            if ('sonata.media.provider.vimeo' == $id) {
                $definition->addMethodCall('addFormat', ['admin', [
                    'quality' => 100,
                    'width' => 190,
                    'height' => null,
                    'constraint' => true,
                ]]);
            }
        }
    }
}
