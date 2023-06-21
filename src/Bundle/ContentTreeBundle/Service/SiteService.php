<?php

namespace Trollfjord\Bundle\ContentTreeBundle\Service;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use ReflectionException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Trollfjord\Bundle\ContentTreeBundle\Entity\Site;
use Trollfjord\Bundle\ContentTreeBundle\Entity\SiteContent;
use Trollfjord\Bundle\ContentTreeBundle\Entity\SiteContentData;
use Trollfjord\Bundle\ContentTreeBundle\Entity\SiteContentHistory;
use Trollfjord\Bundle\ContentTreeBundle\Entity\SiteHistory;
use Trollfjord\Bundle\ContentTreeBundle\Entity\SitePublished;
use Trollfjord\Bundle\ContentTreeBundle\Repository\SiteContentRepository;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\ElementNotFoundException;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\Exception as TemplateEngineException;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\TemplateEngine;
use function array_merge;
use function array_reverse;
use function count;
use function file_exists;
use function in_array;
use function is_array;
use function is_null;
use function is_numeric;
use function json_encode;
use function opendir;
use function readdir;
use function strlen;
use function strpos;
use function substr;
use function unlink;

/**
 * Class SiteService
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\Service
 */
class SiteService
{
    /**
     * @var string
     */
    protected string $cacheDir;

    /**
     * @var EntityManagerInterface
     */
    protected EntityManagerInterface $entityManager;

    /**
     * @var Security
     */
    protected Security $security;

    /**
     * @var TemplateEngine
     */
    protected TemplateEngine $templateEngine;

    /**
     * @var SnippetService
     */
    protected SnippetService $snippetService;

    /**
     * @var RouterInterface
     */
    protected RouterInterface $router;

    /**
     * SiteService constructor.
     * @param string                 $cacheDir
     * @param Security               $security
     * @param EntityManagerInterface $entityManager
     * @param TemplateEngine         $templateEngine
     * @param SnippetService         $snippetService
     * @param RouterInterface        $router
     */
    public function __construct(
        string                 $cacheDir,
        Security               $security,
        EntityManagerInterface $entityManager,
        TemplateEngine         $templateEngine,
        SnippetService         $snippetService,
        RouterInterface        $router
    ) {
        $this->cacheDir = $cacheDir;
        $this->security = $security;
        $this->entityManager = $entityManager;
        $this->templateEngine = $templateEngine;
        $this->snippetService = $snippetService;
        $this->router = $router;
    }

    /**
     * @param int $siteId
     * @return Site|null
     */
    public function getSite(int $siteId): ?Site
    {
        $sr = $this->entityManager->getRepository(Site::class);
        return $sr->find($siteId);
    }

    /**
     * @return array|Site[]
     */
    public function getSites(): array
    {
        $sr = $this->entityManager->getRepository(Site::class);
        return $sr->findBy(['deleted' => false]);
    }

    /**
     * @return array|SitePublished[]
     */
    public function getPublishedSites(): array
    {
        $sr = $this->entityManager->getRepository(SitePublished::class);
        return $sr->findAll();
    }

