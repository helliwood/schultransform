<?php

namespace Trollfjord\Bundle\QuestionnaireBundle\TemplateEngine\Element;

use Doctrine\ORM\EntityRepository;
use DOMNodeList;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\AbstractElement;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\FormExtenderInterface;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\PublishVariablesInterface;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\Exception;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Questionnaire;
use function is_string;

/**
 * Class QuestionnaireId
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element
 */
class QuestionnaireId extends AbstractElement implements FormExtenderInterface, PublishVariablesInterface
{
    /**
     * @var string
     */
    protected static string $name = 'questionnaire-id';

    /**
     * @var string|null
     */
    protected ?string $data = null;

    /**
     * QuestionnaireId constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->createAttribute("id", true);
    }

    /**
     * @return DOMNodeList|null
     * @throws Exception
     */
    public function renderNodeList(): ?DOMNodeList
    {
        $id = $this->getDataKey();

        $fragment = $this->getDOMElement()->ownerDocument->createDocumentFragment();
        $fragment->appendXML('<![CDATA[' . $this->data . ']]>');
        return $fragment->childNodes;
    }

    /**
     * @param FormBuilderInterface $formBuilder
     * @throws Exception
     */
    public function extendForm(FormBuilderInterface $formBuilder): void
    {
        $options = [
            'label' => "Fragebogen: ",
            'constraints' => [new NotBlank()],
            'class' => Questionnaire::class,
            'choice_label' => 'name',
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('q')
                    ->orderBy('q.name', 'ASC');
            },
            'choice_value' => function ($questionnaire) {
                if (is_string($questionnaire)) {
                    return $questionnaire;
                } else {
                    return $questionnaire ? $questionnaire->getId() : null;
                }
            }
        ];
        $formBuilder->add($this->getDataKey(), EntityType::class, $options);
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
        return $this->getAttributeValue("id");
    }

    /**
     * @param $data
     */
    public function setData($data): void
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