<?php


namespace Trollfjord\Bundle\MediaBaseBundle\Validator\Constraints;


use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;

trait ConstrainsTrait
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $mimeTypes
     * @param array $names[string]
     */
    public function setConstrain(FormBuilderInterface $builder, $mimeTypes=[], array $names) {
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function(\Symfony\Component\Form\Event\PreSubmitEvent $event) use($mimeTypes, $names){
                $form = $event->getForm();
                $data = $event->getData();
                /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */

                foreach ($names as $name) {
                    $file = $data[$name];
                    if ($file) {
                        $_options = [];
                        foreach ($this->mimeTypes as $mimeType => $mimeTypeValues) {
                            if (preg_match('/' . str_replace("/", "\\/", $mimeType) . '/', $file->getMimeType(), $matches)) {
                                $_options["mimeTypes"] = $file->getMimeType();
                                $_options["maxSize"] = $mimeTypeValues["max_size"];
                                $_options["extensions"] = $mimeTypeValues["allowed_extensions"];
                                $_options["extensionsMessage"] = 'Dateiendung {{ ext }} nicht erlaubt';

                                $form->add($name, FileType::class,
                                    [
                                        'label' => _("Datei:"),
                                        'required' => true,
                                        'constraints' => [
                                            new File($_options)
                                        ],
                                    ]
                                );
                            }
                        }
                    }
                }
            });
    }
}