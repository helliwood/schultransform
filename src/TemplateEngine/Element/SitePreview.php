<?php

namespace Trollfjord\TemplateEngine\Element;

use DOMNodeList;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Trollfjord\Bundle\ContentTreeBundle\Entity\Site;
use Trollfjord\Bundle\ContentTreeBundle\Repository\SiteRepository;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\AbstractElement;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\FormExtenderInterface;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\PublishVariablesInterface;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\Exception;
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
 * @package Trollfjord\TemplateEngine\Element
 */
class SitePreview extends AbstractElement implements FormExtenderInterface, PublishVariablesInterface
{
    /**
     * @var string
     */
    protected static string $name = 'sitepreview';

    /**
     * @var string|null
     */
    protected ?string $data = null;

    /**
     * @var SiteRepository
     */
    protected SiteRepository $siteRepository;

    /**
     * @var Environment
     */
    protected Environment $twig;

    /**
     * Text constructor.
     * @param SiteRepository $siteRepository
     * @param Environment $twig
     */
    public function __construct(SiteRepository $siteRepository, Environment $twig)
    {
        parent::__construct();
        $this->createAttribute("parent", true);
        $this->createAttribute("amountmainnews", true);
        $this->createAttribute("amountnews", true);
        $this->createAttribute("amountimages", true);
        $this->createAttribute("sort", true);
        $this->createAttribute("filter", true);
        $this->siteRepository = $siteRepository;
        $this->twig = $twig;
    }


    private function getLinkedValue($value, $sub = "")
    {
        $val = $this->getAttributeValue($value);

        if (substr($val, 0, 1) === "$") {
            $val = $this->getVariable(substr($val, 1) . $sub);
        }
        return $val;
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
        $mainnews = [];
        $news = [];
        $parentId = $this->getLinkedValue('parent', '_id');
        $amountMainNews = $this->getLinkedValue('amountmainnews');
        $amountnews = $this->getLinkedValue('amountnews');
        $filter = $this->getLinkedValue('filter');
        $sort = $this->getLinkedValue('sort');
        $amountimages = $this->getLinkedValue('amountimages');

        $newsRay = [];
        $iterator = 0;

        $content = '';

        if (is_numeric($parentId)) {
            $parentSite = $this->siteRepository->find($parentId);
            $sites = $parentSite->getChildren();
            if (!$amountnews) {
                $amountnews = 9999999;
            }

            foreach ($sites as $site) {
                $valid = true;
                //If Site has to be part of the Menu
                if (($filter == 2 || $filter == 3) && !$site->isMenuEntry()) {
                    $valid = false;
                }
                //If Site has to be published
                if (($filter == 1 || $filter == 3) && !$site->isPublished()) {
                    $valid = false;
                }
                if (!$valid) {
                    continue;
                }


                $img = $site->getSocialMediaImage();

                if ($img) {

                    $img = $img->getId();
                } else {
                    $img = "";
                }
                $newsRay[] = ['url' => $site->getUrl(), 'name' => $site->getName(), 'title' => $site->getDcTitle(), 'description' => $site->getDcDescription(), 'img' => $img, 'creator' => $site->getDcCreator(), 'date' => $site->getDcDate()->format('d.m.Y')];

            }


            if ($sort > 0) {
                if ($sort == 2) {
                    usort($newsRay, array($this, 'sortByDateDesc'));
                }
                if ($sort == 4) {
                    usort($newsRay, array($this, 'sortByDateAsc'));
                }
            }

            foreach ($newsRay as $newsItem) {
                $iterator++;
                if ($iterator <= $amountMainNews) {
                    $mainnews[] = $newsItem;
                } elseif ($iterator <= $amountnews) {
                    $news[] = $newsItem;
                }
            }


            if ($parentSite) {
                $content = $this->twig->render('frontend/index/30siteteaser.html.twig', [
                    'mainnews' => $mainnews,
                    'news' => $news,
                    'amountimages' => $amountimages,
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

    private static function sortByDateAsc($a, $b)
    {
        return strtotime($a['date']) - strtotime($b['date']);
    }

    private static function sortByDateDesc($a, $b)
    {
        return strtotime($b['date']) - strtotime($a['date']);
    }
}
