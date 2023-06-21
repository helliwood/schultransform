<?php

namespace Trollfjord\Bundle\MediaBaseBundle\DependencyInjection;

use Trollfjord\Bundle\MediaBaseBundle\Arranger\Audio;
use Trollfjord\Bundle\MediaBaseBundle\Arranger\Image;
use Trollfjord\Bundle\MediaBaseBundle\Arranger\Pdf;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Trollfjord\Bundle\MediaBaseBundle\Arranger\DefaultArranger;

/**
 * Class Configuration
 *
 * @author Tim Wettstein <wettstein@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\DependencyInjection
 */
class Configuration implements ConfigurationInterface
{

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('media_base');

        $treeBuilder->getRootNode()
                ->children()
                    ->scalarNode('storage_service')
                        ->defaultValue('media_base.storage.filesystem')
                    ->end()
                    ->scalarNode('storage_service_cache')
                        ->defaultValue('media_base.storage.filesystem_cache')
                    ->end()
                    ->scalarNode('file_path')
                        ->defaultValue('%kernel.project_dir%/mediabase')
                    ->end()
                    ->scalarNode('file_path_cache')
                        ->defaultValue('%kernel.project_dir%/mediabase/cache')
                    ->end()
                    ->arrayNode("mimeTypes")
                        ->scalarPrototype()->end()
                        ->defaultValue([
                            'image/.*' => [
                                'arranger' => Image::class,
                                'allowed_extensions' => [
                                    'jpg',
                                    'jpeg',
                                    'png',
                                    'svg'
                                ],
                                'max_size' => '14M',
                                'icon' => 'fas fa-image',
                                'filetype' => ['image','file']
                            ],
                            'application/pdf' => [
                                'arranger' => Pdf::class,
                                'allowed_extensions' => [
                                    'pdf'
                                ],
                                'max_size' => '14M',
                                'icon' => 'far fa-file-pdf',
                                'filetype' => 'file'
                            ],
                            'audio/mpeg' => [
                                'arranger' => Audio::class,
                                'allowed_extensions' => [
                                    'mp3'
                                ],
                                'max_size' => '20M',
                                'icon' => 'fas fa-film',
                                'filetype' => ['file', 'audio']
                            ],
                            'application/zip' => [
                                'arranger' => DefaultArranger::class,
                                'allowed_extensions' => [
                                    'zip'
                                ],
                                'max_size' => '14M',
                                'icon' => 'far fa-file-archive',
                                'filetype' => 'file'
                            ],
                            'application/vnd.ms-excel' => [
                                'arranger' => DefaultArranger::class,
                                'allowed_extensions' => [
                                    'xls',
                                    'xlt',
                                    'xla'
                                ],
                                'max_size' => '4M',
                                'icon' => 'far fa-file-excel',
                                'filetype' => 'file'
                            ],
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => [
                                'arranger' => DefaultArranger::class,
                                'allowed_extensions' => [
                                    'xlsx'
                                ],
                                'max_size' => '14M',
                                'icon' => 'far fa-file-excel',
                                'filetype' => 'file'
                            ],
                            'application/application/vnd.ms-powerpoint' => [
                                'arranger' => DefaultArranger::class,
                                'allowed_extensions' => [
                                    'ppt'
                                ],
                                'max_size' => '14M',
                                'icon' => 'far fa-file-powerpoint',
                                'filetype' => 'file'
                            ],
                            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => [
                                'arranger' => DefaultArranger::class,
                                'allowed_extensions' => [
                                    'pptx'
                                ],
                                'max_size' => '14M',
                                'icon' => 'far fa-file-powerpoint',
                                'filetype' => 'file'
                            ],
                            'application/msword' => [
                                'arranger' => DefaultArranger::class,
                                'allowed_extensions' => [
                                    'doc'
                                ],
                                'max_size' => '14M',
                                'icon' => 'far fa-file-word',
                                'filetype' => 'file'
                            ],
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => [
                                'arranger' => DefaultArranger::class,
                                'allowed_extensions' => [
                                    'docx'
                                ],
                                'max_size' => '14M',
                                'icon' => 'far fa-file-word',
                                'filetype' => 'file'
                            ],
                             'text/plain' => [
                                'arranger' => DefaultArranger::class,
                                'allowed_extensions' => [
                                    'json',
                                    'xml',
                                    'csv'
                                ],
                                'max_size' => '4M',
                                'icon' => 'far fa-light fa-file-lines',
                                'filetype' => 'file'
                            ],
                            'application/json' => [
                                'arranger' => DefaultArranger::class,
                                'allowed_extensions' => [
                                    'json'
                                ],
                                'max_size' => '4M',
                                'icon' => 'far fa-light fa-file-lines',
                                'filetype' => 'file'
                            ]
                        ])
                    ->end()
                ->end();

        return $treeBuilder;
    }
}
