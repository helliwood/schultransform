<?php

namespace Trollfjord\Bundle\MediaBaseBundle\TemplateEngine\Element;

use DOMNodeList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\AbstractElement;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\Attribute\Type\AttributeTypeEnum;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\FormExtenderInterface;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\PublishVariablesInterface;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\Exception;
use Trollfjord\Bundle\MediaBaseBundle\Component\Form\Type\MediaType;
use Trollfjord\Bundle\MediaBaseBundle\Service\MediaService;

/**
 * Class Text
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element
 */
class Media extends AbstractElement implements FormExtenderInterface, PublishVariablesInterface
{
    /**
     * @var string
     */
    protected static string $name = 'media';

    /**
     * @var string|null
     */
    protected ?string $data = null;

    /**
     * @var UrlGeneratorInterface
     */
    protected $router;

    /**
     * @var MediaService
     */
    protected $mediaService;

    /**
     * @var Array
     */
    protected $currentData;

    /**
     * @var \Trollfjord\Bundle\MediaBaseBundle\Entity\Media
     */
    protected $currentMedia = null;

    /**
     * Text constructor.
     */
    public function __construct(UrlGeneratorInterface $router, MediaService $mediaService)
    {
        parent::__construct();
        $this->router = $router;
        $this->mediaService = $mediaService;
        $this->createAttribute("id", true);
        $this->createAttribute("label", true);
        $this->createAttribute("filetype", true, "file", new AttributeTypeEnum(array_keys($mediaService->getFiletypes())));
        $this->createAttribute("class");
        $this->createAttribute("alt");
        $this->createAttribute("title");
        $this->createAttribute('srcset');
    }

    /**
     * @return bool
     */
    public function hasSubContent(): bool
    {
        return true;
    }

    /**
     * @return DOMNodeList|null
     */
    public function renderNodeList(): ?DOMNodeList
    {
        $filetype = $this->getAttributeValue("filetype");
        switch($filetype) {
            case "image":
                if($nodeList = $this->getRenderChildes()) {
                    return $nodeList;
                }
                $whString = "";
                if($this->currentData["width"]) $whString.=' width="'.$this->currentData["width"].'"';
                if($this->currentData["height"]) $whString.=' height="'.$this->currentData["height"].'"';

                $fragment = $this->getDOMElement()->ownerDocument->createDocumentFragment();
                $fragment->appendXML('<img '.$whString.' class="'.$this->getAttributeValue("class").'" src="'.$this->router->generate('media_base_public_show', ['id'=>$this->data]).'" />');
                return $fragment->childNodes;
                break;
            default:
                if($nodeList = $this->getRenderChildes()) {
                    return $nodeList;
                }
                $this->getDOMElement()->nodeValue = $this->data;
                return $this->getDOMElement()->childNodes;
                break;
        }

        $this->getDOMElement()->nodeValue = $this->data;
        return $this->getDOMElement()->childNodes;
    }

    /**
     * @return DOMNodeList|false
     */
    private function getRenderChildes() {
        if($this->getDOMElement()->hasChildNodes()) {

            $quelltext = "";
            foreach($this->getDOMElement()->childNodes AS $node) {
                $quelltext .= $this->getDOMElement()->ownerDocument->saveHtml($node);
            }

            foreach($this->currentData AS $k => $v) {
                $quelltext = str_replace('@'.$k, $v, $quelltext);
            }

            $fragment = $this->getDOMElement()->ownerDocument->createDocumentFragment();
            $fragment->appendXML($quelltext);
            return $fragment->childNodes;
        }
        return false;
    }

    /**
     * @param FormBuilderInterface $formBuilder
     * @throws Exception
     */
    public function extendForm(FormBuilderInterface $formBuilder): void
    {
        $id = $this->getAttributeValue("id");
        $label = $this->getAttributeValue("label");
        try {
            $filetype = $this->getAttributeValue("filetype");
        }catch(\Exception $e) {
            $filetype = false;
        }
        $options = [
            'label' => $label,
            'constraints' => [ ],
            'block_prefix' => 'media',
            "attr" => [
                "filetype" => $filetype
            ]
        ];


        $formBuilder->add($this->getDataKey(), MediaType::class, $options);
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
        $this->currentData = [
            "id" => $id,
            "url" => null,
            "alturl" => null,
            "alt" => null,
            "title" => null,
            "description" => null,
            "width" => null,
            "height" => null,
            "extension" => null,
            "size" => null
        ];
        $this->currentMedia = null;
        $_data = [];
        foreach($this->currentData AS $k => $v) {
            if($k!="id") $_data[$id."_".$k] = '';
        }
        $_data[$id] = $this->data;
        if((int) $this->data > 0) {
            $media = $this->mediaService->getMediaById($this->data);
            $altUrl = "";
            if($media instanceof \Trollfjord\Bundle\MediaBaseBundle\Entity\Media) {
                $this->currentMedia = $media;

                $this->currentData["url"] = $this->router->generate('media_base_public_show', ["id"=>$media->getId()]);
                $children = $media->getChildren();
                if($children[0]){
                    $altUrl = $this->router->generate('media_base_public_show', ["id"=>$children[0]->getId()]);
                }
                $_data[$id."_alturl"]= $altUrl;
                $_data[$id."_url"] = $this->currentData["url"];

                $_data[$id."_extension"] = $media->getExtension();
                $_data[$id."_size"] = $media->getFormatFileSize();
                $_data[$id."_description"] = $media->getDescription();


                $this->currentData["alt"] = $media->getName();
                $_data[$id."_alt"] = $this->currentData["alt"];
                foreach($media->getMetas() AS $meta) {
                    $_name = $meta->getName();
                    $this->currentData[$_name] = $meta->getValue();
                    $_data[$id."_".$_name] = $this->currentData[$_name];
                }
                foreach($this->currentData AS $k => $v) {
                    try {
                        $val = $this->getAttributeValue($k);
                        $this->currentData[$k] = $val;
                    }catch (\Exception $e) {}
                }
            }
        }
        return $_data;
    }

    /**
     * @return string|null
     */
    public function getFormThemeTemplate(): ?string
    {
        return '@MediaBase/forms/media.html.twig';
    }
}
