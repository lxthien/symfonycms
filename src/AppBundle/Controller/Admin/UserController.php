<?php

namespace AppBundle\Controller\Admin;

use AppBundle\Entity\User;
use AppBundle\Form\AdminUserCreateType;
use AppBundle\Form\AdminUserPasswordType;
use AppBundle\Form\AdminUserType;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Controller used to manage users in the backend.
 * @Route("/admin/user")
 * @Security("is_granted('CMS_USER_MANAGE')")
 */

class UserController extends Controller
{
    /**
     * Lists all users entities.
     *
     * @Route("/", name="admin_user_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $users = $em->getRepository(User::class)->findBy([], ['id' => 'DESC']);

        return $this->render('admin/user/index.html.twig', [
            'objects' => $users,
            'is_super_admin' => $this->isGranted('ROLE_SUPER_ADMIN'),
        ]);
    }

    /**
     * @Route("/new", name="admin_user_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        $user = new User();
        $user->setEnabled(true);
        $user->setRoles(['ROLE_CONTRIBUTOR']);

        $form = $this->createForm(AdminUserCreateType::class, $user, [
            'allow_super_admin' => $this->isGranted('ROLE_SUPER_ADMIN'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->normalizeUserIdentity($user);

            if ($this->hasDuplicateUser($user)) {
                $this->addDuplicateUserErrors($form);

                return $this->render('admin/user/new.html.twig', [
                    'object' => $user,
                    'form' => $form->createView(),
                ]);
            }

            $this->sanitizeRoles($user);
            $user->setPassword($passwordEncoder->encodePassword($user, $form->get('plainPassword')->getData()));

            try {
                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();
            } catch (UniqueConstraintViolationException $e) {
                $this->addDuplicateUserErrors($form);

                return $this->render('admin/user/new.html.twig', [
                    'object' => $user,
                    'form' => $form->createView(),
                ]);
            }

            $this->addFlash('success', 'Đã tạo user mới.');

            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/new.html.twig', [
            'object' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", requirements={"id": "\d+"}, name="admin_user_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, User $user)
    {
        $this->denyNonSuperAdminManagingSuperAdmin($user);

        $form = $this->createForm(AdminUserType::class, $user, [
            'allow_super_admin' => $this->isGranted('ROLE_SUPER_ADMIN'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->normalizeUserIdentity($user);

            if ($this->hasDuplicateUser($user)) {
                $this->addDuplicateUserErrors($form);

                return $this->render('admin/user/edit.html.twig', [
                    'object' => $user,
                    'form' => $form->createView(),
                    'is_super_admin' => $this->isGranted('ROLE_SUPER_ADMIN'),
                ]);
            }

            $this->sanitizeRoles($user);
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Đã cập nhật phân quyền người dùng.');

            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/edit.html.twig', [
            'object' => $user,
            'form' => $form->createView(),
            'is_super_admin' => $this->isGranted('ROLE_SUPER_ADMIN'),
        ]);
    }

    /**
     * @Route("/{id}/password", requirements={"id": "\d+"}, name="admin_user_password")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     */
    public function passwordAction(Request $request, User $user, UserPasswordEncoderInterface $passwordEncoder)
    {
        $form = $this->createForm(AdminUserPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($passwordEncoder->encodePassword($user, $form->get('plainPassword')->getData()));
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Đã cập nhật mật khẩu user.');

            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/password.html.twig', [
            'object' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/toggle", requirements={"id": "\d+"}, name="admin_user_toggle")
     * @Method("POST")
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     */
    public function toggleAction(Request $request, User $user)
    {
        if (!$this->isCsrfTokenValid('toggle_user_' . $user->getId(), $request->request->get('token'))) {
            return $this->redirectToRoute('admin_user_index');
        }

        if ($user->getId() === $this->getUser()->getId()) {
            $this->addFlash('warning', 'Không thể khóa chính tài khoản đang đăng nhập.');

            return $this->redirectToRoute('admin_user_index');
        }

        $user->setEnabled(!$user->isEnabled());
        $this->getDoctrine()->getManager()->flush();
        $this->addFlash('success', $user->isEnabled() ? 'Đã mở khóa user.' : 'Đã khóa user.');

        return $this->redirectToRoute('admin_user_index');
    }

    /**
     * @Route("/{id}/delete", requirements={"id": "\d+"}, name="admin_user_delete")
     * @Method("POST")
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     */
    public function deleteAction(Request $request, User $user)
    {
        if (!$this->isCsrfTokenValid('delete_user_' . $user->getId(), $request->request->get('token'))) {
            return $this->redirectToRoute('admin_user_index');
        }

        if ($user->getId() === $this->getUser()->getId()) {
            $this->addFlash('warning', 'Không thể xóa chính tài khoản đang đăng nhập.');

            return $this->redirectToRoute('admin_user_index');
        }

        try {
            $em = $this->getDoctrine()->getManager();
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Đã xóa user.');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Không thể xóa user này vì đang liên kết dữ liệu. Hãy khóa tài khoản thay vì xóa.');
        }

        return $this->redirectToRoute('admin_user_index');
    }

    private function sanitizeRoles(User $user)
    {
        $roles = array_values(array_unique($user->getRoles()));

        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            $roles = array_values(array_diff($roles, ['ROLE_SUPER_ADMIN']));
        }

        if (!$roles) {
            $roles = ['ROLE_CONTRIBUTOR'];
        }

        $user->setRoles($roles);
    }

