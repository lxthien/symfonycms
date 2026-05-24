<?php

namespace App\Controller;

use App\Controller\LegacyController as AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_security_login", methods={"GET", "POST"})
     */
    public function loginAction(AuthenticationUtils $authenticationUtils)
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('admin_dashboard_index');
        }

        return $this->render('security/login.html.twig', array(
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'csrf_token' => $this->get('security.csrf.token_manager')->getToken('authenticate')->getValue(),
        ));
    }

    /**
     * @Route("/login_check", name="app_security_check", methods={"POST"})
     */
    public function checkAction()
    {
        throw new \LogicException('This route is intercepted by the security firewall.');
    }

    /**
     * @Route("/logout", name="app_security_logout", methods={"GET"})
     */
    public function logoutAction()
    {
        throw new \LogicException('This route is intercepted by the security firewall.');
    }

    /**
     * @Route("/register", name="app_registration_register", methods={"GET", "POST"})
     */
    public function registerAction()
    {
        $this->addFlash('warning', 'Đăng ký tài khoản public đang tắt. Vui lòng liên hệ quản trị viên.');

        return $this->redirectToRoute('app_security_login');
    }

    /**
     * @Route("/resetting/request", name="app_resetting_request", methods={"GET", "POST"})
     */
    public function resettingRequestAction()
    {
        $this->addFlash('warning', 'Chức năng quên mật khẩu đang được xử lý bởi quản trị viên.');

        return $this->redirectToRoute('app_security_login');
    }

    /**
     * @Route("/profile", name="app_profile_show", methods={"GET"})
     */
    public function profileAction()
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_security_login');
        }

        return $this->redirectToRoute('admin_user_edit', array('id' => $this->getUser()->getId()));
    }
}
