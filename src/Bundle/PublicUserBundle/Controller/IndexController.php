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
use Trollfjord\Core\Controller\AbstractController;
use JMS\Serializer\SerializerBuilder;
use Trollfjord\Entity\School;
use Trollfjord\Entity\SchoolAuthority;

/**
 * Class IndexController
 *
 * @author  Tim Wettstein <wettstein@helliwood.com>
 *
 * @package Trollfjord\Bundle\PublicUserBundle\Controller
 *
 * @Route("/PublicUserBackend", name="public_user_")
 */
class IndexController extends AbstractController
{

    /**
     * @Route("/",
     *     name="home",
     *     defaults={"parent": null}
     * )
     *
     * @return Response
     */
    public function index(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            /** @var MediaRepository $rr */
            $userRepos = $this->getDoctrine()->getRepository(User::class);
            if ($request->isMethod(Request::METHOD_POST)) {
                $em = $this->getDoctrine()->getManager();
                switch ($request->get('action', null)) {
                    /*case "delete":
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
                        if($media = $mediaService->getMediaById($request->get("id")) ) {
                            return new JsonResponse($media);
                        }else {
                            return new JsonResponse(false);
                        }
                        break;*/
                }
            }
            $resArray = $userRepos->find4Ajax(
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


        return $this->render('@PublicUser/index/index.html.twig', [
            "a" => 1
        ]);
    }

    /**
     * @Route("/edit/{id<\d+>?}",
     *     name="edit",
     *     defaults={"id": null}
     * )
     *
     * @return Response
     */
    public function edit(Request $request, EntityManagerInterface $entityManager, UserPasswordEncoderInterface $encoder)
    {
        $id = $request->attributes->get("id");
        $user = $entityManager->getRepository(User::class)->findOneBy(['id' => $id]);
        $form = $this->createForm(\Trollfjord\Bundle\PublicUserBundle\Form\User::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $_user */
            $_user = $form->getData();

            if ($_user->getUsername() == "") {
                $_user->setUsername(null);
                $_user->setPassword(null);
            } elseif ($_user->getPassword() != "") {
                $pwsString = $encoder->encodePassword($_user, $_user->getPassword());
                $_user->setPassword($pwsString);
            }

            $entityManager->persist($_user);
            $entityManager->flush($_user);

            $this->addFlash('success', 'User wurde erfolgreich gespeichert!');
            //return $this->render('@PublicUser/close-frame.html.twig');
        }

        return $this->render('@PublicUser/index/edit.html.twig', [
            "form" => $form->createView()
        ]);
    }

    /**
     * @Route("/confirmSchool/{id<\d+>?}",
     *     name="confirm_school",
     *     defaults={"id": null}
     * )
     *
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param UserPasswordEncoderInterface $encoder
     * @param MailerInterface $mailer
     * @return Response
     * @throws TransportExceptionInterface
     */
    public function confirmSchool(Request $request, EntityManagerInterface $entityManager, UserPasswordEncoderInterface $encoder, MailerInterface $mailer): Response
    {
        $id = $request->attributes->get("id");
        /** @var User $user */
        $user = $entityManager->getRepository(User::class)->findOneBy(['id' => $id]);
        $form = $this->createForm(\Trollfjord\Bundle\PublicUserBundle\Form\ConfirmSchool::class, $user->getSchool());

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($user->getSchool() && ($user->getSchool()->isConfirmed())) {
                $user->setRoles(["ROLE_SCHOOL"]);
                if ($form->get('send_mail')->getData()) {
                    $viewData = [];
                    $viewData['user'] = $user;
                    $email = (new TemplatedEmail())
                        ->subject('Bestätigung Ihrer Schule auf schultransform.org')
                        ->from(new Address('support@schultransform.org', 'Schultransform'))
                        ->to($viewData['user']->getEmail())
                        ->htmlTemplate('@PublicUser/emails/school_confirmed.html.twig')
                        ->context(
                            ['data' => $viewData]
                        );

                    $mailer->send($email);
                }
            } else {
                $user->setRoles(["ROLE_SCHOOL_LITE"]);
            }
            $entityManager->flush();

            $this->addFlash('success', 'Daten wurden erfolgreich gespeichert!');
            return $this->render('@PublicUser/close-frame.html.twig');
        }

        return $this->render('@PublicUser/index/edit.html.twig', [
            "form" => $form->createView()
        ]);
    }

