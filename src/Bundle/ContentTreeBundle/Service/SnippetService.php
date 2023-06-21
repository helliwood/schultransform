<?php

namespace Trollfjord\Bundle\ContentTreeBundle\Service;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use DOMDocument;
use Exception;
use Trollfjord\Bundle\ContentTreeBundle\Entity\SiteContent;
use Trollfjord\Bundle\ContentTreeBundle\Entity\SiteContentHistory;
use Trollfjord\Bundle\ContentTreeBundle\Entity\Snippet;
use function array_map;
use function count;
use function explode;
use function file_exists;
use function file_get_contents;
use function filemtime;
use function realpath;
use function sort;

/**
 * Class SnippetsService
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\Service
 */
class SnippetService
{
    /**
     * @var EntityManagerInterface
     */
    protected EntityManagerInterface $entityManager;

    /**
     * SiteService constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param int $snippetId
     * @return Snippet|null
     */
    public function getSnippet(int $snippetId): ?Snippet
    {
        /** @var Snippet $snippet */
        $snippet = $this->entityManager->find(Snippet::class, $snippetId);

        return $snippet;
    }

    /**
     * @param array|string[] $allowedGroups
     * @return array|Snippet[]
     */
    public function getSnippetsByAllowedGroups(array $allowedGroups): array
    {
        $snippetRepository = $this->entityManager->getRepository(Snippet::class);
        return $snippetRepository->findByAllowedGroups($allowedGroups);
    }

    /**
     * @param string $templatePath
     * @param bool   $force
     * @return string[]
     * @throws Exception
     */
    public function insertOrUpdateSnippet(string $templatePath, bool $force = false): array
    {
        $this->entityManager->beginTransaction();
        try {
            $metas = $this->getMetaDataFromTemplate($templatePath);
            $snippet = $this->getSnippet($metas["id"]);
            $changes = [];
            $groups = array_map('trim', explode(',', $metas["groups"]));
            sort($groups);
            $groups = count($groups) > 0 ? $groups : null;
            if (! $snippet) {
                $snippet = new Snippet();
                $snippet->setId($metas["id"]);
                $snippet->setName($metas["name"]);
                $snippet->setOriginalName($metas["name"]);
                $snippet->setGroups($groups);
                $snippet->setTemplate(file_get_contents($metas["file"]));
                $snippet->setFile($metas["file"]);
                $changes[] = "New Snippet";
            } else {
                if ($snippet->getOriginalName() !== $metas["name"]) {
                    $changes[] = "Name changed";
                    $snippet->setOriginalName($metas["name"]);
                }
                if ($snippet->getGroups() !== $groups) {
                    $changes[] = "Groups changed";
                    $snippet->setGroups($groups);
                }
                if ($snippet->getTemplate() !== file_get_contents($metas["file"])) {
                    $changes[] = "Template changed";
                    $snippet->setTemplate(file_get_contents($metas["file"]));
                }
                if ($snippet->getFile() !== $metas["file"]) {
                    $changes[] = "File moved";
                    $snippet->setFile($metas["file"]);
                }
                if ($snippet->isRemoved()) {
                    $changes[] = "File added again";
                    $snippet->setRemoved(false);
                }
                if ($snippet->isForm() !== $metas['form']) {
                    $changes[] = "Is form changed";
                    $snippet->setForm($metas['form']);
                }
                if (count($changes) > 0) {
                    $snippet->setUpdatedAt(new DateTime());
                }

                if ($force) {
                    $this->forceSnippetUpdate($snippet);
                }
            }

            $this->entityManager->persist($snippet);
            $this->entityManager->flush();

            $this->entityManager->commit();
        } catch (Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        return $changes;
    }

    /**
     * @param int[] $snippetIds
     * @return Snippet[]
     */
    public function markAsRemovedWhereNotInList(array $snippetIds): array
    {
        $removed = [];
        /** @var Snippet[] $snippets */
        $snippets = $this->entityManager
            ->getRepository(Snippet::class)
            ->findWhereNotInList($snippetIds);
        foreach ($snippets as $snippet) {
            $snippet->setRemoved(true);
            $snippet->setUpdatedAt(new DateTime());
            $this->entityManager->persist($snippet);
            $removed[] = $snippet;
        }
        $this->entityManager->flush();

        return $removed;
    }

    /**
     * @param string $templatePath
     * @return string[]
     * @throws Exception
     */
    public function getMetaDataFromTemplate(string $templatePath): array
    {
        if (! file_exists($templatePath)) {
            throw new Exception('Snippet file not found! ' . $templatePath);
        }

        $document = new DOMDocument('1.0');
        $document->load($templatePath);
        // @codingStandardsIgnoreStart
        return [
            "id" => $document->documentElement->getAttribute("id"),
            "name" => $document->documentElement->getAttribute("name"),
            "groups" => $document->documentElement->hasAttribute("groups") ?
                $document->documentElement->getAttribute("groups") : null,
            "form" => $document->documentElement->hasAttribute("form") &&
            $document->documentElement->getAttribute("form") === "false" ? false
                : true,
            "file" => realpath($templatePath),
            "mtime" => filemtime($templatePath)
        ];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @param Snippet $snippet
     * @throws Exception
     */
    public function forceSnippetUpdate(Snippet $snippet): void
    {
        $repo = $this->entityManager->getRepository(SiteContent::class);
        /** @var SiteContent[] $siteContents */
        $siteContents = $repo->findBy(['snippet' => $snippet]);
        foreach ($siteContents as $siteContent) {
            if ($siteContent->getTemplate() !== $snippet->getTemplate()) {
                $siteContentHistory = new SiteContentHistory($siteContent, SiteContentHistory::ACTION_TEMPLATE_UPDATED);
                $this->entityManager->persist($siteContentHistory);
                $this->entityManager->flush();
                $siteContent->setTemplate($snippet->getTemplate());
                $siteContent->setForm($snippet->isForm());
                $this->entityManager->persist($siteContent);
                $this->entityManager->flush();
            }
        }
    }
}
