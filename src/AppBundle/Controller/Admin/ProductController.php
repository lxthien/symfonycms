<?php

namespace AppBundle\Controller\Admin;

use AppBundle\Entity\ProductCat;
use AppBundle\Entity\Product;
use AppBundle\Form\ProductCatType;
use AppBundle\Form\ProductType;
use AppBundle\Form\ProductImageType;
use AppBundle\Utils\Slugger;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/admin/product")
 * @Security("has_role('ROLE_ADMIN')")
 */

class ProductController extends Controller
{
    /**
     * @Route("/", name="admin_product_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $objects = $em->getRepository(Product::class)->findAll();

        return $this->render('admin/product/index.html.twig', ['objects' => $objects]);
    }

    /**
     * @Route("/new", name="admin_product_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request, Slugger $slugger)
    {
        $object = new Product();
        $object->setAuthor($this->getUser());

        $form = $this->createForm(ProductType::class, $object);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em = $this->getDoctrine()->getManager();
                $em->persist($object);
                $em->flush();

                $this->addFlash('success', 'action.created_successfully');

                return $this->redirectToRoute('admin_product_edit', array(
                    'id' => $object->getId()
                ));
            } catch (\DBALException $e) {
                $message = sprintf('DBALException [%i]: %s', $e->getCode(), $e->getMessage());
            } catch (\PDOException $e) {
                $message = sprintf('PDOException [%i]: %s', $e->getCode(), $e->getMessage());
            } catch (\ORMException $e) {
                $message = sprintf('ORMException [%i]: %s', $e->getCode(), $e->getMessage());
            } catch (\Exception $e) {
                $message = sprintf('Exception [%i]: %s', $e->getCode(), $e->getMessage());
            }

            $this->addFlash('error', $message);

            return $this->render('admin/product/new.html.twig', [
                'object' => $object,
                'form' => $form->createView(),
            ]);
        }

        return $this->render('admin/product/new.html.twig', [
            'object' => $object,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", requirements={"id": "\d+"}, name="admin_product_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Product $object, Slugger $slugger)
    {
        $form = $this->createForm(ProductType::class, $object);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->getDoctrine()->getManager()->flush();
                $this->addFlash('success', 'action.updated_successfully');

                return $this->redirectToRoute('admin_product_edit', array(
                    'id' => $object->getId()
                ));
            } catch (\DBALException $e) {
                $message = sprintf('DBALException [%i]: %s', $e->getCode(), $e->getMessage());
            } catch (\PDOException $e) {
                $message = sprintf('PDOException [%i]: %s', $e->getCode(), $e->getMessage());
            } catch (\ORMException $e) {
                $message = sprintf('ORMException [%i]: %s', $e->getCode(), $e->getMessage());
            } catch (\Exception $e) {
                $message = sprintf('Exception [%i]: %s', $e->getCode(), $e->getMessage());
            }

            $this->addFlash('error', $message);

            return $this->render('admin/product/edit.html.twig', [
                'object' => $object,
                'form' => $form->createView(),
            ]);
        }

        return $this->render('admin/product/edit.html.twig', [
            'object' => $object,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/delete", methods={"POST"}, name="admin_product_delete")
     */
    public function deleteAction(Request $request, $id, Product $object)
    {
        if (!$this->isCsrfTokenValid('delete', $request->request->get('token'))) {
            return $this->redirectToRoute('admin_product_index');
        }

        try {
            $object->getProductImages()->clear();
            $object->getRating()->clear();

            $em = $this->getDoctrine()->getManager();
            $em->remove($object);
            $em->flush();

            $this->addFlash('success', 'action.deleted_successfully');

            return $this->redirectToRoute('admin_product_index');
        } catch (\DBALException $e) {
            $message = sprintf('DBALException [%i]: %s', $e->getCode(), $e->getMessage());
        } catch (\PDOException $e) {
            $message = sprintf('PDOException [%i]: %s', $e->getCode(), $e->getMessage());
        } catch (\ORMException $e) {
            $message = sprintf('ORMException [%i]: %s', $e->getCode(), $e->getMessage());
        } catch (\Exception $e) {
            $message = sprintf('Exception [%i]: %s', $e->getCode(), $e->getMessage());
        }

        $this->addFlash('error', $message);

        return $this->redirectToRoute('admin_product_index');
    }
}