    private function normalizeUserIdentity(User $user)
    {
        $username = trim((string) $user->getUsername());
        $email = trim((string) $user->getEmail());

        $user->setUsername($username);
        $user->setEmail($email);
        $user->setUsernameCanonical(mb_strtolower($username, 'UTF-8'));
        $user->setEmailCanonical(mb_strtolower($email, 'UTF-8'));
    }

    private function hasDuplicateUser(User $user)
    {
        $duplicate = $this->getDoctrine()->getRepository(User::class)->createQueryBuilder('u')
            ->where('u.id != :id')
            ->andWhere('u.usernameCanonical = :username OR u.emailCanonical = :email OR u.username = :rawUsername OR u.email = :rawEmail')
            ->setParameter('id', $user->getId() ?: 0)
            ->setParameter('username', mb_strtolower((string) $user->getUsername(), 'UTF-8'))
            ->setParameter('email', mb_strtolower((string) $user->getEmail(), 'UTF-8'))
            ->setParameter('rawUsername', $user->getUsername())
            ->setParameter('rawEmail', $user->getEmail())
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $duplicate !== null;
    }

    private function addDuplicateUserErrors($form)
    {
        $message = 'Username hoặc email đã tồn tại.';
        $form->get('username')->addError(new FormError($message));
        $form->get('email')->addError(new FormError($message));
    }

    private function denyNonSuperAdminManagingSuperAdmin(User $user)
    {
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)) {
            throw $this->createAccessDeniedException('Chỉ Super Admin được chỉnh tài khoản Super Admin.');
        }
    }

    public function getRoleLabel($role)
    {
        $labels = [
            'ROLE_SUPER_ADMIN' => 'Super Admin',
            'ROLE_ADMIN' => 'Admin',
            'ROLE_EDITOR' => 'Editor',
            'ROLE_AUTHOR' => 'Author',
            'ROLE_CONTRIBUTOR' => 'Contributor',
            'ROLE_SEO' => 'SEO',
            'ROLE_SALES' => 'Sales',
            'ROLE_USER' => 'User',
        ];

        return isset($labels[$role]) ? $labels[$role] : $role;
    }

    public function getPrimaryRole(User $user)
    {
        $priority = [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_EDITOR',
            'ROLE_AUTHOR',
            'ROLE_CONTRIBUTOR',
            'ROLE_SEO',
            'ROLE_SALES',
            'ROLE_USER',
        ];

        foreach ($priority as $role) {
            if (in_array($role, $user->getRoles(), true)) {
                return $role;
            }
        }

        return 'ROLE_USER';
    }
}