    /**
     * @param Site $site
     * @return Site
     * @throws Exception
     */
    public function addSite(Site $site): Site
    {
        $sr = $this->entityManager->getRepository(Site::class);
        /** @var Site[] $reorderSites */
        $reorderSites = $sr->findBy(['parent' => $site->getParent(), 'deleted' => false], ['position' => 'ASC']);
        $this->entityManager->beginTransaction();
        try {
            foreach ($reorderSites as $reorderSite) {
                if ($reorderSite->getPosition() >= $site->getPosition()) {
                    $reorderSite->setPosition($reorderSite->getPosition() + 1);
                    $this->entityManager->persist($reorderSite);
                }
            }
            $this->entityManager->persist($site);
            $siteHistory = new SiteHistory($site, SiteHistory::ACTION_ADDED, $this->security->getUser());
            $this->entityManager->persist($siteHistory);
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        $this->clearRouteCache();
        return $site;
    }

    /**
     * @param Site $site
     * @return Site
     * @throws Exception
     */
    public function saveSite(Site $site): Site
    {
        $unitOfWork = $this->entityManager->getUnitOfWork();
        $unitOfWork->computeChangeSets();
        $changes = $unitOfWork->getEntityChangeSet($site);
        $sr = $this->entityManager->getRepository(Site::class);
        $this->entityManager->beginTransaction();
        try {
            $this->entityManager->persist($site);
            $this->entityManager->flush();
            $hasNewParent = isset($changes['parent']);
            $hasNewPosition = isset($changes['position']);
            $isMovingDown = isset($changes['position']) && $changes['position'][0] < $changes['position'][1];
            $isMovingUp = isset($changes['position']) && $changes['position'][0] > $changes['position'][1];

            // Alten parent neu sortieren
            if ($hasNewParent) {
                /** @var Site[] $reorderSites */
                $reorderSites = $sr->findBy(['parent' => $changes['parent'][0], 'deleted' => false], ['position' => 'ASC']);
                $pos = 1;
                foreach ($reorderSites as $reorderSite) {
                    $reorderSite->setPosition($pos);
                    $pos++;
                }
            }
            if ($hasNewPosition || $hasNewParent) {
                if (! $hasNewParent && $isMovingDown) {
                    $site->setPosition($site->getPosition() - 1);
                }
                $reorderSites = $sr->findBy(['parent' => $site->getParent(), 'deleted' => false], ['position' => 'ASC']);
                $pos = 1;
                foreach ($reorderSites as $reorderSite) {
                    if ($reorderSite->getId() !== $site->getId()) {
                        if ($reorderSite->getPosition() === $site->getPosition()) {
                            if ($hasNewParent || $isMovingUp) {
                                $pos++;
                                $reorderSite->setPosition($pos);
                            } else {
                                $reorderSite->setPosition($reorderSite->getPosition() - 1);
                                $pos++;
                            }
                        } else {
                            $reorderSite->setPosition($pos);
                        }
                        $pos++;
                    }
                }
            }
            if (count($changes) > 0) {
                $siteHistory = new SiteHistory($site, SiteHistory::ACTION_META_UPDATED, $this->security->getUser());
                $siteHistory->setChanges($changes);
                $this->entityManager->persist($siteHistory);
            }
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
        $this->clearRouteCache();
        return $site;
    }

    /**
     * @param Site $site
     * @throws Exception
     */
    public function deleteSite(Site $site): void
    {
        $this->entityManager->beginTransaction();
        try {
            $site->setDeleted(true);
            $this->entityManager->flush();
            if ($site->getParent()) {
                $site->getParent()->reorderChildren();
            } else {
                $sr = $this->entityManager->getRepository(Site::class);
                /** @var Site[] $children */
                $children = $sr->findBy(['parent' => null, 'deleted' => false], ['position' => 'ASC']);
                $newPosition = 1;
                foreach ($children as $child) {
                    $child->setPosition($newPosition);
                    $newPosition++;
                }
            }
            $siteHistory = new SiteHistory($site, SiteHistory::ACTION_DELETED, $this->security->getUser());
            $this->entityManager->persist($siteHistory);
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    /**
     * @param Site $site
     * @throws Exception
     */
    public function undeleteSite(Site $site): void
    {
        $this->entityManager->beginTransaction();
        try {
            $site->setDeleted(false);
            $this->entityManager->flush();
            if ($site->getParent()) {
                $site->getParent()->reorderChildren();
            } else {
                $sr = $this->entityManager->getRepository(Site::class);
                /** @var Site[] $children */
                $children = $sr->findBy(['parent' => null, 'deleted' => false], ['position' => 'ASC']);
                $newPosition = 1;
                foreach ($children as $child) {
                    $child->setPosition($newPosition);
                    $newPosition++;
                }
            }
            $siteHistory = $this->entityManager->getRepository(SiteHistory::class)->findOneBy(['site' => $site, 'action' => SiteHistory::ACTION_DELETED]);
            $this->entityManager->remove($siteHistory);
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    /**
     * @param Site $site
     * @param bool $forceDisablePublish
     * @throws ReflectionException
     * @throws Exception
     */
    public function publishSite(Site $site, bool $forceDisablePublish = false): void
    {
        if ($site->isDeleted()) {
            foreach ($site->getChildren() as $child) {
                $child->setDeleted(true);
                $this->publishSite($child);
            }
            if (! is_null($site->getPublishedSite())) {
                $this->reorderPublishedSitesByParent($site->getPublishedSite()->getParent());
                $this->entityManager->remove($site->getPublishedSite());
                $this->entityManager->flush();
            }
            return;
        }

        $publishedSite = new SitePublished();
        if (! is_null($site->getPublishedSite())) {
            $publishedSite = $site->getPublishedSite();
            $publishedSite->setPublishDate(new DateTime());
        }
        if (! is_null($site->getParent()) && is_null($site->getParent()->getPublishedSite())) {
            $this->publishSite($site->getParent(), true);
        }
        $publishedSite->setSite($site);
        $publishedSite->setParent($site->getParent() ? $site->getParent()->getPublishedSite() : null);
        $publishedSite->setPosition($site->getPosition());
        $publishedSite->setName($site->getName());
        $publishedSite->setSlug($site->getSlug());
        $publishedSite->setAlternativeRoute($site->getAlternativeRoute());
        $publishedSite->setSocialMediaImage($site->getSocialMediaImage());
        $publishedSite->setDcTitle($site->getDcTitle());
        $publishedSite->setDcCreator($site->getDcCreator());
        $publishedSite->setDcDate($site->getDcDate());
        $publishedSite->setDcKeywords($site->getDcKeywords());
        $publishedSite->setDcDescription($site->getDcDescription());
        $publishedSite->setMenuEntry($site->isMenuEntry());
        if ($forceDisablePublish) {
            $publishedSite->setPublished(false);
        } else {
            $publishedSite->setData($site->getSiteContentAsArray());
            $publishedSite->setPublished($site->isPublished());
            $siteHistory = new SiteHistory($site, SiteHistory::ACTION_PUBLISHED, $this->security->getUser());
            $this->entityManager->persist($siteHistory);
        }
        $this->entityManager->persist($publishedSite);
        $this->entityManager->flush();
        $this->reorderPublishedSitesByParent($publishedSite->getParent());

        $this->clearRouteCache();
    }

    /**
     * @param SitePublished|null $parent
     */
    public function reorderPublishedSitesByParent(?SitePublished $parent): void
    {
        $spr = $this->entityManager->getRepository(SitePublished::class);
        /** @var Site[] $reorderSites */
        $reorderSites = $spr->findBySitePositionAndParent($parent);
        $pos = 1;
        foreach ($reorderSites as $reorderSite) {
            $reorderSite->setPosition($pos);
            $pos++;
        }
        $this->entityManager->flush();
    }

    /**
     * @param string $route
     * @return Site|null
     */
    public function getSiteByRoute(string $route): ?Site
    {
        $site = null;
        $siteId = Site::getIdByRouteName($route);
        if (! is_null($siteId)) {
            $site = $this->entityManager->find(Site::class, $siteId);
        }
        // Add functionality for Homepage
        if (is_null($site)) {
            $site = $this->entityManager->getRepository(Site::class)->findAll()[0];
        }
        return $site;
    }

    /**
     * @param string $route
     * @return SitePublished|null
     */
    public function getSitePublishedByRoute(string $route): ?SitePublished
    {
        $site = null;
        $siteId = Site::getIdByRouteName($route);
        if (! is_null($siteId)) {
            $site = $this->entityManager->getRepository(SitePublished::class)->findOneBy(['site_id' => $siteId]);
        }
        return $site;
    }

    /**
     * @return string[]
     */
    public function getContentTree(): array
    {
        $sr = $this->entityManager->getRepository(Site::class);
        /** @var Site[] $allSites */
        $allSites = $sr->getSites4ContentTree();

        $getSitesByParent = function (?Site $parent = null) use ($allSites) {
            $sites = [];
            foreach ($allSites as $site) {
                if ($site->getParent() === $parent) {
                    $sites[] = $site;
                }
            }
            return $sites;
        };

        $getTree = function (?Site $parent = null) use (&$getTree, $getSitesByParent): array {
            $ct = [];
            /** @var Site[] $sites */
            $sites = $getSitesByParent($parent);
            foreach ($sites as $site) {
                try {
                    $url = $this->router->generate($site->getRouteName());
                } catch (Exception $e) {
                    $url = null;
                }
                $ct[] = array_merge($site->toArray(), [
                    'children' => $getTree($site),
                    'url' => $url,
                    'previewPath' => $this->router->generate('content_tree_site_preview', ['id' => $site->getId()]),
                    'editPath' => $this->router->generate('content_tree_site_edit', ['id' => $site->getId()]),
                    'deletePath' => $this->router->generate('content_tree_site_delete', ['id' => $site->getId()]),
                    'undeletePath' => $this->router->generate('content_tree_site_undelete', ['id' => $site->getId()]),
                    'metasPath' => $this->router->generate('content_tree_site_metas_form', ['id' => $site->getId()]),
                    'historyPath' => $this->router->generate('content_tree_site_history', ['id' => $site->getId()]),
                    'publishPath' => $this->router->generate('content_tree_site_publish', ['id' => $site->getId()])
                ]);
            }
            return $ct;
        };

        return $getTree();
    }

    /**
     * @return SitePublished|null
     */
    public function getHomepage(): ?SitePublished
    {
        $spr = $this->entityManager->getRepository(SitePublished::class);
        /** @var SitePublished $allSites */
        return $spr->findOneBy(['published' => true], ['parent' => 'ASC', 'position' => 'ASC']);
    }

    /**
     * @return string[]
     */
    public function getContentTreePublished(): array
    {
        $spr = $this->entityManager->getRepository(SitePublished::class);
        /** @var SitePublished[] $allSites */
        $allSites = $spr->findBy(['published' => true], ['parent' => 'ASC', 'position' => 'ASC']);

        $getSitesByParent = function (?SitePublished $parent = null) use ($allSites) {
            $sites = [];
            foreach ($allSites as $site) {
                if ($site->getParent() === $parent) {
                    $sites[] = $site;
                }
            }
            return $sites;
        };

        $getTree = function (?SitePublished $parent = null) use (&$getTree, $getSitesByParent): array {
            $ct = [];
            /** @var Site[] $sites */
            $sites = $getSitesByParent($parent);
            foreach ($sites as $site) {
                try {
                    $url = $this->router->generate($site->getRouteName());
                } catch (Exception $e) {
                    $url = null;
                }
                $ct[] = array_merge($site->toArray(), [
                    'children' => $getTree($site),
                    'url' => $url,
                    'previewPath' => $this->router->generate('content_tree_site_preview', ['id' => $site->getId()]),
                    'editPath' => $this->router->generate('content_tree_site_edit', ['id' => $site->getId()]),
                    'deletePath' => $this->router->generate('content_tree_site_delete', ['id' => $site->getId()]),
                    'metasPath' => $this->router->generate('content_tree_site_metas_form', ['id' => $site->getId()]),
                    'historyPath' => $this->router->generate('content_tree_site_history', ['id' => $site->getId()]),
                    'publishPath' => $this->router->generate('content_tree_site_publish', ['id' => $site->getId()])
                ]);
            }
            return $ct;
        };

        return $getTree();
    }

    /**
     * @return Site[]
     */
    public function getContentTree4Select(): array
    {
        $sr = $this->entityManager->getRepository(Site::class);
        /** @var Site[] $allSites */
        $allSites = $sr->findBy(['deleted' => false], ['parent' => 'ASC', 'position' => 'ASC']);

        $getSitesByParent = function (?Site $parent = null) use ($allSites) {
            $sites = [];
            foreach ($allSites as $site) {
                if ($site->getParent() === $parent) {
                    $sites[] = $site;
                }
            }
            return $sites;
        };

        $getTree = function (?Site $parent = null, int $depth = 0) use (&$getTree, $getSitesByParent): array {
            $ct = [];
            /** @var Site[] $sites */
            $sites = $getSitesByParent($parent);
            foreach ($sites as $site) {
                $ct[] = $site;
                $ct = array_merge($ct, $getTree($site, $depth + 1));
            }
            return $ct;
        };

        return $getTree();
    }

    /**
     * @param Site   $site
     * @param string $renderMode
     * @return string|null
     * @throws ElementNotFoundException
     * @throws TemplateEngineException
     */
    public function renderSiteContents(Site $site, string $renderMode = TemplateEngine::RENDER_MODE_PUBLIC): ?string
    {
        $content = null;
        if ($site->getContentByParentNull()->count() > 0) {
            foreach ($site->getContentByParentNull() as $siteContent) {
                $content .= $this->templateEngine->renderSiteContent($siteContent, $renderMode);
            }
        }

        if (count($this->templateEngine->getPreRenderer()) > 0) {
            foreach ($this->templateEngine->getPreRenderer() as $preRenderer) {
                $content = $preRenderer->preRenderContent($renderMode, $content);
            }
        }
        return $content;
    }

    /**
     * @param SitePublished $sitePublished
     * @param string        $renderMode
     * @return string|null
     * @throws ElementNotFoundException
     * @throws TemplateEngineException
     */
    public function renderSiteContentsBySitePublished(SitePublished $sitePublished, string $renderMode = TemplateEngine::RENDER_MODE_PUBLIC): ?string
    {
        $content = null;
        if (count($sitePublished->getData()) > 0) {
            foreach ($sitePublished->getData() as $siteContentData) {
                $siteContent = $this->createSiteContentByJson($sitePublished, $siteContentData);
                $content .= $this->templateEngine->renderSiteContent($siteContent, $renderMode);
            }
        }

        if (count($this->templateEngine->getPreRenderer()) > 0) {
            foreach ($this->templateEngine->getPreRenderer() as $preRenderer) {
                $content = $preRenderer->preRenderContent($renderMode, $content);
            }
        }
        return $content;
    }

    /**
     * Fake SiteContent-Structure from Json-Data for TemplateEngine
     * @param SitePublished    $sitePublished
     * @param string[]         $siteContentData
     * @param SiteContent|null $parent
     * @return SiteContent
     */
    protected function createSiteContentByJson(SitePublished $sitePublished, array $siteContentData, ?SiteContent $parent = null): SiteContent
    {
        $siteContent = new SiteContent();
        $siteContent->setId($siteContentData['id']);
        $siteContent->setPosition($siteContentData['position']);
        $siteContent->setTemplate($siteContentData['template']);
        $siteContent->setParent($parent);
        $siteContent->setSite($sitePublished->getSite());
        $siteContent->setArea($siteContentData['area']);
        $siteContent->setData(new ArrayCollection($this->createSiteContentDataByJson($siteContent, $siteContentData['data'])));
        if (isset($siteContentData['children'])) {
            $children = [];
            foreach ($siteContentData['children'] as $area => $items) {
                foreach ($items as $child) {
                    $children[] = $this->createSiteContentByJson($sitePublished, $child, $siteContent);
                }
            }
            $siteContent->setChildren(new ArrayCollection($children));
        }

        return $siteContent;
    }

    /**
     * @param SiteContent          $siteContent
     * @param string[]             $data
     * @param SiteContentData|null $parent
     * @return SiteContentData[]
     */
    protected function createSiteContentDataByJson(SiteContent $siteContent, array $data, ?SiteContentData $parent = null): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $siteContentData = new SiteContentData();
            $siteContentData->setSiteContent($siteContent);
            $siteContentData->setParent($parent);
            $siteContentData->setKey($key);
            if (is_array($value)) {
                $result = array_merge($result, $this->createSiteContentDataByJson($siteContent, $value, $siteContentData));
            } else {
                $siteContentData->setValue($value);
            }
            $result[] = $siteContentData;
        }
        return $result;
    }

    /**
     * @param Site             $site
     * @param SiteContent|null $siteContent
     * @param string|null      $area
     * @param string           $renderMode
     * @return string|null
     * @throws ElementNotFoundException
     * @throws TemplateEngineException
     */
    public function renderSiteContentsArea(Site $site, ?SiteContent $siteContent, ?string $area, string $renderMode = TemplateEngine::RENDER_MODE_PUBLIC): ?string
    {
        if (is_null($siteContent)) {
            $siteContens = $site->getContentByParentNull();
        } else {
            $siteContens = $siteContent->getChildrenByArea($area);
        }
        $content = null;
        foreach ($siteContens as $siteContentChild) {
            $content .= $this->templateEngine->renderSiteContent($siteContentChild, $renderMode);
        }
        return $content;
    }

    /**
     * @param SiteContent $siteContent
     * @return FormInterface
     * @throws ElementNotFoundException
     * @throws TemplateEngineException
     */
    public function getForm(SiteContent $siteContent): FormInterface
    {
        return $this->templateEngine->getSiteContentForm($siteContent);
    }

    /**
     * Call AFTER getForm!!!
     * @return string[]
     */
    public function getFormThemeTemplates(): array
    {
        return $this->templateEngine->getFormThemeTemplates();
    }

    /**
     * @param SiteContent $siteContent
     */
    public function saveSiteContent(SiteContent $siteContent): void
    {
        $this->entityManager->persist($siteContent);
        $this->entityManager->flush();
    }

    /**
     * @param int $siteContentId
     * @return SiteContent|null
     */
    public function getSiteContent(int $siteContentId): ?SiteContent
    {
        $sr = $this->entityManager->getRepository(SiteContent::class);
        return $sr->find($siteContentId);
    }

    /**
     * @param int              $snippetId
     * @param Site             $site
     * @param int|null         $after
     * @param SiteContent|null $parent
     * @param string|null      $area
     * @return SiteContent
     * @throws Exception
     */
    public function addSiteContent(int $snippetId, Site $site, ?int $after = null, ?SiteContent $parent = null, ?string $area = null): SiteContent
    {
        $snippet = $this->snippetService->getSnippet($snippetId);
        if (! $snippet) {
            throw new Exception('Snippet ' . $snippetId . ' nicht gefunden!');
        }
        $this->entityManager->beginTransaction();
        try {
            $siteContent = new SiteContent();
            $siteContent->setName($snippet->getName());
            $siteContent->setSite($site);
            $siteContent->setParent($parent);
            $siteContent->setSnippet($snippet);
            $siteContent->setForm($snippet->isForm());
            $siteContent->setTemplate($snippet->getTemplate());
            $siteContent->setArea($area);
            if ($after) {
                $afterFound = false;
                foreach ($site->getContent($parent, $area) as $child) {
                    if ($afterFound) {
                        $child->setPosition($child->getPosition() + 1);
                    }
                    if ($child->getId() === $after) {
                        $afterFound = true;
                        $siteContent->setPosition($child->getPosition() + 1);
                    }
                }
                if (! $afterFound) {
                    throw new Exception('After Snippet ' . $after . ' not found!');
                }
            } else {
                $siteContent->setPosition($site->getContent($parent, $area)->count() + 1);
            }
            $this->entityManager->persist($siteContent);
            $this->entityManager->flush();
            $this->createHistory($siteContent, SiteContentHistory::ACTION_ADDED);
            $this->entityManager->commit();
        } catch (Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
        return $siteContent;
    }

    /**
     * @param SiteContent $siteContent
     * @throws Exception
     */
    public function deleteSiteContent(SiteContent $siteContent): void
    {
        $this->createHistory($siteContent, SiteContentHistory::ACTION_DELETED);
        if (is_null($siteContent->getParent())) {
            $site = $siteContent->getSite();
            $site->getContent()->removeElement($siteContent);
            $this->entityManager->remove($siteContent);
            $this->entityManager->flush();
            $site->reorderRootContent();
            $this->entityManager->flush();
        } else {
            $siteContent->getParent()->getChildren()->removeElement($siteContent);
            $this->entityManager->remove($siteContent);
            $this->entityManager->flush();
            $siteContent->getParent()->reorderChildrenByArea($siteContent->getArea());
            $this->entityManager->flush();
        }
    }

    /**
     * @param int              $siteContentCopyId
     * @param Site             $site
     * @param SiteContent|null $parent
     * @param string|null      $area
     * @throws Exception
     */
    public function pasteSiteContent(int $siteContentCopyId, Site $site, ?SiteContent $parent, ?string $area): void
    {
        $this->entityManager->beginTransaction();
        try {
            /** @var SiteContentRepository $scr */
            $siteContentCopy = clone $this->entityManager->find(SiteContent::class, $siteContentCopyId);
            $siteContentCopy->setSite($site);
            $siteContentCopy->setParent($parent);
            $siteContentCopy->setArea($area);
            $siteContentCopy->setPosition($parent ? $parent->getChildrenByArea($area)->count() + 1 : $site->getContentByParentNull()->count() + 1);
            $this->entityManager->persist($siteContentCopy);
            $this->entityManager->flush();
            $this->createHistory($siteContentCopy, SiteContentHistory::ACTION_ADDED);
            $this->entityManager->commit();
        } catch (Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    /**
     * @param SiteContent $siteContent
     * @param string[]    $data
     * @param string[]    $rawData
     * @throws Exception
     */
    public function saveSiteContentWithData(SiteContent $siteContent, array $data, array $rawData): void
    {
        $saveContentData = function (array $data, array $rawData, ?SiteContentData $parent = null) use (&$saveContentData, &$siteContent): void {
            $siteContentRepo = $this->entityManager->getRepository(SiteContentData::class);
            foreach ($data as $key => $value) {
                if ((! $this->startsWith($key, 'TE-') && ! $this->endsWith($key, '-TE'))
                    && isset($rawData[$key])) {
                    if (is_array($value)) {
                        $siteContentDataParent = $siteContentRepo->getOrCreate($siteContent, $key, $parent);
                        $siteContentDataParent->setValue(null);
                        $this->entityManager->persist($siteContentDataParent);
                        $saveContentData($value, $rawData[$key], $siteContentDataParent);
                    } else {
                        $siteContentData = $siteContentRepo->getOrCreate($siteContent, $key, $parent);
                        $siteContentData->setValue($value);
                        $this->entityManager->persist($siteContentData);
                    }
                }
            }
        };
        $this->entityManager->beginTransaction();
        try {
            $saveContentData($data, $rawData);
            $this->createHistory($siteContent, SiteContentHistory::ACTION_CONTENT_UPDATED);
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    /**
     * @param SiteContent $siteContent
     * @throws Exception
     */
    public function up(SiteContent $siteContent): void
    {
        $this->createHistory($siteContent, SiteContentHistory::ACTION_UPDATED);
        $scr = $this->entityManager->getRepository(SiteContent::class);
        $scr->up($siteContent);
    }

    /**
     * @param SiteContent $siteContent
     * @throws Exception
     */
    public function down(SiteContent $siteContent): void
    {
        $this->createHistory($siteContent, SiteContentHistory::ACTION_UPDATED);
        $scr = $this->entityManager->getRepository(SiteContent::class);
        $scr->down($siteContent);
    }

    /**
     * @param SiteContent $siteContent
     * @param string      $action
     * @param string      $keypath
     * @throws Exception
     */
    public function modify(SiteContent $siteContent, string $action, string $keypath): void
    {
        $item = $siteContent->getDataByKeyPath($keypath);
        if (! is_null($item)) {
            switch ($action) {
                case "remove":
                    $this->entityManager->beginTransaction();
                    try {
                        $parent = $item->getParent();
                        $siteContent->getData()->removeElement($item);
                        $parent->getChildren()->removeElement($item);
                        $this->entityManager->remove($item);
                        $this->entityManager->flush();
                        $newKey = 0;
                        foreach ($parent->getChildren() as $child) {
                            $child->setKey($newKey);
                            $this->entityManager->flush();
                            $newKey++;
                        }
                        $this->entityManager->commit();
                    } catch (Exception $e) {
                        $this->entityManager->rollback();
                        throw $e;
                    }
                    break;
                case "append":
                    $scd = new SiteContentData();
                    $scd->setKey(count($siteContent->getDataAsKeyValueArray($item)));
                    $scd->setParent($item);
                    $scd->setSiteContent($siteContent);
                    $scdChild = new SiteContentData("needed_for_new");
                    $scdChild->setParent($scd);
                    $scdChild->setSiteContent($siteContent);
                    break;
                case "addBefore":
                    $this->entityManager->beginTransaction();
                    try {
                        if (is_numeric($item->getKey())) {
                            $index = (int)$item->getKey();
                            // Rückwärts da sonst wieder ein index-Fehler kommt
                            foreach (array_reverse($item->getParent()->getChildren()->toArray()) as $data) {
                                if ((int)$data->getKey() >= $index) {
                                    $data->setKey((int)$data->getKey() + 1);
                                    $this->entityManager->flush();
                                }
                            }
                            $this->entityManager->flush();
                            $scd = new SiteContentData();
                            $scd->setKey($index);
                            $scd->setParent($item->getParent());
                            $scd->setSiteContent($siteContent);
                            $scdChild = new SiteContentData("needed_for_new");
                            $scdChild->setParent($scd);
                            $scdChild->setSiteContent($siteContent);
                            $this->entityManager->flush();
                        }
                        $this->entityManager->commit();
                    } catch (Exception $e) {
                        $this->entityManager->rollback();
                        throw $e;
                    }
                    break;
                case "up":
                    $this->entityManager->beginTransaction();
                    try {
                        if (is_numeric($item->getKey())) {
                            $index = (int)$item->getKey();
                            if ($index > 0) {
                                $item2 = $siteContent->getDataByKey($index - 1, $item->getParent());
                                $item2->setKey($index . "temp"); // Unique error workflow
                                $this->entityManager->flush();
                                $item->setKey($index - 1);
                                $this->entityManager->flush();
                                $item2->setKey($index);
                                $this->entityManager->flush();
                            }
                        }
                        $this->entityManager->commit();
                    } catch (Exception $e) {
                        $this->entityManager->rollback();
                        throw $e;
                    }
                    break;
                case "down":
                    $this->entityManager->beginTransaction();
                    try {
                        if (is_numeric($item->getKey())) {
                            $index = (int)$item->getKey();
                            if ($index < $item->getParent()->getChildren()->count() - 1) {
                                $item2 = $siteContent->getDataByKey($index + 1, $item->getParent());
                                $item2->setKey($index . "temp"); // Unique error workflow
                                $this->entityManager->flush();
                                $item->setKey($index + 1);
                                $this->entityManager->flush();
                                $item2->setKey($index);
                                $this->entityManager->flush();
                            }
                        }
                        $this->entityManager->commit();
                    } catch (Exception $e) {
                        $this->entityManager->rollback();
                        throw $e;
                    }
                    break;
            }
        }
    }

    /**
     * @param SiteContent $siteContent
     * @param string      $action
     * @throws Exception
     */
    public function createHistory(SiteContent $siteContent, string $action): void
    {
        $update = true;
        if (in_array($action, [SiteContentHistory::ACTION_ADDED, SiteContentHistory::ACTION_CONTENT_UPDATED])) {
            $schr = $this->entityManager->getRepository(SiteContentHistory::class);
            /** @var SiteContentHistory $lastSiteContentHistory */
            $lastSiteContentHistory = $schr->findOneBy(['siteContentId' => $siteContent->getId()], ['date' => 'DESC']);
            if ($lastSiteContentHistory && $lastSiteContentHistory->getData() === json_encode($siteContent->getDataAsKeyValueArray())) {
                $update = false;
            }
        }
        if ($update) {
            $siteContentHistory = new SiteHistory($siteContent->getSite(), SiteHistory::ACTION_CONTENT_CHANGED, $this->security->getUser());
            $this->entityManager->persist($siteContentHistory);
            $siteContentHistory = new SiteContentHistory($siteContent, $action, $this->security->getUser());
            $this->entityManager->persist($siteContentHistory);
            $this->entityManager->flush();
        }
    }

    public function clearRouteCache(): void
    {
        $routeCacheFilesBeginsWith = ['url_generating_routes', 'url_matching_routes'];
        $handle = opendir($this->cacheDir);
        if ($handle) {
            /* Das ist der korrekte Weg, ein Verzeichnis zu durchlaufen. */
            while (false !== ($entry = readdir($handle))) {
                foreach ($routeCacheFilesBeginsWith as $check) {
                    if (strpos($entry, $check) === 0) {
                        if (file_exists($this->cacheDir . '/' . $entry)) {
                            unlink($this->cacheDir . '/' . $entry);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param string $string
     * @param string $startString
     * @return bool
     */
    public function startsWith(string $string, string $startString): bool
    {
        $len = strlen($startString);
        return substr($string, 0, $len) === $startString;
    }

    /**
     * @param string $string
     * @param string $endString
     * @return bool
     */
    public function endsWith(string $string, string $endString): bool
    {
        $len = strlen($endString);
        if ($len === 0) {
            return true;
        }
        return substr($string, -$len) === $endString;
    }
}
