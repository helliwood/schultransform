<?php

namespace Trollfjord\Controller;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Trollfjord\Bundle\PublicUserBundle\Entity\User;
use Trollfjord\Bundle\PublicUserBundle\Service\GenerateUserService;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Question;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Recommendation;
use Trollfjord\Bundle\QuestionnaireBundle\Service\QuestionnaireService;
use Trollfjord\Core\Controller\AbstractController;
use Trollfjord\Entity\School;
use Trollfjord\Entity\SchoolAuthority;
use Trollfjord\Entity\SchoolType as SchoolTypeEntity;
use Trollfjord\Form\AccountType;
use Trollfjord\Form\DsgvoType;
use Trollfjord\Form\SchoolAuthorityType;
use Trollfjord\Form\SchoolType;
use Trollfjord\Service\UserService;
use function array_merge;
use function is_null;
use function is_numeric;
use function md5;
use function time;

/**
 * @method User getUser
 */
class UserController extends AbstractController
{
    /**
     * @Route("/PublicUser/register-school/{step}", defaults={"step":1}, name="user_register_school")
     * @param Request $request
     * @param SessionInterface $session
     * @param UserPasswordEncoderInterface $encoder
     * @param GenerateUserService $generateUserService
     * @param MailerInterface $mailer
     * @param UserService $userService
     * @param int $step
     * @return Response
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function registerSchool(
        Request                      $request, SessionInterface $session,
        UserPasswordEncoderInterface $encoder,
        GenerateUserService          $generateUserService,
        MailerInterface              $mailer,
        UserService                  $userService,
        int                          $step = 1): Response
    {
        $form = null;
        $usedSchoolAuthority = null;
        // Dieser Query-Parameter wird im Einladungs-PDF vom Schulträger an den Registrierungslink angehangen
        if ($request->query->has('schoolAuthority')) {
            $session->set(self::class . '_school-authority', $request->query->get('schoolAuthority'));
            $session->remove(self::class . 'step_2');
            $session->set(self::class . '_schoolAuthorityInvitation', true);
        }
        $viewData = ['schoolAuthorityInvitation' => $session->get(self::class . '_schoolAuthorityInvitation', false)];
        $schoolAuthorityId = $session->get(self::class . '_school-authority');

        if ($step == 1) {
            $school = new School();
            if ($session->has(self::class . 'step_1')) {
                /** @var School $school */
                $school = $session->get(self::class . 'step_1', null);
                $school->setSchoolType($this->getDoctrine()->getManager()->find(SchoolTypeEntity::class, $school->getSchoolType()->getName()));
            }
            $form = $this->createForm(SchoolType::class, $school);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                if ($school->getAddress()->getCountry() !== null) {
                    $school->getAddress()->setFederalState(null);
                }
                $session->set(self::class . 'step_1', $form->getData());
                return $this->redirectToRoute("user_register_school", ["step" => 2]);
            }
        } elseif ($step == 2) {
            $schoolAuthority = new SchoolAuthority();
            if ($session->has(self::class . 'step_2')) {
                $schoolAuthority = $session->get(self::class . 'step_2', null);
            }
            $form = $this->createForm(SchoolAuthorityType::class, $schoolAuthority);
            $form->handleRequest($request);

            if (is_numeric($schoolAuthorityId)) {
                $usedSchoolAuthority = $this->getDoctrine()->getRepository(SchoolAuthority::class)->find($schoolAuthorityId);
            }
            if ($request->request->has('back')) {
                return $this->redirectToRoute("user_register_school", ["step" => 1]);
            }
            if ($form->isSubmitted() && $form->isValid()) {
                $session->set(self::class . 'step_2', $form->getData());
                $session->remove(self::class . '_school-authority');
                return $this->redirectToRoute("user_register_school", ["step" => 3]);
            }
        } elseif ($step == 3) {
            /** @var User $user */
            $user = clone $session->get(self::class . 'step_3', new User());
            $form = $this->createForm(AccountType::class, $user, ['user' => $user]);
            $form->handleRequest($request);

            if ($request->request->has('back')) {
                return $this->redirectToRoute("user_register_school", ["step" => 2]);
            }
            if ($form->isSubmitted() && $form->isValid()) {
                if ($user->getNewPassword()) {
                    $user->setPassword($encoder->encodePassword($user, $user->getNewPassword()));
                    $user->setNewPassword(null);
                }
                $session->set(self::class . 'step_3', $user);
                return $this->redirectToRoute("user_register_school", ["step" => 4]);
            }
        } elseif ($step == 4) {
            $form = $this->createForm(DsgvoType::class);
            $form->handleRequest($request);
            if ($request->request->has('back')) {
                return $this->redirectToRoute("user_register_school", ["step" => 3]);
            }

            $viewData['user'] = $session->get(self::class . 'step_3', null);
            $viewData['school'] = $session->get(self::class . 'step_1', null);
            if (is_numeric($schoolAuthorityId)) {
                $viewData['schoolAuthority'] = $this->getDoctrine()->getRepository(SchoolAuthority::class)->find($schoolAuthorityId);
            } else {
                $viewData['schoolAuthority'] = $session->get(self::class . 'step_2', null);
            }

            if ($form->isSubmitted() && $form->isValid()) {
                // Muss neu aus der DB geholt werden, in Session funktioniert es nicht
                $schoolType = $this->getDoctrine()->getManager()->find(SchoolTypeEntity::class, $viewData['school']->getSchoolType()->getName());
                $this->getDoctrine()->getManager()->persist($viewData['user']);
                $viewData['school']->setSchoolType($schoolType);
                $viewData['school']->setSchoolAuthority($viewData['schoolAuthority']);
                $viewData['school']->setCode($generateUserService->getNewSchoolCode());
                $this->getDoctrine()->getManager()->persist($viewData['school']);
                if (is_null($schoolAuthorityId)) {
                    $this->getDoctrine()->getManager()->persist($viewData['schoolAuthority']);
                }
                $viewData['user']->setSchoolType($schoolType);
                $viewData['user']->setRoles(["ROLE_SCHOOL_LITE"]);
                $viewData["user"]->setHash(md5(time()));
                $viewData["user"]->setSchool($viewData['school']);
                //save the registration date
                $registrationDate = new DateTime;
                $viewData["user"]->setRegistrationDate($registrationDate);
                $this->getDoctrine()->getManager()->flush();
                $session->clear();

                $viewData['link'] = $this->generateUrl('user_register_school_confirmation', ['hash' => $viewData['user']->getHash()], UrlGeneratorInterface::ABSOLUTE_URL);

                //mail for school
                $email = (new TemplatedEmail())
                    ->subject('schultransform | Bitte bestätigen Sie Ihre Registrierung')
                    ->from(new Address('support@schultransform.org', 'Schultransform'))
                    ->to($viewData['user']->getEmail())
                    ->htmlTemplate('mail/register_school.html.twig')
                    ->context(
                        ['data' => $viewData]
                    )->attach($userService->getSchoolFaxDompdf($viewData['user'])->output(), "Schulfax.pdf");

                $mailer->send($email);

                //mail for internal
                $intemail = (new TemplatedEmail())
                    ->subject('schultransform | Neue Schule wurde registriert')
                    ->from(new Address('support@schultransform.org', 'Schultransform'))
                    ->to(new Address('support@schultransform.org', 'Schultransform'))
                    ->htmlTemplate('mail/register_school_teaminfo.html.twig')
                    ->context(
                        ['data' => $viewData]
                    );

                $mailer->send($intemail);
                return $this->redirectToRoute("user_register_school_finish", ['code' => $viewData['school']->getCode()]);
            }
        }
        return $this->render('frontend/user/register_school.html.twig', array_merge($viewData, [
            'form' => $form ? $form->createView() : null,
            'schoolAuthorityFormSubmitted' => $step == 2 && ($form->isSubmitted() || $session->has(self::class . 'step_2')),
            'usedSchoolAuthority' => $usedSchoolAuthority,
            'step' => $step
        ]));
    }

    /**
     * @Route("/PublicUser/set-school-authority", name="user_set_school_authority")
     * @param SessionInterface $session
     * @param Request $request
     * @return JsonResponse
     */
    public function setSchoolAuthority(SessionInterface $session, Request $request): JsonResponse
    {

        if (is_numeric($request->query->get('school-authority-id'))) {
            $session->set(self::class . '_school-authority', $request->query->get('school-authority-id'));
            $session->remove(self::class . 'step_2');
        } else {
            $session->remove(self::class . '_school-authority');
        }
        return new JsonResponse($request->query->get('school-authority-id'));
    }

    /**
     * @Route("/PublicUser/register-school-authority/{step}", defaults={"step":1}, name="user_register_school_authority")
     *
     * @throws Exception|TransportExceptionInterface
     */
    public function registerSchoolAuthority(
        Request                      $request,
        SessionInterface             $session,
        UserPasswordEncoderInterface $encoder,
        GenerateUserService          $generateUserService,
        MailerInterface              $mailer,
        UserService                  $userService,
        int                          $step = 1
    ): Response
    {
        $form = null;
        $schoolAuthorityId = $session->get(self::class . '_school-authority');
        $usedSchoolAuthority = null;
        $viewData = [];

        if ($step == 1) {
            $schoolAuthority = new SchoolAuthority();
            if ($session->has(self::class . 'step_1')) {
                $schoolAuthority = $session->get(self::class . 'step_1', null);
            }

            if (is_numeric($schoolAuthorityId)) {
                $usedSchoolAuthority = $this->getDoctrine()->getRepository(SchoolAuthority::class)->find($schoolAuthorityId);
                $form = $this->createForm(SchoolAuthorityType::class, $usedSchoolAuthority);
            } else {
                $form = $this->createForm(SchoolAuthorityType::class, $schoolAuthority);
            }
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $session->set(self::class . 'step_1', $form->getData());
                $session->remove(self::class . '_school-authority');
                return $this->redirectToRoute("user_register_school_authority", ["step" => 2]);
            }
        } elseif ($step == 2) {
            /** @var User $user */
            $user = clone $session->get(self::class . 'step_2', new User());
            $form = $this->createForm(AccountType::class, $user, ['user' => $user]);
            $form->handleRequest($request);

            if ($request->request->has('back')) {
                return $this->redirectToRoute("user_register_school_authority", ["step" => 1]);
            }
            if ($form->isSubmitted() && $form->isValid()) {
                if ($user->getNewPassword()) {
                    $user->setPassword($encoder->encodePassword($user, $user->getNewPassword()));
                    $user->setNewPassword(null);
                }
                $session->set(self::class . 'step_2', $user);
                return $this->redirectToRoute("user_register_school_authority", ["step" => 3]);
            }
        } elseif ($step == 3) {
            $form = $this->createForm(DsgvoType::class);
            $form->handleRequest($request);
            if ($request->request->has('back')) {
                return $this->redirectToRoute("user_register_school_authority", ["step" => 2]);
            }

            $viewData['user'] = $session->get(self::class . 'step_2', null);
            if (is_numeric($schoolAuthorityId)) {
                $viewData['schoolAuthority'] = $this->getDoctrine()->getRepository(SchoolAuthority::class)->find($schoolAuthorityId);
            } else {
                $viewData['schoolAuthority'] = $session->get(self::class . 'step_1', null);
            }

            if ($form->isSubmitted() && $form->isValid()) {
                $this->getDoctrine()->getManager()->persist($viewData['user']);
                $viewData['schoolAuthority']->setCode($generateUserService->getNewSchoolAuthorityCode());
                if (is_null($schoolAuthorityId)) {
                    $this->getDoctrine()->getManager()->persist($viewData['schoolAuthority']);
                }
                $viewData['user']->setRoles(["ROLE_SCHOOL_AUTHORITY_LITE"]);
                $viewData["user"]->setHash(md5(time()));
                $viewData["user"]->setSchoolAuthority($viewData['schoolAuthority']);
                $this->getDoctrine()->getManager()->flush();
                $session->clear();

                $viewData['link'] = $this->generateUrl('user_register_school_authority_confirmation', ['hash' => $viewData['user']->getHash()], UrlGeneratorInterface::ABSOLUTE_URL);
                $email = (new TemplatedEmail())
                    ->subject('Registrierung auf schultransform.org')
                    ->from(new Address('support@schultransform.org', 'Schultransform'))
                    ->to($viewData['user']->getEmail())
                    ->htmlTemplate('mail/register_school_authority.html.twig')
                    ->context(
                        ['data' => $viewData]
                    )->attach($userService->getSchoolAuthorityFaxDompdf($viewData['user'])->output(), "Schultraegerfax.pdf");

                $mailer->send($email);

                return $this->redirectToRoute("user_register_school_authority_finish", ['code' => $viewData['schoolAuthority']->getCode()]);
            }
        }

        return $this->render('frontend/user/register_school_authority.html.twig', array_merge($viewData, [
            'form' => $form ? $form->createView() : null,
            'schoolAuthorityFormSubmitted' => $step == 1 && ($form->isSubmitted() || $session->has(self::class . 'step_1')),
            'usedSchoolAuthority' => $usedSchoolAuthority,
            'step' => $step
        ]));
    }

    /**
     * @Route("/PublicUser/register-school-authority-finish/{code}", name="user_register_school_authority_finish")
     * @param string $code
     * @return Response
     */
    public function registerSchoolAuthorityFinish(string $code): Response
    {
        return $this->render('frontend/user/register_school_authority_finish.html.twig', ['code' => $code]);
    }

    /**
     * @Route("/PublicUser/register-school-authority/confirmation/{hash}", name="user_register_school_authority_confirmation")
     * @param string $hash
     * @return Response
     */
    public function registerSchoolAuthorityConfirmation(string $hash): Response
    {
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(["hash" => $hash]);
        if ($user instanceof User) {
            $user->setHash(null);
            $user->setUsername($user->getEmail());
            $user->setRoles(["ROLE_SCHOOL_AUTHORITY_LITE"]);
            $this->getDoctrine()->getManager()->persist($user);
            $this->getDoctrine()->getManager()->flush($user);

            return $this->render('frontend/user/register_school_authority_confirmation.html.twig', [
                'user' => $user
            ]);
        } else {
            return $this->render('frontend/user/register_school_authority_confirmation.html.twig', [
                'user' => false
            ]);
        }

    }

    /**
     * @Route("/PublicUser/register-school-finish/{code}", name="user_register_school_finish")
     * @param string $code
     * @return Response
     */
    public function registerSchoolFinish(string $code): Response
    {
        return $this->render('frontend/user/register_school_finish.html.twig', ['code' => $code]);
    }

    /**
     * @Route("/PublicUser/register-school/confirmation/{hash}", name="user_register_school_confirmation")
     * @param string $hash
     * @return Response
     */
    public function registerSchoolConfirmation(string $hash): Response
    {
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(["hash" => $hash]);
        if ($user instanceof User) {
            $user->setHash(null);
            $user->setUsername($user->getEmail());
            $user->setRoles(["ROLE_SCHOOL_LITE"]);
            $this->getDoctrine()->getManager()->persist($user);
            $this->getDoctrine()->getManager()->flush($user);

            return $this->render('frontend/user/register_school_confirmation.html.twig', [
                'user' => $user
            ]);
        } else {
            return $this->render('frontend/user/register_school_confirmation.html.twig', [
                'user' => false
            ]);
        }

    }

    /**
     * @Route("/PublicUser/get-school-fax", name="get_school_fax")
     * @IsGranted("ROLE_SCHOOL_LITE")
     */
    public function getSchoolFax(UserService $userService): void
    {
        $dompdf = $userService->getSchoolFaxDompdf();
        $dompdf->stream("Bestätigung der Schule.pdf");
    }

    /**
     * @Route("/PublicUser/create-school-fax/{schoolCode}", name="create_school_fax")
     * @param string|null $schoolCode
     * @return Response
     */
    public function createSchoolFax(string $schoolCode,UserService $userService): void
    {
        $school = $this->getDoctrine()->getManager()->getRepository(School::class)->findOneBy(['code' => $schoolCode]);
        $user = $school->getMainUser();
        $dompdf = $userService->getSchoolFaxDompdf($user);
        $dompdf->stream("Bestätigung der Schule.pdf");
    }


    /**
     * @Route("/PublicUser/search-school-authorities", name="user_search_school_authorities")
     * @param Request $request
     * @return JsonResponse
     */
    public function searchSchoolAuthorities(Request $request): JsonResponse
    {
        $data = $this->getDoctrine()
            ->getRepository(SchoolAuthority::class)
            ->findByPostalcode($request->query->get('search'));
        return new JsonResponse($data);
    }

    /**
     * @Route("/PublicUser/api", name="user_api")
     * @param Request $request
     * @param GenerateUserService $generateUserService
     * @return Response
     * @throws Exception
     */
    public function api(Request $request, GenerateUserService $generateUserService): Response
    {
        if ($request->isXmlHttpRequest()) {
            if ($request->isMethod(Request::METHOD_POST)) {
                $em = $this->getDoctrine()->getManager();
                switch ($request->get('action', null)) {
                    case "newCode":
                        $user = $generateUserService->getNewUser($request->get('schoolType', null), $request->get('schoolCode', null));
                        return new JsonResponse($user->getCode());
                        break;
                }
            }
        }
    }

    /**
     * @Route("/PublicUser/invite-teacher/{schoolCode}", name="invite_teacher")
     * @param string|null $schoolCode
     * @return Response
     */
    public function inviteTeacher(string $schoolCode): Response
    {
        $school = $this->getDoctrine()->getManager()->getRepository(School::class)->findOneBy(['code' => $schoolCode]);
        if ($this->getUser()) {
            return $this->render('frontend/user/invite_teacher_error.html.twig', [
                "school" => $school
            ]);
        }

        return new RedirectResponse($this->generateUrl('user_login', ["schoolCode" => $schoolCode]));
    }

    /**
     * @Route("/PublicUser/user-login/{schoolCode?}",
     *     name="user_login",
     *     defaults={"schoolCode": null}
     * )
     * @param string|null $schoolCode
     * @param Request $request
     * @param AuthenticationUtils $authenticationUtils
     * @return Response
     */
    public function login(?string $schoolCode, Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $school = null;
        if (!is_null($schoolCode)) {
            $school = $this->getDoctrine()->getManager()->getRepository(School::class)->findOneBy(['code' => $schoolCode]);
        }

        if ($this->getUser()) {
            return new RedirectResponse($this->generateUrl('user_success'));
        }

        return $this->render('frontend/user/login.html.twig', [
            "error" => $error,
            "school" => $school ? array_merge($school->toArray(0), ["schoolType" => $school->getSchoolType()->getName()]) : null
        ]);

    }

    /**
     * @Route("/PublicUser/user-success", name="user_success")
     * @IsGranted("ROLE_PUBLIC")
     * @param Request $request
     * @param QuestionnaireService $questionnaireService
     * @return Response
     * @throws \Doctrine\DBAL\Exception
     */
    public function success(Request $request, QuestionnaireService $questionnaireService): Response
    {
        $user = $this->getUser();
        //save the DateTime
        if ($user instanceof User) {
            $loginDate = new DateTime;
            //check if exist a current login date
            if ($user->getCurrentLogin()) {
                //save current login date into last login date
                $user->setLastLogin($user->getCurrentLogin());
            }
            $user->setCurrentLogin($loginDate); //TODO: put this into login-logic
            $this->getDoctrine()->getManager()->persist($user);
            $this->getDoctrine()->getManager()->flush();
        }
        if ($this->isGranted('ROLE_TEACHER')) {
            return $this->redirectToRoute('dashboard_teacher_home');
        } elseif ($this->isGranted('ROLE_SCHOOL_LITE')) {
            return $this->redirectToRoute('dashboard_school_home');
        } elseif ($this->isGranted('ROLE_SCHOOL_AUTHORITY_LITE')) {
            return $this->redirectToRoute('dashboard_school_authority_home');
        }
        throw $this->createNotFoundException('Unknown User');
    }


    /**
     * @Route("/PublicUser/edit-school", name="user_edit_school")
     * @IsGranted("ROLE_SCHOOL_LITE")
     * @return Response
     */
    public function editSchool(Request $request)
    {
        $user = $this->getUser();
        $form = $this->createForm(SchoolType::class, $user->getSchool());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            return $this->redirectToRoute("user_success");
        }
        return $this->render('frontend/user/edit_school.html.twig', [
            "user" => $user,
            "form" => $form->createView()
        ]);
    }

    /**
     * @Route("/PublicUser/edit-school-authority", name="user_edit_school_authority")
     * @IsGranted("ROLE_SCHOOL_AUTHORITY_LITE")
     * @return Response
     */
    public function editSchoolAuthority(Request $request)
    {
        $user = $this->getUser();
        $form = $this->createForm(SchoolAuthorityType::class, $user->getSchoolAuthority());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            return $this->redirectToRoute("user_success");
        }
        return $this->render('frontend/user/edit_school_authority.html.twig', [
            "user" => $user,
            "form" => $form->createView()
        ]);
    }

    /**
     * @Route("/print-teacher-invitation", name="print_teacher_invitation")
     * @IsGranted("ROLE_PUBLIC")
     * @throws Exception
     */
    public function print()
    {

        if (!$this->isGranted('ROLE_SCHOOL_LITE') && !$this->isGranted('ROLE_TEACHER')) {
            throw $this->createAccessDeniedException('Zugriff verweigert');
        }


        $school = $this->getUser()->getSchool();
        $dompdf = new Dompdf(array('isPhpEnabled' => true));
        $content = $this->render('pdf/teacher-invitation.html.twig', [
            'school' => $school
        ])->getContent();
        $dompdf->loadHtml($content);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream($this->getUser()->getSchool()->getName() . "-Einladung.pdf");
    }


    /**
     * @Route("/print-teacher-invitation-school-authority/{school<\d+>?}", defaults={"school":null}, name="print_teacher_invitation_school_authority")
     * @IsGranted("ROLE_PUBLIC")
     * @throws Exception
     */
    public function printSchoolAuthority(School $school)
    {

        if (!$this->isGranted('ROLE_SCHOOL_AUTHORITY_LITE')) {
            throw $this->createAccessDeniedException('Zugriff verweigert');
        }


        $dompdf = new Dompdf(array('isPhpEnabled' => true));
        $content = $this->render('pdf/teacher-invitation.html.twig', [
            'school' => $school
        ])->getContent();
        $dompdf->loadHtml($content);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream($school->getName() . "-Einladung.pdf");
    }


    /**
     * @Route("/link-school", name="link_school")
     * @IsGranted("ROLE_TEACHER")
     * @throws Exception
     */
    public function linkSchool(Request $request)
    {
        $errors = [];

        try {
            $linkSchoolForm = $this->createForm(FormType::class, null, ['csrf_protection' => false]);
            $linkSchoolForm->add('schoolCode', TextType::class, ['constraints' => [new NotBlank()]]);

            $linkSchoolForm->submit($request->request->all());
            $linkSchoolForm->handleRequest($request);
            if ($linkSchoolForm->isSubmitted() && $linkSchoolForm->isValid()) {

                $school = $this->getDoctrine()->getRepository(School::class)->findOneBy(['code' => $linkSchoolForm->getData()['schoolCode']]);

                if (is_null($school)) {
                    $linkSchoolForm->addError(new FormError('Schulcode nicht gefunden!'));

                } elseif ($school->getSchoolType() !== $this->getUser()->getSchoolType()) {
                    $linkSchoolForm->addError(new FormError('Der Schultyp (' . $school->getSchoolType() . ') ist nicht mit Ihrem Code (' . $this->getUser()->getSchoolType() . ') kompatibel!'));
                } else {
                    $this->getUser()->setSchool($school);
                    $this->getDoctrine()->getManager()->flush();
                    return new JsonResponse(['message' => 'Schule erfolgreich verknüpft'], 200);

                }
            };
            foreach ($linkSchoolForm->getErrors() as $key => $error) {
                $errors[] = $error->getMessage();
            }
            if (count($errors) > 0) {
                return new JsonResponse(['error' => implode(',', $errors)], 200);
            } else {
                return new JsonResponse(['message' => 'Schule erfolgreich verknüpft'], 200);
            }

        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), 500);
        }
    }


    /**
     * @Route("/link-school-authority", name="link_school_authority")
     * @IsGranted("ROLE_SCHOOL_AUTHORITY")
     * @throws Exception
     */
    public function linkSchoolAuthority(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $error = 'Etwas ist schief gelaufen.';
        $success = false;
        $message = '';
        $schoolCode = $request->request->get('schoolCode', null);
        if ($schoolCode) {
            $schoolAuthority = $this->getUser()->getSchoolAuthority();
            //check if school code exist
            /** @var School $school * */
            if ($school = $entityManager->getRepository(School::class)->findOneBy(['code' => $schoolCode])) {
                //check if the school has already a school authority assigned -> column not null
//                if (!$school->getSchoolAuthority()) {
                //check if the school is already assigned
                $saveRecord = false;
                if ($school->getSchoolAuthority()) {
                    if ($school->getSchoolAuthority()->getId() === $schoolAuthority->getId()) {
                        $error = 'Die Schule ist schon verbunden!';
                    } else {
                        $saveRecord = true;
                    }
                } else {
                    $saveRecord = true;
                }

                if ($saveRecord) {
                    $school->setSchoolAuthority($schoolAuthority);
                    $entityManager->persist($school);
                    $entityManager->flush();
                    $success = true;
                    $message = 'Die Schule wurde erfolgreich verbunden!';
                    $error = null;
                }


//                } else {
//                    //if the user tries to assign the same school
//                    //assign an error
//                    if ($school->getSchoolAuthority()->getId() === $this->getUser()->getSchoolAuthority()->getId()) {
//                        $error = 'Die Schule ist bereits mit ihrem Schulträger verbunden.';
//                    }else{
//                        $error = 'Die Schule ist bereits mit einem Schulträger verbunden.';
//                    }
//                }

            } else {
                //no school found under the code
                $error = 'der Schulcode wurde nicht gefunden';
            }
        }
        $toReturn = [
            'success' => $success,
            'error' => $error,
            'message' => $message,
        ];

        return new JsonResponse($toReturn);
    }


    /**
     * @Route("/send-invitation", name="send_invitation")
     * @IsGranted("ROLE_PUBLIC")
     * @throws Exception
     */
    public function sendInvitation(Request $request, MailerInterface $mailer, EntityManagerInterface $entityManager)
    {
        $errors = [];
        $mails = explode(",", $request->get('recipients'));
        $typeOfUser = $request->get('typeOfUser');
        $bcc = [];

        try {
            $user = $this->getUser();
            $inviteForm = $this->createForm(FormType::class, null, ['csrf_protection' => false]);
            $inviteForm->add('recipients', TextType::class, ['constraints' => [new NotBlank()]]);
            //define template
            $emailTemplate = 'mail/invite.html.twig';
            $userTypeVar = 'school';
            $userDataValues = $user->getSchool();

            //If the user is school authority
            if ($typeOfUser === 'schoolAuthority') {
                $emailTemplate = 'mail/invitation_from_school_authority.html.twig';
                $inviteForm->add('typeOfUser', TextType::class);
                $userTypeVar = 'schoolAuthority';
                $userDataValues = $user->getSchoolAuthority();

            }
            $inviteForm->submit($request->request->all());
            $inviteForm->handleRequest($request);

            if ($inviteForm->isSubmitted() && $inviteForm->isValid()) {

                foreach ($mails as $mail) {
                    if (filter_var(trim($mail), FILTER_VALIDATE_EMAIL)) {
                        $bcc[] = trim($mail);
                        //If the user is school authority
                        if ($typeOfUser === 'schoolAuthority') {
                            //verify if the email is already in the database
                            //1) when the emails are in db and does not below to school authority
                            /**@var User $user_ * */
                            if ($user_ = $entityManager->getRepository(User::class)->findOneBy(['email' => trim($mail)])) {

                                //check if the user has the school authority
                                if ($school = $user_->getSchool()) {
                                    if ($school->getSchoolAuthority()) {
                                        if ($school->getSchoolAuthority()->getId() === $user->getSchoolAuthority()->getId()) {
                                            return new JsonResponse(['error' => 'Die Schule ist bereits in den Schulträger eingebunden.<i>: ' . trim($mail)]);
                                        }
                                    }
                                }
                                return new JsonResponse(['error' => 'Die E-Mail-Adresse wird bereits von einem anderen Benutzer verwendet: <i> ' . trim($mail)]);
                            }

                        }

                    } else {
                        return new JsonResponse(['error' => 'Die E-Mailadresse <i>' . $mail . '</i> hat kein gültiges Format'], 200);
                    }
                }

                if (count($bcc) <= 0) {
                    return new JsonResponse(['Kein gültiger Empfänger'], 200);
                }
                $email = (new TemplatedEmail())
                    ->subject('Einladung von schultransform.org')
                    ->from(new \Symfony\Component\Mime\Address('support@schultransform.org', 'Schultransform'))
                    ->bcc(...$bcc)
                    ->htmlTemplate($emailTemplate)
                    ->context(
                        ['data' => $inviteForm->getData(), $userTypeVar => $userDataValues]
                    );

                 $mailer->send($email);

                return new JsonResponse(['message' => 'Kolleg:innen erfolgreich eingeladen'], 200);
            };
            foreach ($inviteForm->getErrors() as $key => $error) {
                $errors[] = $error->getMessage();
            }
            if (count($errors) > 0) {
                return new JsonResponse(['error' => implode(',', $errors)], 200);
            } else {
                return new JsonResponse(['message' => 'Kolleg:innen erfolgreich eingeladen'], 200);
            }

        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 200);
        }
    }


    /**
     * @Route("/print-colleague-invitation", name="print_colleague_invitation")
     * @IsGranted("ROLE_TEACHER")
     * @throws Exception
     */
    public function printColleagueInvitation()
    {
        $school = $this->getUser()->getSchool();
        if (!$school) {
            throw new Exception('Ihnen ist keine Schule zugeordnet');
            //TODO Verlinkung irgendwohin wo man Schulen verknüpfen kann?
        }
        $dompdf = new Dompdf(array('isPhpEnabled' => true));
        $content = $this->render('pdf/colleague-invitation.html.twig', [
            'school' => $school
        ])->getContent();
        $dompdf->loadHtml($content);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream($this->getUser()->getSchool()->getName() . "-Einladung.pdf");
    }


    /**
     * @Route("/print-school-invitation", name="print_school_invitation")
     * @IsGranted("ROLE_SCHOOL_AUTHORITY_LITE")
     * @throws Exception
     */
    public function printSchoolInvitation()
    {
        $schoolAuthority = $this->getUser()->getSchoolAuthority();
        $dompdf = new Dompdf(array('isPhpEnabled' => true));
        $content = $this->render('pdf/school-invitation.html.twig', [
            'schoolAuthority' => $schoolAuthority
        ])->getContent();
        $dompdf->loadHtml($content);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream($this->getUser()->getSchoolAuthority()->getName() . "-Einladung.pdf");
    }

    /**
     * @Route("/PublicUser/user-logout", name="user_logout")
     *
     * @return Response
     * @throws Exception
     */
    public function logoutAction(): Response
    {
        // controller can be blank: it will never be executed!
        throw new Exception('Don\'t forget to activate logout in security.yaml');
    }
}