    /**
     * @Route("/confirmSchoolAuthority/{id<\d+>?}",
     *     name="confirm_school_authority",
     *     defaults={"id": null}
     * )
     *
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param UserPasswordEncoderInterface $encoder
     * @param MailerInterface $mailer
     * @return Response
     * @throws TransportExceptionInterface
     */
    public function confirmSchoolAuthority(Request $request, EntityManagerInterface $entityManager, UserPasswordEncoderInterface $encoder, MailerInterface $mailer): Response
    {
        $id = $request->attributes->get("id");
        /** @var User $user */
        $user = $entityManager->getRepository(User::class)->findOneBy(['id' => $id]);
        $form = $this->createForm(\Trollfjord\Bundle\PublicUserBundle\Form\ConfirmSchoolAuthority::class, $user->getSchoolAuthority());

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            if ($user->getSchoolAuthority() && ($user->getSchoolAuthority()->isConfirmed())) {
                $user->setRoles(["ROLE_SCHOOL_AUTHORITY"]);
                if ($form->get('send_mail')->getData()) {
                    $viewData = [];
                    $viewData['user'] = $user;
                    $email = (new TemplatedEmail())
                        ->subject('Bestätigung als Schulträger auf schultransform.org')
                        ->from(new Address('support@schultransform.org', 'Schultransform'))
                        ->to($viewData['user']->getEmail())
                        ->htmlTemplate('@PublicUser/emails/school_authority_confirmed.html.twig')
                        ->context(
                            ['data' => $viewData]
                        );

                    $mailer->send($email);
                }
            } else {
                $user->setRoles(["ROLE_SCHOOL_AUTHORITY_LITE"]);
            }
            $entityManager->flush();

            $this->addFlash('success', 'Daten wurden erfolgreich gespeichert!');
            return $this->render('@PublicUser/close-frame.html.twig');
        }

