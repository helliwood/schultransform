<?php

namespace Trollfjord\Bundle\PublicUserBundle\Controller;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\Naming\SerializedNameAnnotationStrategy;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Trollfjord\Bundle\PublicUserBundle\Entity\User;
use Trollfjord\Bundle\PublicUserBundle\Form\EmailLinkVerification;
use Trollfjord\Bundle\PublicUserBundle\Service\GenerateUserService;
use JMS\Serializer\SerializerBuilder;
use Trollfjord\Entity\School;
use Trollfjord\Entity\SchoolAuthority;
use Trollfjord\Core\Controller\AbstractController;
use Trollfjord\Entity\SchoolTag;

/**
 *
 * @author  Dirk Mertins
 **
 * @Route("/School", name="school_")
 */
class SchoolController extends AbstractController
{

    /**
     * @Route("/school_tag",
     *     name="tag_home",
     *     defaults={"parent": null}
     * )
     *
     * @return Response
     */
    public function index(Request $request)
    {

        if ($request->isXmlHttpRequest()) {
            /** @var MediaRepository $rr */
            if ($request->isMethod(Request::METHOD_POST)) {
                $em = $this->getDoctrine()->getManager();
                $id = $request->get("id");
                switch ($request->get('action', null)) {
                    case "add":
                        $tagId = $request->get("tag");
                        $school = $em->getRepository(School::class)->find($id);
                        $tag = $em->getRepository(SchoolTag::class)->find($tagId);
                        $school->addTag($tag);
                        $em->persist($school);
                        $em->flush($school);
                        break;
                    case "remove":
                        $tagId = $request->get("tag");
                        $school = $em->getRepository(School::class)->find($id);
                        $tag = $em->getRepository(SchoolTag::class)->find($tagId);
                        $school->removeTag($tag);
                        $em->persist($school);
                        $em->flush($school);
                }
            }
            $resArray = [];
            $res = $this->getTags($id);
            $serializer = SerializerBuilder::create()
                ->setPropertyNamingStrategy(
                    new SerializedNameAnnotationStrategy(
                        new IdenticalPropertyNamingStrategy()
                    )
                )
                ->build();
            $resArray = $serializer->serialize($res, 'json');
              return new JsonResponse($resArray);
        }

        return $this->render('@PublicUser/index/school_index.html.twig', [
            "a" => 1
        ]);
    }

    public function getTags($id){
        $conn = $this->getDoctrine()->getConnection();
        $sql ="select school_tag.id as tagid, school_tag.name,st.school_id as relID from school_tag left join school_tags st on st.school_id=$id and st.school_tag_id=school_tag.id order by school_tag.id asc";
        $res = $conn->prepare($sql);
        $res->execute();
        $ret = $res->fetchAllAssociative();

        return $ret;
    }


    /**
     * @Route("/schooloverview",
     *     name="overview",
     *     defaults={"parent": null}
     * )
     *
     * @return Response
     */
    public function school_overview(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            /** @var MediaRepository $rr */
            $SchoolRepos = $this->getDoctrine()->getRepository(School::class);
            if ($request->isMethod(Request::METHOD_POST)) {
                $em = $this->getDoctrine()->getManager();

            }
            $resArray = $SchoolRepos->find4Ajax(
                $request->query->get('sort', 'name'),
                $request->query->getBoolean('sortDesc', false),
                $request->query->getInt('page', 1),
                $request->query->getInt('size', 1),
                $request->query->get('filter', null)
            );

            $serializer = SerializerBuilder::create()
                ->setPropertyNamingStrategy(
                    new SerializedNameAnnotationStrategy(
                        new IdenticalPropertyNamingStrategy()
                    )
                )
                ->build();
            $result = $serializer->serialize($resArray, 'json');


            return new Response($result);


            //     return new JsonResponse($resArray);
        }


        return $this->render('@PublicUser/index/school_index.html.twig', [
            "a" => 1
        ]);
    }

    /**
     * @Route("/schooloverviewsorted/{userType}", name="sorted_by", defaults={"userType":null} )
     *
     * @param string $userType
     * @param Request $request
     * @return Response
     * @throws ExceptionInterface
     */
    public function getSortedBy(string $userType, Request $request): Response
    {
        $result = [];
        if ($request->isXmlHttpRequest()) {
            $schoolRepos = $this->getDoctrine()->getRepository(School::class);
            $resArray = $schoolRepos->find4AjaxSortedBy(
                $userType,
                $request->query->get('sort', 'name'),
                $request->query->getBoolean('sortDesc', false),
                $request->query->getInt('page', 1),
                $request->query->getInt('size', 1),
                $request->query->get('filter', null)
            );
            $serializer = SerializerBuilder::create()
                ->setPropertyNamingStrategy(
                    new SerializedNameAnnotationStrategy(
                        new IdenticalPropertyNamingStrategy()
                    )
                )
                ->build();
            $result = $serializer->serialize($resArray, 'json');
        }
        return new Response($result);
    }

}
