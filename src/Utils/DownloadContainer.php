<?php


namespace Trollfjord\Utils;


class DownloadContainer
{
    protected ?string $title;
    protected ?string $desc;
    protected ?string $url;
    protected ?string $FileText;
    protected ?string $type;
    const TYPE_DOWNLOAD = "download";
    const TYPE_LINK = "link";

    /**
     * DownloadContainer constructor.
     * @param string|null $title
     * @param string|null $desc
     * @param string|null $url
     * @param string|null $FileText
     * @param string|null $type
     */
    public function __construct(?string $title, ?string $desc, ?string $url, ?string $FileText, ?string $type)
    {
        $this->title = $title;
        $this->desc = $desc;
        $this->url = $url;
        $this->FileText = $FileText;
        $this->type = $type;
    }


    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     */
    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return string|null
     */
    public function getDesc(): ?string
    {
        return $this->desc;
    }

    /**
     * @param string|null $desc
     */
    public function setDesc(?string $desc): void
    {
        $this->desc = $desc;
    }

    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param string|null $url
     */
    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    /**
     * @return string|null
     */
    public function getFileText(): ?string
    {
        return $this->FileText;
    }

    /**
     * @param string|null $FileText
     */
    public function setFileText(?string $FileText): void
    {
        $this->FileText = $FileText;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string|null $type
     */
    public function setType(?string $type): void
    {
        $this->type = $type;
    }


    /**
     * @return array|null
     */
    public function getArray(): ?array
    {
        return array(
            'title' => $this->getTitle(),
            'desc' => $this->getDesc(),
            'url' => $this->getUrl(),
            'FileText' => $this->getFileText(),
            'type' => $this->getType()
        );
    }

}