<?php

namespace Trollfjord\Menu;

use Doctrine\ORM\EntityManagerInterface;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use ReflectionException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;
use Trollfjord\Bundle\ContentTreeBundle\Service\SiteService;
use Trollfjord\Bundle\PublicUserBundle\Entity\User;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Category;
use function is_null;

/**
 * Class Builder
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Menu
 */
class Builder
{
    /**
     * @var FactoryInterface
     */
    private FactoryInterface $factory;

    /**
     * @var SiteService
     */
    private SiteService $siteService;

    /**
     * @var Security
     */
    private Security $security;

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * @var ItemInterface|null
     */
    private ?ItemInterface $frontendMenu = null;

    /**
     * Add any other dependency you need...
     * @param FactoryInterface       $factory
     * @param SiteService            $siteService
     * @param Security               $security
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        FactoryInterface       $factory,
        SiteService            $siteService,
        Security               $security,
        EntityManagerInterface $entityManager
    ) {
        $this->factory = $factory;
        $this->siteService = $siteService;
        $this->security = $security;
        $this->entityManager = $entityManager;
    }

    /**
     * @param array $options
     * @return ItemInterface
     * @throws ReflectionException
     */
    public function createMainMenu(array $options): ItemInterface
    {
        if (! is_null($this->frontendMenu)) {
            return $this->frontendMenu;
        }
        $menu = $this->factory->createItem('home', ['label' => '<i class="fad  fa-home-lg"></i>', 'route' => 'home']);
        $tree = $this->siteService->getContentTreePublished();
        $addChildren = function ($menu, $children) use (&$addChildren) {
            foreach ($children as $site) {
                if ($site['menuEntry']) {
                    $addChildren($menu->addChild($site['name'], ['route' => $site['route']]), $site['children']);
                }
            }
            return $menu;
        };
        $menu = $addChildren($menu, $tree);

        // Add additional Items here :D

        if ($this->security->getUser() && $this->security->getUser() instanceof User) {

            $type = $this->security->getUser()->getUserType();
            switch ($type) {
                case 'SCHOOL_AUTHORITY':
                    //SCHOOL AUTHORITY MENU
                    $dashboard_school_authority = $menu->addChild('dashboard_school_authority',
                        [
                            'label' => 'Mein Schultransform',
                            'route' => 'dashboard_school_authority_home',
                            'displayChildren' => false
                        ]);
                    break;
                case 'SCHOOL':
                    //SCHOOL MENU
                    $dashboard_school = $menu->addChild('dashboard_school',
                        [
                            'label' => 'Mein Schultransform',
                            'route' => 'dashboard_school_home',
                            'displayChildren' => false
                        ]);
                    break;
                case 'TEACHER':
                    //TEACHER MENU
                    $dashboard = $menu->addChild('dashboard',
                        [
                            'label' => 'Mein Schultransform',
                            'route' => 'dashboard_teacher_home',
                            'displayChildren' => false
                        ]);

                    /** @var Category $category */
                    foreach ($this->entityManager->getRepository(Category::class)->findBy(['parent' => null]) as $category) {
                        $dashboard->addChild('dashboard_teacher_category_overview' . $category->getId(), [
                            'label' => $category->getName(),
                            'route' => 'dashboard_teacher_category_overview',
                            'routeParameters' => ['category' => $category->getId()],
                            'display' => false
                        ]);
                    }
                    break;
            }


        }

        $this->frontendMenu = $menu;

        return $this->frontendMenu;
    }


    /**
     * @param array $options
     * @return ItemInterface
     * @throws ReflectionException
     */
    public function createDashboardMenu(array $options): ItemInterface
    {
        $menu = $this->factory->createItem('home', ['label' => '<i class="fad  fa-home-lg"></i>', 'route' => 'home']);

        if ($this->security->getUser()) {
            $dashboard = $menu->addChild('dashboard',
                [
                    'label' => 'Mein Schultransform',
                    'route' => 'dashboard_teacher_home'
                ]);

        }
        return $menu;
    }

    public function createUserMenu(array $options): ItemInterface
    {

        $menu = $this->factory->createItem('home', ['label' => '<i class="fad  fa-home-lg"></i>', 'route' => 'home']);

        $user = $this->security->getUser();
        if (! $user || ! ($user instanceof User)) {
            return $menu;
        }

        $translator = new Translator('de');
        $translator->addLoader('yaml', new YamlFileLoader());
        $translator->addResource('yaml', __DIR__ . '/../../translations/dashboard.de.yaml', 'de');
        //Todo: strange way to load the translations, guess i'm doing it wrong

        $menu->addChild($translator->trans("linkDashboard"), ['route' => 'user_success', 'attributes' => ['class' => '', 'icon' => 'fad fa-tachometer-alt']]);

        //Individual Links
        $type = $user->getUserType();
        switch ($type) {
            case 'TEACHER':
                if (! $user->getSchool()) {
                    $menu->addChild($translator->trans("linkSchool"), ['route' => '', 'attributes' => ['class' => 'school-color', 'icon' => 'fad fa-school', 'modal' => "modal-profil"]]);
                } else {
                    $menu->addChild($translator->trans("inviteColleague"), ['route' => 'print_colleague_invitation', 'attributes' => ['icon' => 'fad fa-users', 'modal' => "modal-profil"]]);
                }
                break;
            case 'SCHOOL':
                $menu->addChild($translator->trans("inviteTeacher"), ['route' => 'print_teacher_invitation', 'attributes' => ['icon' => 'fad fa-users', 'modal' => "modal-profil"]]);
                $menu->addChild($translator->trans("editSchool"), ['route' => 'user_edit_school', 'attributes' => ['class' => '', 'icon' => 'fad fa-edit']]);

                break;
            case 'SCHOOL_AUTHORITY':
                $menu->addChild($translator->trans("inviteSchool"), ['route' => 'print_school_invitation', 'attributes' => ['class' => 'school-color', 'icon' => 'fad fa-school', 'modal' => "modal-profil"]]);
                $menu->addChild($translator->trans("editSchoolAuthority"), ['route' => 'user_edit_school_authority', 'attributes' => ['class' => '', 'icon' => 'fad fa-edit']]);
                break;
            default:

        }
        $lastLogin = $user->getLastLogin() ? $user->getLastLogin()->format('d.m.Y H:i') : '';
        $menu->addChild($translator->trans("logout"), ['route' => 'user_logout', 'attributes' => ['class' => 'theme-color', 'icon' => 'fad fa-sign-out']]);
        $menu->addChild($translator->trans('lastLogin') . ' ' . $lastLogin, ['route' => '', 'attributes' => ['class' => 'smaller-font white-bg-color']]);

        return $menu;
    }

}
