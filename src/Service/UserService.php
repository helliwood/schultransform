<?php

namespace Trollfjord\Service;

use Dompdf\Dompdf;
use Symfony\Component\Security\Core\Security;
use Trollfjord\Bundle\PublicUserBundle\Entity\User;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class UserService
{

    /**
     * @var Environment
     */
    protected Environment $twig;

    /**
     * @var Security
     */
    protected Security $security;

    /**
     * @param Environment $twig
     * @param Security    $security
     */
    public function __construct(Environment $twig, Security $security)
    {
        $this->twig = $twig;
        $this->security = $security;
    }

    /**
     * @param User|null $user
     * @return Dompdf
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function getSchoolFaxDompdf(?User $user = null): Dompdf
    {
        $dompdf = new Dompdf(array('isPhpEnabled' => true));
        $dompdf->loadHtml($this->twig->render('pdf/school-fax.html.twig', ['user' => $user ?? $this->security->getUser()]));
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return $dompdf;
    }

    /**
     * @param User|null $user
     * @return Dompdf
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function getSchoolAuthorityFaxDompdf(?User $user = null): Dompdf
    {
        $dompdf = new Dompdf(array('isPhpEnabled' => true));
        $dompdf->loadHtml($this->twig->render('pdf/school-authority-fax.html.twig', ['user' => $user ?? $this->security->getUser()]));
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return $dompdf;
    }
}