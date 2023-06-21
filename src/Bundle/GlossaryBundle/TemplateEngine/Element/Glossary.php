<?php

namespace Trollfjord\Bundle\GlossaryBundle\TemplateEngine\Element;

use DOMNodeList;
use Symfony\Component\Form\FormBuilderInterface;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\AbstractElement;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\FormExtenderInterface;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\PublishVariablesInterface;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\Exception;
use Trollfjord\Bundle\GlossaryBundle\Repository\GlossaryRepository;
use Twig\Environment;


/**
 * Class Glossary
 *
 * @author  Juan Mayoral <mayoral@helliwood.com>
 *
 * @package Trollfjord\Bundle\GlossaryBundle\TemplateEngine\Element
 */
class Glossary extends AbstractElement implements FormExtenderInterface, PublishVariablesInterface
{
    /**
     * @var string
     */
    protected static string $name = 'glossary';

    /**
     * @var string|null
     */
    protected ?string $data = null;

    /**
     * @var GlossaryRepository
     */
    protected GlossaryRepository $glossaryRepository;

    /**
     * @var Environment
     */
    protected Environment $twig;

    /**
     * Text constructor.
     * @param GlossaryRepository $glossaryRepository
     * @param Environment             $twig
     */
    public function __construct(GlossaryRepository $glossaryRepository, Environment $twig)
    {
        parent::__construct();
        $this->createAttribute("wordId", true);
        $this->glossaryRepository = $glossaryRepository;
        $this->twig = $twig;
    }

    /**
     * @return DOMNodeList|null
     * @throws Exception
     */
    public function renderNodeList(): ?DOMNodeList
    {
        return null;
    }

    /**
     * @param FormBuilderInterface $formBuilder
     * @throws Exception
     */
    public function extendForm(FormBuilderInterface $formBuilder):void
    {

    }

    /**
     * @return FormBuilderInterface|null
     */
    public function getFormBuilderForChildren(): ?FormBuilderInterface
    {
        return null;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getDataKey(): string
    {
        return "wordId";
    }

    /**
     * @param $data
     */
    public function setData($data):void
    {
        $this->data = $data;
    }

    /**
     * @return string[]
     * @throws Exception
     */
    public function publishVariables(): array
    {
        $id = $this->getScopedId();
        return [$id => $this->data];
    }

    /**
     * @return string|null
     */
    public function getFormThemeTemplate(): ?string
    {
        return null;
    }
}