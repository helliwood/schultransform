<?php

namespace Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element;

use Symfony\Component\Form\FormBuilderInterface;

/**
 * Interface FormExtenderInterface
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element
 */
interface FormExtenderInterface
{
    /**
     * @param FormBuilderInterface $formBuilder
     */
    public function extendForm(FormBuilderInterface $formBuilder): void;

    /**
     * @return FormBuilderInterface|null
     */
    public function getFormBuilderForChildren(): ?FormBuilderInterface;

    /**
     * @return string
     */
    public function getDataKey(): string;

    /**
     * @param string[]|string|int|null $data
     */
    public function setData($data): void;

    /**
     * @return string|null
     */
    public function getFormThemeTemplate(): ?string;
}
