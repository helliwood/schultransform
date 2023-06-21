<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2020-09-04
 * Time: 6:30
 */

namespace Trollfjord\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Trollfjord\Core\Controller\AbstractController;

class TypeCollector extends AbstractController
{
    /**
     * @Route("/typecollect", name="Typeform generator")
     * @return Response
     */
    public function index(): Response
    {

        return new Response(
            'ok'
        );

    }
}
