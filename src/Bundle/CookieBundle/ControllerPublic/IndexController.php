<?php

namespace Trollfjord\Bundle\CookieBundle\ControllerPublic;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Trollfjord\Bundle\CookieBundle\Entity\CookieMain;
use Trollfjord\Core\Controller\AbstractPublicController;
use Symfony\Component\Routing\Annotation\Route;


/**
 *
 * Class IndexController
 *
 * @author  Juan Mayoral <mayoral@helliwood.com>
 *
 * @package Trollfjord\Bundle\CookieBundle\ControllerPublic
 *
 * @Route("/Cookie", name="cookie_frontend_")
 */
class IndexController extends AbstractPublicController
{


    /**
     * @Route("/get/{id}", name="get")
     * @param int $id
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function getCookie(int $id, Request $request, EntityManagerInterface $entityManager)
    {
        if ($request->isXmlHttpRequest()) {
            $record = $entityManager->getRepository(CookieMain::class)->find($id);
            return new JsonResponse($record);
        } else {
            return $this->redirect('/');
        }
    }


}
