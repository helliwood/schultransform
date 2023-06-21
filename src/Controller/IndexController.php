<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2020-09-04
 * Time: 6:30
 */

namespace Trollfjord\Controller;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Trollfjord\Bundle\ContentTreeBundle\Controller\AbstractSiteContentController;
use Trollfjord\Bundle\ContentTreeBundle\Service\SiteService;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\ElementNotFoundException;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\Exception;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\TemplateEngine;
use Trollfjord\Bundle\QuestionnaireBundle\Service\QuestionnaireService;
use Trollfjord\Entity\School;
use Trollfjord\Form\ContactType;
use Trollfjord\Form\OrdersType;
use Trollfjord\Form\RegistrationType;
use Trollfjord\Service\ChartService;
use Trollfjord\Bundle\TFSecurityBundle\Entity\SpamMail;
use Symfony\Component\HttpFoundation\BinaryFileResponse;


use Trollfjord\Service\Dashboard\SchoolService;
use function md5;
use function time;


class IndexController extends AbstractSiteContentController
{
    /**
     * @Route("/", name="home")
     * @param Request      $request
     * @param SiteService  $siteService
     * @param ChartService $chartService
     * @return Response
     * @throws ElementNotFoundException
     * @throws Exception
     */
    public function index(Request $request, SiteService $siteService, ChartService $chartService): Response
    {
        if (false !== ($res = $chartService->execute($request))) {
            return $res;
        }

        $site = $siteService->getSiteByRoute($request->attributes->get('_route'));
        if ($site && $site->getPublishedSite() && $site->getPublishedSite()->isPublished()) {
            return $this->render('frontend/index/index.html.twig', [
                'site' => $site->getPublishedSite(),
                'siteContent' => $siteService->renderSiteContentsBySitePublished($site->getPublishedSite(), TemplateEngine::RENDER_MODE_PUBLIC)
            ]);
        }
        throw $this->createNotFoundException('Die Seite wurde nicht gefunden.');
    }





    /**
     * @param Request         $request
     * @param MailerInterface $mailer
     * @return Response
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws TransportExceptionInterface
     */
    public function contactForm(Request $request, MailerInterface $mailer): Response
    {
        $submitted = $request->query->get('submitted', false);
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);
        $entityManager = $this->getDoctrine()->getManager();


        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();



            $subject = 'Kontaktanfrage von schultransform.org';
            //Honeypotfield is set and filled, so we have a possible spam-attack
            if (isset($formData['phonefax']) && $formData['phonefax']!="") {

                //save mail as spam
                $spamMail = new SpamMail();
                $body = $this->renderView('mail/contact.html.twig', [
                    'data' => $form->getData(),
                ]);
                $spamMail->setBody($body);
                $spamMail->setSubject($subject);
                $spamMail->setEmailAddress($form->getData()['email']);
                $entityManager->persist($spamMail);
                $entityManager->flush();
                //redirect to normal THANK YOU Page (we won't show the spammer that we have detected him)
                return $this->redirect($this->generateUrl($request->attributes->get('_route'), ['submitted' => true, 'vtm' => md5(time())], UrlGeneratorInterface::ABSOLUTE_URL));
            } else {

                $email = (new TemplatedEmail())
                    ->subject($subject)
                    ->from(new \Symfony\Component\Mime\Address('support@schultransform.org', 'Schultransform'))
                    ->to('support@schultransform.org')
                    ->htmlTemplate('mail/contact.html.twig')
                    ->context(
                        ['data' => $form->getData()]
                    );

                $mailer->send($email);


                //mail for user

                $email = (new TemplatedEmail())
                    ->subject('Ihre Kontaktanfrage an schultransform.org')
                    ->from(new \Symfony\Component\Mime\Address('support@schultransform.org', 'Schultransform'))
                    ->to($form->getData()['email'])
                    ->htmlTemplate('mail/contact_user.html.twig')
                    ->context(
                        ['data' => $form->getData()]
                    );

                $mailer->send($email);
                return $this->redirect($this->generateUrl($request->attributes->get('_route'), ['submitted' => true, 'vtrn' => md5(time()) . ''], UrlGeneratorInterface::ABSOLUTE_URL));

            }


