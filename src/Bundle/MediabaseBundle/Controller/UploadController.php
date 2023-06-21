<?php


namespace Trollfjord\Bundle\MediaBaseBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Trollfjord\Bundle\MediaBaseBundle\Entity\Media;
use Trollfjord\Bundle\MediaBaseBundle\Form\FileEdit;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Trollfjord\Bundle\MediaBaseBundle\Form\Upload;
use Trollfjord\Bundle\MediaBaseBundle\Repository\MediaRepository;
use Trollfjord\Bundle\MediaBaseBundle\Service\MediaService;
use Trollfjord\Core\Controller\AbstractController;

/**
 * Class UploadController
 * @package Trollfjord\Bundle\MediaBaseBundle\Controller
 * @Route("/MediaBase/Upload", name="media_base_")
 */
class UploadController extends AbstractController
{
    /**
     * @Route("/{parent<\d+>?}",
     *     name="upload",
     *     defaults={"parent": null}
     * )
     * @return Response
     */
    public function index(Request $request, MediaService $mediaService)
    {
        $parentId = $request->attributes->get("parent");

        if (! is_null($parentId)) {
            $parent = $mediaService->getMediaById($parentId);
            if (! $parent || ! $parent->getIsDirectory()) {
                $this->addFlash('danger', "Kein gültiger Ordner!");
                return $this->redirectToRoute("media_base_home", ["parent" => $parent]);
            }
            if (! $mediaService->checkPermissionsForFolder($parentId)) {
                $this->addFlash( 'danger', "Keine Berechtigung für diesen Ordner!");
                return $this->redirectToRoute("media_base_home", ["parent" => $parent]);
            }
        } else {
            $this->addFlash('danger',"Kein gültiger Ordner!");
            return $this->redirectToRoute("media_base_home");
        }

        $media = new Media();
        $form = $this->createForm(Upload::class, $media);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $mediaService->saveUploadedData($form->getData(), $parent);

            if (! empty($_POST['isAjax'])) {
                die("ajax ?? ");
            } else {
                // Fallback for non-JS clients
                return $this->render('@MediaBase/close-frame.html.twig');
                //return $this->redirectToRoute("media_base_home", ["parent" => $parent]);
            }
        }else {
            if (! empty($_POST['isAjax']) || $request->isXmlHttpRequest()) {
                die("ajax ?? ");
            }
        }

        return $this->render('@MediaBase/upload/index.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/EditFile/{id<\d+>?}/{parent<\d+>?}",
     *     name="edit",
     *     defaults={"id": null,"parent": null}
     * )
     * @return Response
     */
    public function edit(Request $request, MediaService $mediaService, EntityManagerInterface $em)
    {

        $parentId = $request->attributes->get("parent");
        $id = $request->attributes->get("id");
        $media = $mediaService->getMediaById($id);

        if(!$media) {
            $this->addFlash('danger',  "File not found!");
            return $this->redirectToRoute("media_base_home");
        }

        if (! is_null($parentId)) {
            $parent = $mediaService->getMediaById($parentId);
            if (! $parent || ! $parent->getIsDirectory()) {
                $this->addFlash('danger', "Kein gültiger Ordner!");
                return $this->redirectToRoute("media_base_home");
            }
        } else {
            $parent = $media->getParent();
        }

        $isMediaUsed = $mediaService->checkMediaUsages($id);

        $form = $this->createForm(FileEdit::class, $media);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
                /** @var Media $data */
                $data = $form->getData();

                if($data->getParent()) {
                    $parent = $data->getParent();
                    if (! $parent || ! $parent->getIsDirectory() || !$mediaService->checkPermissionsForFolder($data->getParent()->getId())) {
                        $this->addFlash('danger', "Kein gültiger Ordner!");
                        return $this->redirectToRoute("media_base_home");
                    }
                }
                if ($data->getFile()) {
                    $mimeType = $data->getFile()->getMimeType();
                    if ($mimeType == $media->getMimeType()
                        || (substr($mimeType,0,5) == substr($media->getMimeType(),0,5))
                        && substr($mimeType,0,5) == "image") {

                        $mediaService->updateUploadedData($data, $parent);
                        return $this->render('@MediaBase/close-frame.html.twig');
                        //return $this->redirectToRoute("media_base_home", ['parent' => $parent->getId()]);
                    } else {
                        $this->addFlash('danger',
                            "Die Datei " . $data->getFile()->getClientOriginalName() . " [" . $mimeType . "] muss vom selben Typ [" .
                            $media->getMimeType() . "] sein!");
                        return $this->redirect($request->getUri());
                    }
                } else {
                    $mediaService->move($data->getId(), $parent->getId());
                    $mediaService->updateUploadedData($data, $parent);
                    return $this->render('@MediaBase/close-frame.html.twig');
                    /*return $this->redirectToRoute("media_base_home", [
                        'parent' => $parent->getId(),
                        'show' => $request->attributes->get("show"),
                        'page' => $request->attributes->get("page")
                    ]);*/
                }
        }

        return $this->render('@MediaBase/upload/index.html.twig', [
            'form' => $form->createView()
        ]);
    }

}