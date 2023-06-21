<?php


namespace Trollfjord\Bundle\MediaBaseBundle\Validator\Constraints;

use Symfony\Component\HttpFoundation\File\File as FileObject;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\FileValidator AS SynfonyFileValidator;

class FileValidator extends SynfonyFileValidator
{
    public function validate($value, Constraint $constraint)
    {
        parent::validate($value, $constraint);

        if ($constraint->extensions) {
            $path = $value instanceof FileObject ? $value->getPathname() : (string) $value;
            $basename = $value instanceof UploadedFile ? $value->getClientOriginalName() : basename($path);
            $_basename = explode('.', $basename);
            $ext = end($_basename);
            if(!in_array(strtolower($ext), $constraint->extensions)) {
                $this->context->buildViolation($constraint->extensionsMessage)
                    ->setParameter('{{ ext }}', $this->formatValue($ext))
                    ->setCode(File::EMPTY_ERROR)
                    ->addViolation();
            }
        }
    }
}