            return $this->redirect($this->generateUrl($request->attributes->get('_route'), ['submitted' => true], UrlGeneratorInterface::ABSOLUTE_URL));
        }

        return $this->render('/frontend/index/contact.html.twig', [
            'form' => $form->createView(),
            'submitted' => $submitted
        ]);
    }

    /**
     * @param Request         $request
     * @param MailerInterface $mailer
     * @return Response
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws TransportExceptionInterface
     */
    public function orderForm(Request $request, MailerInterface $mailer): Response
    {
        $submitted = $request->query->get('submitted', false);
        $form = $this->createForm(OrdersType::class);
        $form->handleRequest($request);
        $entityManager = $this->getDoctrine()->getManager();


        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();



            $subject = 'Bestellung Buch Schultransform Handlungsempfehlungen';
            //Honeypotfield is set and filled, so we have a possible spam-attack
            if (isset($formData['phonefax']) && $formData['phonefax']!="") {

                //save mail as spam
                $spamMail = new SpamMail();
                $body = $this->renderView('mail/order.html.twig', [
                    'data' => $form->getData(),
                ]);
                $spamMail->setBody($body);
                $spamMail->setSubject($subject);
                $spamMail->setEmailAddress($form->getData()['email']);
                $entityManager->persist($spamMail);
                $entityManager->flush();
                //redirect to normal THANK YOU Page (we won't show the spammer that we have detected him)
                return $this->redirect($this->generateUrl($request->attributes->get('_route'), ['submitted' => true, 'vtm' => md5(time())], UrlGeneratorInterface::ABSOLUTE_URL));
            } else {

                $email = (new TemplatedEmail())
                    ->subject($subject)
                    ->from(new \Symfony\Component\Mime\Address('support@schultransform.org', 'Schultransform'))
                    ->to('support@schultransform.org')
                    ->htmlTemplate('mail/order.html.twig')
                    ->context(
                        ['data' => $form->getData()]
                    );

                $mailer->send($email);


                //mail for user

                $email = (new TemplatedEmail())
                    ->subject('Ihre Kontaktanfrage an schultransform.org')
                    ->from(new \Symfony\Component\Mime\Address('support@schultransform.org', 'Schultransform'))
                    ->to($form->getData()['email'])
                    ->htmlTemplate('mail/order_user.html.twig')
                    ->context(
                        ['data' => $form->getData()]
                    );

                $mailer->send($email);
                return $this->redirect($this->generateUrl($request->attributes->get('_route'), ['submitted' => true, 'vtrn' => md5(time()) . ''], UrlGeneratorInterface::ABSOLUTE_URL));

            }


            return $this->redirect($this->generateUrl($request->attributes->get('_route'), ['submitted' => true], UrlGeneratorInterface::ABSOLUTE_URL));
        }

        return $this->render('/frontend/index/order.html.twig', [
            'form' => $form->createView(),
            'submitted' => $submitted
        ]);
    }


    /**
     * @Route("/publicdataservice/globalindex.svg", name="global_index_svg")
     *
     * @throws \Exception
     */
    public function globalIndexSvg(QuestionnaireService $questionnaireService)
    {

        //TODO: eigener controller?
        $value=$questionnaireService->getSchoolIndex();
        $svg = $this->createIndexSvg('globalindex.svg',$value);
        return new Response($svg, 200, ['Content-Type' => 'image/svg+xml']);

    }

    public function createIndexSvg($filename,$value){
        $filename= sys_get_temp_dir().'/'.$filename;
        $now   = time();

        $cacheTimeInDays = 1;
        if(file_exists($filename)){
            if($now - filemtime($filename) <= 60 * 60 * 24 * $cacheTimeInDays){
                return file_get_contents($filename);
            }
        }
        $rot = (int)($value*25.7142857143);
        $mask ='<mask id="borderMask"><path xmlns="http://www.w3.org/2000/svg" d="M 43.75 140 A 5 5 0 1 1 33.75 140 A 101.25 101.25 0 1 1 236.25 140 M 236.25 140 A 5 5 0 1 1 226.25 140 A 91.25 91.25 0 0 0 43.75 140 M 43.75 140 Z" fill="white" fill-opacity="1" stroke="none"/></mask>';
        $value=number_format($value,2,',','.');
        $arrow ='<g style="transform: matrix(0,-1,1,0,135,140) rotate('.$rot.'deg)"><path d="M -0.0002 -70.875 C 1.0259 -70.875 1.869 -70.3168 1.9099 -69.6102 L 5.9951 0.7621 C 6.1275 3.044 3.5508 4.9679 0.2398 5.0592 C 0.1597 5.0614 0.0799 5.0625 -0.0002 5.0625 C -3.3138 5.0625 -6 3.2112 -6 0.9275 C -6 0.8723 -5.9984 0.8172 -5.9953 0.7621 L -1.9101 -69.6102 C -1.8692 -70.3168 -1.0261 -70.875 -0.0002 -70.875 Z" fill="#006292" fill-opacity="1" stroke="none" filter="url(#zr2323-shadow-0)"></path></g>';
        $blueBar = '<g mask="url(#borderMask)"><g style="transform: rotate(-'.(180-$rot).'deg);transform-origin: center;"><path d="M 43.75 140 A 5 5 0 1 1 33.75 140 A 101.25 101.25 0 1 1 236.25 140 M 236.25 140 A 5 5 0 1 1 226.25 140 A 91.25 91.25 0 0 0 43.75 140 M 43.75 140 Z" fill="#006292" fill-opacity="1" stroke="none" filter="url(#zr2323-shadow-0)"></path></g></g>';
        $svg ='<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" baseProfile="full" style="user-select: none; position: absolute; left: 0px; top: 0px;" width="270" height="280"><g><rect width="270" height="280" x="0" y="0" id="0" fill="none" fill-opacity="1"></rect></g><g><path d="M 43.75 140 A 5 5 0 1 1 33.75 140 A 101.25 101.25 0 1 1 236.25 140 M 236.25 140 A 5 5 0 1 1 226.25 140 A 91.25 91.25 0 0 0 43.75 140 M 43.75 140 Z" fill="#E6EBF8" fill-opacity="1" stroke="none"></path><path d="M 53.75 140 L 65.75 140" fill="none" stroke="#666" stroke-width="2" paint-order="fill" stroke-opacity="1" stroke-dasharray="none" stroke-linecap="butt" stroke-miterlimit="10"></path><text xml:space="preserve" style="font: 14px sans-serif;" fill="#666" fill-opacity="1" stroke="none" dominant-baseline="central" text-anchor="start" x="75.75" y="140">0</text><path d="M 53.75 140 L 59.75 140" fill="none" stroke="#666" stroke-width="1" paint-order="fill" stroke-opacity="1" stroke-dasharray="none" stroke-linecap="butt" stroke-miterlimit="10"></path><path d="M 55.7871 121.9202 L 61.6367 123.2553" fill="none" stroke="#666" stroke-width="1" paint-order="fill" stroke-opacity="1" stroke-dasharray="none" stroke-linecap="butt" stroke-miterlimit="10"></path><path d="M 61.7963 104.7469 L 67.2021 107.3502" fill="none" stroke="#666" stroke-width="1" paint-order="fill" stroke-opacity="1" stroke-dasharray="none" stroke-linecap="butt" stroke-miterlimit="10"></path><path d="M 61.7963 104.7469 L 72.6079 109.9536" fill="none" stroke="#666" stroke-width="2" paint-order="fill" stroke-opacity="1" stroke-dasharray="none" stroke-linecap="butt" stroke-miterlimit="10"></path><text xml:space="preserve" style="font: 14px sans-serif;" fill="#666" fill-opacity="1" stroke="none" dominant-baseline="central" text-anchor="start" x="81.61759457678167" y="114.29238845728467">1</text><path d="M 61.7963 104.7469 L 67.2021 107.3502" fill="none" stroke="#666" stroke-width="1" paint-order="fill" stroke-opacity="1" stroke-dasharray="none" stroke-linecap="butt" stroke-miterlimit="10"></path><path d="M 71.4762 89.3415 L 76.1672 93.0824" fill="none" stroke="#666" stroke-width="1" paint-order="fill" stroke-opacity="1" stroke-dasharray="none" stroke-linecap="butt" stroke-miterlimit="10"></path><path d="M 84.3415 76.4762 L 88.0824 81.1672" fill="none" stroke="#666" stroke-width="1" paint-order="fill" stroke-opacity="1" stroke-dasharray="none" stroke-linecap="butt" stroke-miterlimit="10"></path><path d="M 84.3415 76.4762 L 91.8233 85.8582" fill="none" stroke="#666" stroke-width="2" paint-order="fill" stroke-opacity="1" stroke-dasharray="none" stroke-linecap="butt" stroke-miterlimit="10"></path><text xml:space="preserve" style="font: 14px sans-serif;" fill="#666" fill-opacity="1" stroke="none" dominant-baseline="central" text-anchor="start" x="98.05822923987004" y="93.67648466376923">2</text><path d="M 84.3415 76.4762 L 88.0824 81.1672" fill="none" stroke="#666" stroke-width="1" paint-order="fill" stroke-opacity="1" stroke-dasharray="none" stroke-linecap="butt" stroke-miterlimit="10"></path><path d="M 99.7469 66.7963 L 102.3502 72.2021" fill="none" stroke="#666" stroke-width="1" paint-order="fill" stroke-opacity="1" stroke-dasharray="none" stroke-linecap="butt" stroke-miterlimit="10"></path><path d="M 116.9202 60.7871 L 118.2553 66.6367" fill="none" stroke="#666" stroke-width="1" paint-order="fill" stroke-opacity="1" stroke-dasharray="none" stroke-linecap="butt" stroke-miterlimit="10"></path><path d="M 116.9202 60.7871 L 119.5904 72.4862" fill="none" stroke="#666" stroke-width="2" paint-order="fill" stroke-opacity="1" stroke-dasharray="none" stroke-linecap="butt" stroke-miterlimit="10"></path><text xml:space="preserve" style="font: 14px sans-serif;" fill="#666" fill-opacity="1" stroke="none" dominant-baseline="central" text-anchor="middle" x="121.81563466308837" y="89.23552120322695">3</text><path d="M 116.9202 60.7871 L 118.2553 66.6367" fill="none" stroke="#666" stroke-width="1" paint-order="fill" stroke-opacity="1" stroke-dasharray="none" stroke-linecap="butt" stroke-miterlimit="10"></path><path d="M 135 58.75 L 135 64.75" fill="none" stroke="#666" stroke-width="1" paint-order="fill" stroke-opacity="1" stroke-dasharray="none" stroke-linecap="butt" stroke-miterlimit="10"></path><path d="M 153.0798 60.7871 L 151.7447 66.6367" fill="none" stroke="#666" stroke-width="1" paint-order="fill" stroke-opacity="1" stroke-dasharray="none" stroke-linecap="butt" stroke-miterlimit="10"></path><path d="M 153.0798 60.7871 L 150.4096 72.4862" fill="none" stroke="#666" stroke-width="2" paint-order="fill" stroke-opacity="1" stroke-dasharray="none" stroke-linecap="butt" stroke-miterlimit="10"></path><text xml:space="preserve" style="font: 14px sans-serif;" fill="#666" fill-opacity="1" stroke="none" dominant-baseline="central" text-anchor="middle" x="148.18436533691164" y="89.23552120322695">4</text><path d="M 153.0798 60.7871 L 151.7447 66.6367" fill="none" stroke="#666" stroke-width="1" paint-order="fill" stroke-opacity="1" stroke-dasharray="none" stroke-linecap="butt" stroke-miterlimit="10"></path><path d="M 170.2531 66.7963 L 167.6498 72.2021" fill="none" stroke="#666" stroke-width="1" paint-order="fill" stroke-opacity="1" stroke-dasharray="none" stroke-linecap="butt" stroke-miterlimit="10"></path><path d="M 185.6585 76.4762 L 181.9176 81.1672" fill="none" stroke="#666" stroke-width="1" paint-order="fill" stroke-opacity="1" stroke-dasharray="none" stroke-linecap="butt" stroke-miterlimit="10"></path><path d="M 185.6585 76.4762 L 178.1767 85.8582" fill="none" stroke="#666" stroke-width="2" paint-order="fill" stroke-opacity="1" stroke-dasharray="none" stroke-linecap="butt" stroke-miterlimit="10"></path><text xml:space="preserve" style="font: 14px sans-serif;" fill="#666" fill-opacity="1" stroke="none" dominant-baseline="central" text-anchor="end" x="171.94177076012997" y="93.67648466376923">5</text><path d="M 185.6585 76.4762 L 181.9176 81.1672" fill="none" stroke="#666" stroke-width="1" paint-order="fill" stroke-opacity="1" stroke-dasharray="none" stroke-linecap="butt" stroke-miterlimit="10"></path><path d="M 198.5238 89.3415 L 193.8328 93.0824" fill="none" stroke="#666" stroke-width="1" paint-order="fill" stroke-opacity="1" stroke-dasharray="none" stroke-linecap="butt" stroke-miterlimit="10"></path><path d="M 208.2037 104.7469 L 202.7979 107.3502" fill="none" stroke="#666" stroke-width="1" paint-order="fill" stroke-opacity="1" stroke-dasharray="none" stroke-linecap="butt" stroke-miterlimit="10"></path><path d="M 208.2037 104.7469 L 197.3921 109.9536" fill="none" stroke="#666" stroke-width="2" paint-order="fill" stroke-opacity="1" stroke-dasharray="none" stroke-linecap="butt" stroke-miterlimit="10"></path><text xml:space="preserve" style="font: 14px sans-serif;" fill="#666" fill-opacity="1" stroke="none" dominant-baseline="central" text-anchor="end" x="188.38240542321833" y="114.29238845728469">6</text><path d="M 208.2037 104.7469 L 202.7979 107.3502" fill="none" stroke="#666" stroke-width="1" paint-order="fill" stroke-opacity="1" stroke-dasharray="none" stroke-linecap="butt" stroke-miterlimit="10"></path><path d="M 214.2129 121.9202 L 208.3633 123.2553" fill="none" stroke="#666" stroke-width="1" paint-order="fill" stroke-opacity="1" stroke-dasharray="none" stroke-linecap="butt" stroke-miterlimit="10"></path><path d="M 216.25 140 L 210.25 140" fill="none" stroke="#666" stroke-width="1" paint-order="fill" stroke-opacity="1" stroke-dasharray="none" stroke-linecap="butt" stroke-miterlimit="10"></path><path d="M 216.25 140 L 204.25 140" fill="none" stroke="#666" stroke-width="2" paint-order="fill" stroke-opacity="1" stroke-dasharray="none" stroke-linecap="butt" stroke-miterlimit="10"></path><text xml:space="preserve" style="font: 14px sans-serif;" fill="#666" fill-opacity="1" stroke="none" dominant-baseline="central" text-anchor="end" x="194.25" y="140">7</text><path d="M 107.6875 156.4375 L 162.3125 156.4375 A 8 8 0 0 1 170.3125 164.4375 L 170.3125 186.4375 A 8 8 0 0 1 162.3125 194.4375 L 107.6875 194.4375 A 8 8 0 0 1 99.6875 186.4375 L 99.6875 164.4375 A 8 8 0 0 1 107.6875 156.4375" fill="#fff" fill-opacity="1" stroke="#b3b3b3" stroke-width="2" paint-order="stroke" stroke-opacity="1" stroke-dasharray="none" stroke-linecap="butt" stroke-miterlimit="10"></path><text xml:space="preserve" style="font: 20px sans-serif;" fill="#777" fill-opacity="1" stroke="none" dominant-baseline="central" text-anchor="middle" x="135" y="176.4375">'.$value.'</text><path fill="rgb(0,98,146)" fill-opacity="1" stroke="none" transform="matrix(0.782,-0.623,0.623,0.782,135,140)" filter="url(#zr2323-shadow-0)"></path><defs><filter id="zr2323-shadow-0" x="-100%" y="-100%" width="300%" height="300%"><feDropShadow dx="2" dy="2" flood-color="rgb(0,0,0)" flood-opacity="0.3" stdDeviation="5 5"></feDropShadow></filter><filter id="zr2323-shadow-1" x="-100%" y="-100%" width="300%" height="300%"><feDropShadow dx="2.0000000000000004" dy="2.0000000000000004" flood-color="rgb(0,0,0)" flood-opacity="0.3" stdDeviation="5.000000000000001 5.000000000000001"></feDropShadow></filter></defs></g>';
        $svg.=$mask.$arrow.$blueBar.'</svg>';

        $fileSystem = new Filesystem();
        $fileSystem->dumpFile($filename, $svg);

        return $svg;
    }

    /**
     * @Route("/publicdataservice/{schoolCode}/index.svg", name="school_index_svg", defaults={"schoolCode":null})
     *
     * @throws ElementNotFoundException
     * @throws Exception
     */
    public function SchoolIndexSvg(SchoolService $schoolService,string $schoolCode=null)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $school = $entityManager->getRepository(School::class)->findOneBy(['code' => $schoolCode]);
        if(!$school){
            throw $this->createNotFoundException('Inhalt konnte nicht geladen werden.');

        }

        $value = $schoolService->getMeanForAllCategoriesAllUsers($school->getId());
        $svg = $this->createIndexSvg($schoolCode.'.svg',$value);
        return new Response($svg, 200, ['Content-Type' => 'image/svg+xml']);

    }


    /**
     * @Route("/registrierungs-form/{submitted?}", name="register_form", defaults={"submitted":false})
     * @param Request         $request
     * @param MailerInterface $mailer
     * @param bool|null       $submitted
     * @return Response
     * @throws TransportExceptionInterface
     */
    public function registerForm(Request $request, MailerInterface $mailer, ?bool $submitted = false): Response
    {
        $submitted = $request->query->get('submitted', false);
        $form = $this->createForm(RegistrationType::class);
        $form->handleRequest($request);
        $subject = 'Kontaktanfrage von schultransform.org';
        $entityManager = $this->getDoctrine()->getManager();

        if ($form->isSubmitted() && $form->isValid()) {

            $formData = $form->getData();
            if (isset($formData['phonefax']) && $formData['phonefax']!="") {
                $spamMail = new SpamMail();
                $body = $this->renderView('mail/register.html.twig', [
                    'data' => $form->getData(),
                ]);
                $spamMail->setBody($body);
                $spamMail->setSubject("Anmeldung | Kooperation@MINT: Schultransformation gestalten – MINT-Cluster stärken");
                $spamMail->setEmailAddress($form->getData()['email']);
                $entityManager->persist($spamMail);
                $entityManager->flush();
                return $this->redirect($this->generateUrl($request->attributes->get('_route'), ['submitted' => true,"v"=>"VqW0FSPvrxYo7AtHj9XQ"], UrlGeneratorInterface::ABSOLUTE_URL));
            } else {
                                $email = (new TemplatedEmail())
                    ->subject('Anmeldung | Kooperation@MINT: Schultransformation gestalten – MINT-Cluster stärken')
                    ->from(new \Symfony\Component\Mime\Address('support@schultransform.org', 'Schultransform'))
                    ->to('support@schultransform.org')
                    ->htmlTemplate('mail/register.html.twig')
                    ->context(
                        ['data' => $form->getData()]
                    );

                $mailer->send($email);


                //mail for user

                $email = (new TemplatedEmail())
                    ->subject('Ihre Teilnahme | Kooperation@MINT: Schultransformation gestalten – MINT-Cluster stärken')
                    ->from(new \Symfony\Component\Mime\Address('support@schultransform.org', 'Schultransform'))
                    ->to($form->getData()['email'])
                    ->htmlTemplate('mail/register_user.html.twig')
                    ->context(
                        ['data' => $form->getData()]
                    );

                $mailer->send($email);
            }

                return $this->redirect($this->generateUrl($request->attributes->get('_route'), ['submitted' => true,'v'=>"Iyaab5RXFAQcwGHlUE66"], UrlGeneratorInterface::ABSOLUTE_URL));
        }

        return $this->render('/frontend/index/register.html.twig', [
            'form' => $form->createView(),
            'submitted' => $submitted
        ]);
    }
}
