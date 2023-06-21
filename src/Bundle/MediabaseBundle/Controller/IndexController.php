<?php

namespace Trollfjord\Bundle\MediaBaseBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Trollfjord\Bundle\MediaBaseBundle\Form\Folder;
use Trollfjord\Bundle\MediaBaseBundle\Repository\MediaRepository;
use Trollfjord\Bundle\MediaBaseBundle\Service\MediaService;
use Trollfjord\Core\Controller\AbstractController;
use Trollfjord\Bundle\MediaBaseBundle\Entity\Media;
use Trollfjord\Bundle\MediaBaseBundle\Entity\MediaType;

/**
 * Class IndexController
 *
 * @author Tim Wettstein <wettstein@helliwood.com>
 *
 * @package Trollfjord\Bundle\MediaBaseBundle\Controller
 *
 * @Route("/MediaBase", name="media_base_")
 */
class IndexController extends AbstractController
{

    /**
     * @Route("/{parent<\d+>?}",
     *     name="home",
     *     defaults={"parent": null}
     * )
     *
     * @return Response
     */
    public function index(Request $request, MediaService $mediaService)
    {
        $user = $this->getUser();
        $parentId = $request->attributes->get("parent");

        $orderBy = $this->getOrderBy('name', $request);
        $order = $this->getOrder('ASC', $request);

        if (!is_null($parentId)) {
            $parent = $mediaService->getMediaById($parentId);
        } else {
            $parent = null;
        }
        try {
            $media = $mediaService->fetchAll($parentId, null, $orderBy, $order);
        } catch (\Exception $e) {
            throw $e;
        }

        if ($request->isXmlHttpRequest()) {
            /** @var MediaRepository $mr */
            $mr = $this->getDoctrine()->getRepository(Media::class);
            if ($request->isMethod(Request::METHOD_POST)) {
                $em = $this->getDoctrine()->getManager();
                switch ($request->get('action', null)) {
                    case "delete":
                        $mediaService->deleteMedia(
                            $mediaService->getMediaById($request->get("id"))
                        );
                        break;
                    case "move":
                        $mediaService->move(
                            $request->get("id"),
                            $request->get("to")
                        );
                        break;
                    case "get":
                        if ($media = $mediaService->getMediaById($request->get("id"))) {
                            return new JsonResponse($media);
                        } else {
                            return new JsonResponse(false);
                        }
                        break;
                }
            }
            $filetype = $request->query->get('filetype', null);
                $resArray = $mr->find4Ajax(
                    $parent,
                    $request->query->get('sort', 'name'),
                    $request->query->getBoolean('sortDesc', false),
                    $request->query->getInt('page', 1),
                    $request->query->getInt('size', 1),
                    $request->query->get('filter', null),
                    ($filetype) ? $mediaService->getFiletypes()[$filetype] : null
                );

            $resArray["icons"] = $mediaService->getIcons();
            return new JsonResponse($resArray);

        }

        return $this->render('@MediaBase/index/index.html.twig', [
            "medien" => $media
        ]);
    }

    /**
     * @Route("/Details/{id<\d+>?}",
     *     name="details"
     * )
     *
     * @return Response
     */
    public function details(Media $media, Request $request, MediaService $mediaService)
    {
        return $this->render('@MediaBase/index/details.html.twig', [
            "media" => $media,
            'icons' => $mediaService->getIcons()
        ]);
    }

    /**
     * @Route("/DetailsIconView/{id<\d+>?}",
     *     name="details_icon_view"
     * )
     *
     * @return Response
     */
    public function detailsIconView(Media $media, Request $request, MediaService $mediaService)
    {
        return $this->render('@MediaBase/index/details-icon-view.html.twig', [
            "media" => $media,
            'icons' => $mediaService->getIcons()
        ]);
    }

    /**
     * @Route("/EditFolder/{id<\d+>?}/{parent<\d+>?}",
     *     name="folder",
     *     defaults={"id": null, "parent": null}
     * )
     *
     * @Route("/AddFolder/{parent<\d+>?}",
     *     name="addFolder",
     *     defaults={"parent": null}
     * )
     *
     * @return Response
     */
    public function editFolderAction(Request $request, MediaService $mediaService)
    {
        $folderId = $request->attributes->get("id");
        $parentId = $request->attributes->get("parent");

        /*
        if (is_null($parentId) && ! $this->identity()->isInRole("Admin")) {
            $this->flashMessenger()->addErrorMessage("Ordneroptionen nicht erlaubt!");
            return $this->redirectToRoute('media_base_home');
        }*/

        if (!is_null($parentId) && !$mediaService->checkPermissionsForFolder($parentId)) {
            $this->addFlash('danger', "Keine Berechtigung für diesen Ordner!");
            return $this->redirectToRoute('media_base_home');
        }

        if ($folderId) {
            $editMode = true;
            /** @var  Media $folder */
            $folder = $mediaService->getMediaById($folderId);
            $realParentId = (($folder && $folder->getParent()) ? $folder->getParent()->getId() : null);
            if ($parentId != $realParentId) {
                $this->addFlash('danger', 'Manipulation erkannt!');
                return $this->redirectToRoute('media_base_home');
            }
            if (!$folder || !$folder->getIsDirectory()) {
                $this->addFlash('danger', 'Oberordner nicht gefunden!');
                return $this->redirectToRoute('media_base_home');
            }
        } else {
            $editMode = false;
            $folder = new \Trollfjord\Bundle\MediaBaseBundle\Entity\Media();
            $folder->setIsDirectory(true);
            $folder->setOwner($this->getUser());
            if (!is_null($parentId)) {
                /** @var Media $parent */
                $parent = $mediaService->getMediaById($parentId);
                if (!$parent || !$parent->getIsDirectory()) {
                    $this->addFlash('danger', 'Oberordner nicht gefunden!');
                    return $this->redirectToRoute('media_base_home');
                }
                $folder->setParent($parent);
            }
        }

        $form = $this->createForm(Folder::class, $folder);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $folder = $form->getData();
            $mediaService->saveFolder($folder);

            $this->addFlash('success', 'Der Ordner wurde erfolgreich gespeichert!');
            return $this->render('@MediaBase/close-frame.html.twig');
            //return $this->redirectToRoute('media_base_home', ['parent' => ($folder->getParent())? $folder->getParent()->getId():null ]);
        }

