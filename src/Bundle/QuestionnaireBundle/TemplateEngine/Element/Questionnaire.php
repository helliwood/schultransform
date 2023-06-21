<?php

namespace Trollfjord\Bundle\QuestionnaireBundle\TemplateEngine\Element;

use DOMNodeList;
use Symfony\Component\Form\FormBuilderInterface;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\AbstractElement;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\FormExtenderInterface;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\PublishVariablesInterface;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\Exception;
use Trollfjord\Bundle\QuestionnaireBundle\Repository\QuestionnaireRepository;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use function is_numeric;

/**
 * Class Questionnaire
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element
 */
class Questionnaire extends AbstractElement implements FormExtenderInterface, PublishVariablesInterface
{
    /**
     * @var string
     */
    protected static string $name = 'questionnaire';

    /**
     * @var string|null
     */
    protected ?string $data = null;

    /**
     * @var QuestionnaireRepository
     */
    protected QuestionnaireRepository $questionnaireRepository;

    /**
     * @var Environment
     */
    protected Environment $twig;

    /**
     * Text constructor.
     * @param QuestionnaireRepository $questionnaireRepository
     * @param Environment             $twig
     */
    public function __construct(QuestionnaireRepository $questionnaireRepository, Environment $twig)
    {
        parent::__construct();
        $this->createAttribute("questionnaireId", true);
        $this->questionnaireRepository = $questionnaireRepository;
        $this->twig = $twig;
    }

    /**
     * @return DOMNodeList|null
     * @throws Exception
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function renderNodeList(): ?DOMNodeList
    {
        $questionnaireId = $this->getVariable("questionnaireId");


        $content = '';
        if (is_numeric($questionnaireId)) {
            $questionnaire = $this->questionnaireRepository->find($questionnaireId);

            if ($questionnaire) {
                $content = $this->twig->render('@Questionnaire/show_questionnaire_in_snippet.html.twig', [
                    'questionnaire' => $questionnaire
                ]);
            }
        }
        $fragment = $this->getDOMElement()->ownerDocument->createDocumentFragment();
        $fragment->appendXML('<![CDATA[' . $content . ']]>');
        return $fragment->childNodes;
    }

    /**
     * @param FormBuilderInterface $formBuilder
     * @throws Exception
     */
    public function extendForm(FormBuilderInterface $formBuilder): void
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
        return "questionnaireId";
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