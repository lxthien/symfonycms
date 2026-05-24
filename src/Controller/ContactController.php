<?php

namespace App\Controller;

use App\Controller\LegacyController as AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use App\Entity\Contact;
use App\Entity\News;
use App\Form\Type\RecaptchaType;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class ContactController extends AbstractController
{
    /**
     * @Route("lien-he", name="contact")
     */
    public function indexAction(Request $request, MailerInterface $mailer)
    {
        $contact = new Contact();

        $form = $this->createFormBuilder($contact)
            ->add('name', TextType::class, array('label' => 'label.author'))
            ->add('email', EmailType::class, array('label' => 'label.author_email'))
            ->add('phone', TextType::class, array('label' => 'label.phone'))
            ->add('contents', TextareaType::class, array(
                'label' => 'label.content',
                'attr' => array('rows' => '7')
            ))
            ->add('recaptcha', RecaptchaType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $em->persist($contact);
            $em->flush();

            if (null === $contact->getId()) {
                $this->addFlash(
                    'error',
                    $this->get('translator')->trans('contact.message.error')
                );

                return $this->render('contact/index.html.twig', [
                    'form' => $form->createView(),
                ]);
            } else {
                $this->addFlash(
                    'notice',
                    $this->get('translator')->trans('contact.message.success')
                );

                $message = (new Email())
                    ->subject($this->get('translator')->trans('contact.email.title', ['%siteName%' => $this->get('settings_manager')->get('siteName')]))
                    ->from('hotro.xaydungminhduy@gmail.com')
                    ->replyTo($this->get('settings_manager')->get('siteName'))
                    ->to($this->get('settings_manager')->get('emailContact'))
                    ->html(
                        $this->renderView(
                            'Emails/contact.html.twig',
                            array(
                                'name' => $form->get('name')->getData(),
                                'phone' => $form->get('phone')->getData(),
                                'email' => $form->get('email')->getData(),
                                'body' => $form->get('contents')->getData()
                            )
                        )
                    )
                ;

                $mailer->send($message);

                return $this->redirectToRoute('contact');
            }
        }

        $breadcrumbs = $this->get("white_october_breadcrumbs");
        $breadcrumbs->addItem("home", $this->generateUrl("homepage"));
        $breadcrumbs->addItem('contactus');

        $post = $this->getDoctrine()
            ->getRepository(News::class)
            ->findOneBy(
                array('url' => 'lien-he')
            );

        return $this->render('contact/index.html.twig', [
            'form' => $form->createView(),
            'post' => $post
        ]);
    }
}