        return $this->render('@PublicUser/index/edit-school-authority.html.twig', [
            "form" => $form->createView()
        ]);
    }

    /**
     * @Route("/add", name="add" )
     *
     * @return Response
     */
    public function addUser(Request $request, EntityManagerInterface $entityManager, UserPasswordEncoderInterface $encoder, GenerateUserService $generateUserService)
    {
        $form = $this->createForm(\Trollfjord\Bundle\PublicUserBundle\Form\AddUser::class, new User());
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $_user */
            $_user = $form->getData();

            $user = $generateUserService->getNewUser();
            $user->setUsername($_user->getUsername());
            $user->setEmail($_user->getEmail());
            $pwsString = $encoder->encodePassword($user, $_user->getPassword());
            $user->setPassword($pwsString);

            $entityManager->persist($user);
            $entityManager->flush($user);

            $this->addFlash('success', 'User wurde erfolgreich gespeichert!');
            return $this->render('@PublicUser/close-frame.html.twig');
        }

        return $this->render('@PublicUser/index/add.html.twig', [
            "form" => $form->createView()
        ]);
    }

    /**Entry points to control the UserTable vue**/

    /**
     * @Route("/sortedItems", name="sorted_items" )
     *
     * @return Response
     */
    public function getItemsSorted()
    {
        $userRepos = $this->getDoctrine()->getRepository(User::class);
        return new JsonResponse($userRepos->sortAndCountUsers());
    }

    /**
     * @Route("/sortedBy/{userType}", name="sorted_by", defaults={"userType":null} )
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
            $userRepos = $this->getDoctrine()->getRepository(User::class);
            $resArray = $userRepos->find4AjaxSortedBy(
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

    /**
     * @Route("/getTeachersRelated/{schoolId}", name="teachers_related", defaults={"schoolId":null} )
     *
     * @param int $schoolId
     * @param Request $request
     * @return Response
     */
    public function getTeachersRelated(int $schoolId, Request $request)
    {
        if ($request->isXmlHttpRequest()) {

            $userRepos = $this->getDoctrine()->getRepository(User::class);
            $resArray = $userRepos->getTeachersRelated($schoolId);

            $serializer = SerializerBuilder::create()
                ->setPropertyNamingStrategy(
                    new SerializedNameAnnotationStrategy(
                        new IdenticalPropertyNamingStrategy()
                    )
                )
                ->build();
            $result = $serializer->serialize($resArray, 'json');

            return new Response($result);
        }
    }

    /**
     * @Route("/getSchoolsRelated/{schoolAuthorityId}", name="schools_related", defaults={"schoolAuthorityId":null} )
     *
     * @param int $schoolAuthorityId
     * @param Request $request
     * @return Response
     */
    public function getSchoolsRelated(int $schoolAuthorityId, Request $request): Response
    {
        if ($request->isXmlHttpRequest()) {

            $userRepos = $this->getDoctrine()->getRepository(User::class);
            $resArray = $userRepos->getSchoolsRelated($schoolAuthorityId);

            $serializer = SerializerBuilder::create()
                ->setPropertyNamingStrategy(
                    new SerializedNameAnnotationStrategy(
                        new IdenticalPropertyNamingStrategy()
                    )
                )
                ->build();
            $result = $serializer->serialize($resArray, 'json');

            return new Response($result);
        }

        return $this->redirectToRoute('public_user_home');
    }

    /**
     * @Route("/getUserInfos/{userId}", name="user_infos", defaults={"userId":null} )
     *
     * @param int $userId
     * @param Request $request
     * @return Response
     */
    public function getUserInfos(int $userId, Request $request): Response
    {
        if ($request->isXmlHttpRequest()) {

            $userRepos = $this->getDoctrine()->getRepository(User::class);
            $resArray = $userRepos->find($userId);

            $serializer = SerializerBuilder::create()
                ->setPropertyNamingStrategy(
                    new SerializedNameAnnotationStrategy(
                        new IdenticalPropertyNamingStrategy()
                    )
                )
                ->build();
            $result = $serializer->serialize($resArray, 'json');

            return new Response($result);
        }
        return $this->redirectToRoute('public_user_home');
    }

    /* Create email verification link & send the email to the user*/
    /**
     * @Route("/create-link/{userId}", name="create_link", defaults={"userId":null} )
     *
     * @param int $userId
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param MailerInterface $mailer
     * @return Response
     * @throws TransportExceptionInterface
     */
    public function createLinkEmailVerification(int $userId, Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {

        $form = $this->createForm(EmailLinkVerification::class);
        $form->handleRequest($request);
        /** @var User $user * */
        if (!$user = $entityManager->getRepository(User::class)->find($userId)) {
            $form->addError(new FormError('der Benutzer wurde nicht gefunden.'));
        }

        $uroute = null;
        $emailTemplate = '';
        if ($form->isSubmitted()) {
            //verify that the user has a hash if not create a new one
            if (!$user->getHash()) {
                $user->setHash(md5(time()));
                $entityManager->persist($user);
                $entityManager->flush();
            }

            if (!empty($user->getRoles())) {

                if (in_array('ROLE_SCHOOL_LITE', $user->getRoles()) || in_array('ROLE_SCHOOL', $user->getRoles())) {
                    $uroute = 'user_register_school_confirmation';
                    $emailTemplate = 'mail/email_confirmation_link_school.html.twig';
                } elseif (in_array('ROLE_SCHOOL_AUTHORITY_LITE', $user->getRoles()) || in_array('ROLE_SCHOOL_AUTHORITY', $user->getRoles())) {
                    $uroute = 'school_authority_confirmation';
                    $emailTemplate = 'mail/email_confirmation_link_school_authority.html.twig';
                }
            }

            if (!$uroute) {
                $form->addError(new FormError('der Benutzer verfügt nicht über die für diese Funktion notwendigen Rollen.'));
            }

        }
        //defining the data to load the template responsible for the email structure.
        $dataForEmailTemplate = [];
        $dataForEmailTemplate['user'] = $user;
        $dataForEmailTemplate['viewOnly'] = true;
        if ($form->isSubmitted() && $form->isValid()) {
            $viewData = [];
            $viewData['user'] = $user;
            $viewData['form'] = $form->getData();
            $viewData['link'] = $this->generateUrl($uroute, ['hash' => $viewData['user']->getHash()], UrlGeneratorInterface::ABSOLUTE_URL);

            try {
                $email = (new TemplatedEmail())
                    ->subject('schultransform | Erinnerung: Bitte bestätigen Sie Ihre Registrierung')
                    ->from(new Address('support@schultransform.org', 'Schultransform'))
                    ->to($viewData['user']->getEmail())
                    ->htmlTemplate($emailTemplate)
                    ->context(
                        ['data' => $viewData]
                    );

                $mailer->send($email);

                //if success save tha date and add 1 to the column 'number_of_reminders'
                $reminderEmailDate = new DateTime();
                $user->setReminderEmailDate($reminderEmailDate);

                $numberTimes = $user->getNumberOfReminders() ?: 0;
                $user->setNumberOfReminders($numberTimes + 1);

                $entityManager->persist($user);
                $entityManager->flush();

            } catch (Exception $e) {

            }

            $this->addFlash('success', 'E-Mail wurde erfolgreich gesendet!');
            return $this->render('@PublicUser/close-frame.html.twig', [
                'email' => $user ? $user->getEmail() : null,
            ]);
        }
        return $this->render('@PublicUser/index/email-verification-link.html.twig', [
            "form" => $form->createView(),
            'data' => $dataForEmailTemplate,
        ]);
    }


    /* Set test school*/
    /**
     * @Route("/setTestSchool", name="set_test_school")
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @return Response
     */
    public function setTestSchool(EntityManagerInterface $entityManager, Request $request): Response
    {
        $toReturn = [
            'success' => false,
        ];
        if ($request->isXmlHttpRequest()) {
            $valueSchoolTest = $request->get('valueSchoolTest');
            $schoolId = $request->get('schoolId');

            if ($school = $entityManager->getRepository(School::class)->find($schoolId)) {
                $school->setTestSchool((int)$valueSchoolTest);
                $entityManager->persist($school);
                $entityManager->flush();
                $toReturn['schoolName'] = $school->getName();
                $toReturn['success'] = true;
                return new JsonResponse($toReturn);
            }
        }

        return $this->redirectToRoute('public_user_home');
    }

    /* Set test school authority*/
    /**
     * @Route("/setTestSchoolAuthority", name="set_test_school_authority")
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @return Response
     */
    public function setTestSchoolAuthority(EntityManagerInterface $entityManager, Request $request): Response
    {
        $toReturn = [
            'success' => false,
        ];

        if ($request->isXmlHttpRequest()) {
            $valueSchoolAuthorityTest = $request->get('valueSchoolAuthorityTest');
            $schoolAuthorityId = $request->get('schoolAuthorityId');

            if ($schoolAuthority = $entityManager->getRepository(SchoolAuthority::class)->find($schoolAuthorityId)) {
                $schoolAuthority->setTestSchoolAuthority((int)$valueSchoolAuthorityTest);
                $entityManager->persist($schoolAuthority);
                $entityManager->flush();
                $toReturn['schoolAuthorityName'] = $schoolAuthority->getName();
                $toReturn['success'] = true;
                return new JsonResponse($toReturn);
            }
        }

        return $this->redirectToRoute('public_user_home');
    }

    /* get the questionnaires filled out by user*/
    /**
     * @Route("/getQuestionnairesFilledOut/{userId}", name="get_questionnaires_filled_out",defaults={"userId":null})
     * @param int $userId
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @return Response
     */
    public function getQuestionnairesFilledOutByUser(int $userId, EntityManagerInterface $entityManager, Request $request): Response
    {

        $toReturn =  $entityManager->getRepository(User::class)->getQuestionnairesFilledOut($userId);

        return new JsonResponse($toReturn);

    }
}
