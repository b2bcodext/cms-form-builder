<?php

declare(strict_types=1);

/*
 * This file is part of the B2Bcodext CMS Form Builder.
 *
 * (c) Daniel Nahrebecki <daniel@b2bcodext.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace B2bCode\Bundle\CmsFormBundle\Controller;

use B2bCode\Bundle\CmsFormBundle\Builder\FormBuilderInterface;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsForm;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormField;
use B2bCode\Bundle\CmsFormBundle\Form\Type\FieldType;
use B2bCode\Bundle\CmsFormBundle\Form\Type\FormType;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Back-office CRUD for CMS forms, their fields and their responses.
 */
class FormController extends AbstractController
{
    /**
     * @return array<string, mixed>
     */
    #[Route(path: '/', name: 'b2b_code_cms_form_index')]
    #[AclAncestor('b2b_code_cms_form_view')]
    #[Template('@B2bCodeCmsForm/Form/index.html.twig')]
    public function indexAction()
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    #[Route(path: '/view/{id}', name: 'b2b_code_cms_form_view', requirements: ['id' => '\d+'])]
    #[AclAncestor('b2b_code_cms_form_view')]
    #[Template('@B2bCodeCmsForm/Form/view.html.twig')]
    public function viewAction(CmsForm $cmsForm, FormBuilderInterface $formBuilder)
    {
        $form = $formBuilder->getForm($cmsForm->getAlias());

        return ['entity' => $cmsForm, 'form' => $form->createView()];
    }

    /**
     * @return array<string, mixed>|RedirectResponse
     */
    #[Route(path: '/create', name: 'b2b_code_cms_form_create')]
    #[AclAncestor('b2b_code_cms_form_create')]
    #[Template('@B2bCodeCmsForm/Form/update.html.twig')]
    public function createAction(
        Request $request,
        UpdateHandlerFacade $formHandler,
        TranslatorInterface $translator
    ) {
        $form = new CmsForm();

        $result = $this->update($request, $form, $formHandler, $translator);

        // for better UX redirect directly to field creation page
        if ($result instanceof RedirectResponse && $form->getId()) {
            return new RedirectResponse($this->generateUrl('b2b_code_cms_form_field_create', ['id' => $form->getId()]));
        }

        return $result;
    }

    /**
     * @return array<string, mixed>|RedirectResponse
     */
    #[Route(path: '/update/{id}', name: 'b2b_code_cms_form_update', requirements: ['id' => '\d+'])]
    #[AclAncestor('b2b_code_cms_form_update')]
    #[Template('@B2bCodeCmsForm/Form/update.html.twig')]
    public function updateAction(
        Request $request,
        CmsForm $form,
        UpdateHandlerFacade $formHandler,
        TranslatorInterface $translator
    ) {
        return $this->update($request, $form, $formHandler, $translator);
    }

    /**
     * @return array<string, mixed>|RedirectResponse
     */
    protected function update(
        Request $request,
        CmsForm $form,
        UpdateHandlerFacade $formHandler,
        TranslatorInterface $translator
    ) {
        $updateResult = $formHandler->update(
            $form,
            $this->createForm(FormType::class, $form),
            $translator->trans('b2bcode.cmsform.saved_message'),
            $request
        );

        return $updateResult;
    }

    /**
     * @return array<string, mixed>
     */
    #[Route(path: '/responses/{id}', name: 'b2b_code_cms_form_responses', requirements: ['id' => '\d+'])]
    #[AclAncestor('b2b_code_cms_form_view')]
    #[Template('@B2bCodeCmsForm/Form/responses.html.twig')]
    public function responsesAction(CmsForm $cmsForm)
    {
        return ['entity' => $cmsForm];
    }

    /**
     * @return array<string, mixed>|RedirectResponse
     */
    #[Route(path: '/{id}/field/create', name: 'b2b_code_cms_form_field_create', requirements: ['id' => '\d+'])]
    #[AclAncestor('b2b_code_cms_form_field_create')]
    #[Template('@B2bCodeCmsForm/Field/update.html.twig')]
    public function createFieldAction(
        Request $request,
        CmsForm $cmsForm,
        UpdateHandlerFacade $formHandler,
        TranslatorInterface $translator
    ) {
        $field = new CmsFormField();
        $field->setForm($cmsForm);

        return $this->updateField($request, $field, $formHandler, $translator);
    }

    /**
     * @return array<string, mixed>|RedirectResponse
     */
    #[Route(path: '/field/update/{id}', name: 'b2b_code_cms_form_field_update', requirements: ['id' => '\d+'])]
    #[AclAncestor('b2b_code_cms_form_field_update')]
    #[Template('@B2bCodeCmsForm/Field/update.html.twig')]
    public function updateFieldAction(
        Request $request,
        CmsFormField $field,
        UpdateHandlerFacade $formHandler,
        TranslatorInterface $translator
    ) {
        return $this->updateField($request, $field, $formHandler, $translator);
    }

    /**
     * @return array<string, mixed>|RedirectResponse
     */
    protected function updateField(
        Request $request,
        CmsFormField $formField,
        UpdateHandlerFacade $formHandler,
        TranslatorInterface $translator
    ) {
        $updateResult = $formHandler->update(
            $formField,
            $this->createForm(FieldType::class, $formField),
            $translator->trans('b2bcode.cmsform.cmsformfield.saved_message'),
            $request
        );

        return $updateResult;
    }
}
