<?php

namespace Trollfjord\Bundle\CookieBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Knp\Menu\MenuItem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Trollfjord\Bundle\CookieBundle\Entity\CookieItem;
use Trollfjord\Bundle\CookieBundle\Entity\CookieMain;
use Trollfjord\Bundle\CookieBundle\Form\CookieItemForm;
use Trollfjord\Bundle\CookieBundle\Form\CookieMainForm;
use Trollfjord\Bundle\CookieBundle\Service\DbTransactions;
use Trollfjord\Core\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;


/**
 * Class IndexController
 *
 * @author  Juan Mayoral <mayoral@helliwood.com>
 *
 * @package Trollfjord\Bundle\CookieBundle\Controller
 *
 * @Route("/Cookie", name="cookie_")
 */
class IndexController extends AbstractController
{
    /**
     * @Route("/", name="home")
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $appUrl = $this->generateUrl('cookie_home');
        return $this->render('@Cookie/index/index.html.twig', [
            'appUrl' => $appUrl,
        ]);
    }

    /**
     * @Route("/details/{id}", name="details")
     * @param int $id
     * @param Request $request
     * @param MenuItem $menu
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function details(int $id, Request $request, MenuItem $menu, EntityManagerInterface $entityManager)
    {
        $record = $entityManager->getRepository(CookieMain::class)->find($id);

        $menu['cookie_']->addChild('Cookie bearbeiten: ' . $record->getName(), [
            'route' => 'cookie_details',
            'routeParameters' => ['id' => $id],
        ]);
        $appUrl = $this->generateUrl('cookie_home');
        return $this->render('@Cookie/index/details.html.twig', [
            'appUrl' => $appUrl,
            'recordId' => $id,
        ]);
    }

    /**
     * @Route("/records", name="records")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function records(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isXmlHttpRequest()) {
            $repositoryCookieMain = $entityManager->getRepository(CookieMain::class);
            $cookies = $repositoryCookieMain->findAll();
            return new JsonResponse($cookies);
        } else {
            return $this->redirectToRoute('cookie_home');
        }
    }

    /**
     * @Route("/getCookies/{id}", name="get_cookies")
     * @param int $id
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function getCookies(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        //This function retrieves the data for the details view.
        if ($request->isXmlHttpRequest()) {
            $repositoryMain = $entityManager->getRepository(CookieMain::class);
            $parent = $repositoryMain->find($id);

            $data = [
             'parent' => $parent,
            ];
            return new JsonResponse($data);
        } else {
            return $this->redirectToRoute('cookie_home');
        }
    }

    /**
     * @Route("/new", name="new")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function new(Request $request, EntityManagerInterface $entityManager)
    {
        //This function create a new entry in the CookieMain entity.
        //This is going to show the form for saving a new word
        $cookieMain = new CookieMain();
        $form = $this->createForm(CookieMainForm::class, $cookieMain, []);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $cookie = $form->getData();
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($cookie);
            $entityManager->flush();
            $this->addFlash(
                'success',
                'Cookie erfolgreich gespeichert'
            );
            return $this->render('@Cookie/index/close-frame.html.twig', [
                'cookie' => $cookie,
            ]);
        }

        return $this->render('@Cookie/index/create-item-iframe.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/add/{id}", name="add_cookie")
     * @param int $id
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function addCookie(int $id, Request $request, EntityManagerInterface $entityManager)
    {
        if (null === $parent = $entityManager->getRepository(CookieMain::class)->find($id)) {
            throw $this->createNotFoundException('Parent nicht gefundet' . $id);
        }

        //this part is going to show the form to create a new item
        //In other words, to save a new entry in the CookieItem entity.
        $cookieItem = new CookieItem();
        $cookieItem->setCookieMain($parent);
        $form = $this->createForm(CookieItemForm::class, $cookieItem, []);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $cookie = $form->getData();

            $entityManager->persist($cookie);
            $entityManager->flush();
            $this->addFlash(
                'success',
                'Cookie erfolgreich gespeichert'
            );
            //return the item/cookie item to show the message in the backend
            return $this->render('@Cookie/index/close-frame.html.twig', [
                'cookie' => $cookie
            ]);
        }

        return $this->render('@Cookie/index/create-item-with-variation-iframe.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/edit/{id}", name="edit")
     * @param int $id
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function edit(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (null === $cookie = $entityManager->getRepository(CookieMain::class)->find($id)) {
            throw $this->createNotFoundException('Cookie nicht gefunden' . $id);
        }

        $editForm = $this->createForm(CookieMainForm::class, $cookie);

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {

            $entityManager->persist($cookie);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Cookie erfolgreich gespeichert'
            );
            return $this->render('@Cookie/index/close-frame.html.twig', [
            ]);

        }

        return $this->render('@Cookie/index/edit-cookie-iframe.html.twig', [
            'form' => $editForm->createView()
        ]);
    }

    /**
     * @Route("/editCookie/{id}", name="edit_cookie")
     * @param $id
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param DbTransactions $dbTransactions
     * @return Response
     */
    public function editCookieItem($id, Request $request, EntityManagerInterface $entityManager, DbTransactions $dbTransactions): Response
    {
        //this function is in charge to edit the entry in the CookieItem entity
        if (null === $cookie = $entityManager->getRepository(CookieItem::class)->find($id)) {
            throw $this->createNotFoundException('Cookie nicht gefunden' . $id);
        }


        $editForm = $this->createForm(CookieItemForm::class, $cookie);

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $entityManager->persist($cookie);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Cookie erfolgreich gespeichert'
            );
            return $this->render('@Cookie/index/close-frame.html.twig', [
            ]);

        }

        return $this->render('@Cookie/index/edit-cookie-item-with-variation-iframe.html.twig', [
            'form' => $editForm->createView()
        ]);
    }

    /**
     * @Route("/delete/{id}", name="delete")
     * @param int $id
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function delete(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isXmlHttpRequest()) {
            $msg = '';
            $success = false;

            if ($id) {
                $repositoryCookieMain = $entityManager->getRepository(CookieMain::class);
                $recordToDelete = $repositoryCookieMain->find($id);
                if ($recordToDelete) {
                    $entityManager->remove($recordToDelete);
                    $entityManager->flush();
                    $success = true;
                    $msg = 'das Cookie wurde erfolgreich gelöscht!';
                }
            }

            $toReturn = [
                'success' => $success,
                'msg' => $msg,
            ];
            return new JsonResponse($toReturn);
        } else {
            return $this->redirectToRoute('cookie_home');
        }
    }

    /**
     * @Route("/deleteCookie/{id}", name="delete_cookie")
     * @param int $id
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function deleteCookie(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        //Function to delete an entry in the CoookieItem entity
        if ($request->isXmlHttpRequest()) {
            $msg = '';
            $success = false;

            if ($id) {
                $repositoryCookieMain = $entityManager->getRepository(CookieItem::class);
                $recordToDelete = $repositoryCookieMain->find($id);
                if ($recordToDelete) {
                    $entityManager->remove($recordToDelete);
                    $entityManager->flush();
                    $success = true;
                    $msg = 'das Cookie wurde erfolgreich gelöscht!';
                }
            }

            $toReturn = [
                'success' => $success,
                'msg' => $msg,
            ];
            return new JsonResponse($toReturn);
        } else {
            return $this->redirectToRoute('cookie_home');
        }
    }

    /**
     * @Route("/changePosition", name="change_position")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function changePosition(Request $request, EntityManagerInterface $entityManager): Response
    {
        //this function is in charge to change the order of the items(their position)
        //in order to be able to sort the items(drag and drop).
        if ($request->isXmlHttpRequest()) {
            $msg = '';
            $success = false;


            if ($request->isMethod(Request::METHOD_POST)) {
                $cookies = $request->get('data');
                if (is_array($cookies) && (!empty($cookies))) {
                    $repositoryItem = $entityManager->getRepository(CookieItem::class);

                    foreach ($cookies as $cookie) {
                        $repositoryItem->find($cookie['id'])->setPosition($cookie['position']);
                    }
                    $entityManager->flush();
                }
            }

            $toReturn = [
                'success' => $success,
                'msg' => $msg,
            ];
            return new JsonResponse($toReturn);

        } else {
            return $this->redirectToRoute('cookie_home');
        }
    }

}