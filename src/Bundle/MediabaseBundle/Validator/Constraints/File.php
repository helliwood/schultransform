<?php

namespace Trollfjord\Bundle\MediaBaseBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\File as SynfonyFile;

class File extends SynfonyFile
{
    public $mimeTypesMessage = 'Der mimeTypes ist nicht erlaubt ({{ type }}). Erlaubt sind nur {{ types }}.';
    public $extensionsMessage = 'Die Dateiendung {{ ext }} ist nicht erlaubt!';
    public $extensions=[];

    public function __construct($options = null)
    {
        parent::__construct($options);
    }

    public function validatedBy()
    {
        return static::class.'Validator';
    }
}