        return $this->render('@MediaBase/index/edit-folder.html.twig', [
            'editMode' => $editMode,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/Delete/{id<\d+>?}", name="delete", defaults={"id": null})
     */
    public function deleteAction(Request $request, MediaService $mediaService): Response
    {
        $id = $request->attributes->get("id");
        $isUsed = $mediaService->checkMediaUsages($id);
        $media = $mediaService->getMediaById($id);

        if ($media && $media->getParent()) {
            $parentId = $media->getParent()->getId();
        } else {
            $parentId = null;
        }
        if ($isUsed) {
            if ($media) {
                try {
                    $mediaService->deleteMedia($media);
                    $this->addFlash('success', 'Eintrag erfolgreich gelöscht!');
                } catch (\Exception $e) {
                    $this->addFlash('danger', 'Beim löschen ist ein Fehler aufgetreten!');
                }
            }
        } else {
            $this->addFlash('danger', 'Media kann nicht gelöscht werden !!!');
        }

        return $this->redirectToRoute('media_base_home', ["parent" => $parentId]);
    }

    /**
     * @Route("/InfoFolder/{id<\d+>?}",
     *     name="folder_infos",
     *     defaults={"id": null}
     * )
     *
     * @param int|null $id
     * @param MediaService $mediaService
     * @return Response
     */
    public function getFolderInfo(?int $id, MediaService $mediaService): Response
    {
        $folder = null;
        $folderStructure = [];
        if ($id) {

            /** @var  Media $folder */
            $folder = $mediaService->getMediaById($id);
            $folderStructure = $mediaService->getFolderDetails($folder);


        }
        return new JsonResponse($folderStructure);
    }

    /**
     * @Route("/InfoRootFolder",
     *     name="folder_root_infos",
     * )
     *
     * @param MediaService $mediaService
     * @return Response
     */
    public function getRootFolderInfo(MediaService $mediaService): Response
    {
        $folder = null;

        $folder = $mediaService->getRootFolderInfo();

        return new JsonResponse($folder);

    }

    /**
     * @Route("/search/{word}",
     *     defaults={"word": null},
     *     name="search_media",
     * )
     *
     * @param string|null $word
     * @param MediaService $mediaService
     * @return Response
     */
    public function searchMedia(?string $word, MediaService $mediaService, Request  $request): Response
    {
        $files = [];
        $filetype = $request->query->get('filetype', null);
        $files = $mediaService->searchForMedia($word,$filetype);

        return new JsonResponse($files);

    }

    /**
     * Gibt den Wert nach dem geordnet werden soll zurück
     *
     * @param string $default
     * @return string|null
     */
    public function getOrderBy($default = null, Request $request)
    {
        $key = $request->get('_route') . '_orderBy';

        if (($orderBy = $request->query->get('orderBy')) !== null) {
            //$this->userSettings()->set($key, $orderBy);
        } else {
            /*if($default !== null && !$this->userSettings()->has($key)) {
                $this->userSettings()->set($key, $default);
            }
            $orderBy = $this->userSettings()->get($key);*/
        }

        return $orderBy;
    }

    /**
     * Gibt die Sortierrichtung wieder
     *
     * @param string $default
     * @return string|null
     */
    public function getOrder($default = null, Request $request)
    {
        $key = $request->get('_route') . '_order';

        if (($order = $request->query->get('order', null)) !== null) {
            //$this->userSettings()->set($key, $order);
        } else {
            /*if($default !== null && !$this->userSettings()->has($key)) {
                $this->userSettings()->set($key, $default);
            }
            $order = $this->userSettings()->get($key);*/
        }

        return $order;
    }

    /**
     * @Route("/getId",
     *     name="get_id",
     * )
     *
     */
    public function getUserId(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $session = $request->getSession();
            $data = unserialize($session->get('_security_main'));
            return new JsonResponse(['id' => 'mediabase_' . $data->getUser()->getId()]);
        }

    }

}
