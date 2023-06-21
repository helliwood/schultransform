<?php

namespace Trollfjord\Bundle\QuestionnaireBundle\TemplateEngine\Element;

use DOMNodeList;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\AbstractElement;
use Trollfjord\Bundle\QuestionnaireBundle\Service\QuestionnaireService;

/**
 * Class QuestionnaireStats
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element
 */
class QuestionnaireStats extends AbstractElement
{
    /**
     * @var string
     */
    protected static string $name = 'questionnaire-stats';

    /**
     * @var QuestionnaireService
     */
    protected QuestionnaireService $questionnaireService;

    /**
     * Text constructor.
     * @param QuestionnaireService $questionnaireService
     */
    public function __construct(QuestionnaireService $questionnaireService)
    {
        parent::__construct();
        $this->questionnaireService = $questionnaireService;
    }

    /**
     * @return DOMNodeList|null
     */
    public function renderNodeList(): ?DOMNodeList
    {
        $questionnaireId = $this->getVariable("questionnaireId");


        $content = $this->questionnaireService->getSchoolIndex();
        /*
        if (is_numeric($questionnaireId)) {
            $questionnaire = $this->questionnaireRepository->find($questionnaireId);

            if ($questionnaire) {
                $content = $this->twig->render('@Questionnaire/show_questionnaire_in_snippet.html.twig', [
                    'questionnaire' => $questionnaire
                ]);
            }
        }*/
        $fragment = $this->getDOMElement()->ownerDocument->createDocumentFragment();
        $fragment->appendXML('<![CDATA[' . $content . ']]>');
        return $fragment->childNodes;
    }
}