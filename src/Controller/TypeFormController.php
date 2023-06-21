<?php


namespace Trollfjord\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Trollfjord\Core\Controller\AbstractController;

class TypeFormController extends AbstractController
{

    /**
     * @Route("/form-test", name="form_test")
     * @return Response
     */
    public function test() {
        return $this->render('frontend/index/typeform.html.twig');
    }

    /**
     * @Route("/form-start/{code?}",
     *     name="form_start",
     *     defaults={"code": null}
     * )
     * @return Response
     */
    public function startTypeForm(Request $request) {
        $this->get('session')->set('show_form_id', $request->attributes->get("code"));
        $this->get('session')->set('route_after_login', "form_show");
        if($this->getUser()) {
            return new RedirectResponse($this->generateUrl('form_show'));
        }else {
            return new RedirectResponse($this->generateUrl('user_login', ["type" => "frame"]));
        }
    }

    /**
     * @Route("/form-show", name="form_show")
     * @return Response
     */
    public function showTypeForm(Request $request) {
        return $this->render('frontend/type-form/show.html.twig', [
            "formId" => $this->get('session')->get('show_form_id')
        ]);
    }

    /**
     * @Route("/form-finish/{code?}",
     *     name="form_finish")
     *     defaults={"code": null}
     * @return Response
     */
    public function finishTypeForm($code, Request $request) {
        return $this->render('frontend/type-form/finish.html.twig', [
            "id" => $code
        ]);
    }


}