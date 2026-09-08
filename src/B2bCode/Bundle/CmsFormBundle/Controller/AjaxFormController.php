<?php

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
use B2bCode\Bundle\CmsFormBundle\Provider\GeneralFieldProvider;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Component\HttpFoundation\Response;

class AjaxFormController extends AbstractController
{
    #[Route(path: '/form-view}', name: 'b2b_code_cms_form_frontend_ajax_form_view')]
    #[AclAncestor('b2b_code_cms_form_field_create')]
    #[Template]
    public function formViewAction(Request $request, GeneralFieldProvider $fieldProvider)
    {
        $form = $this->createForm(FieldType::class, new CmsFormField());
        $form->handleRequest($request);

        $formView = $form->createView();
        $fieldProvider->manipulate($formView);

        return [
            'form' => $formView,
        ];
    }

    #[Route(path: '/form-preview}', name: 'b2b_code_cms_form_frontend_ajax_field_preview')]
    #[AclAncestor('b2b_code_cms_form_field_create')]
    #[Template]
    public function fieldPreviewAction(Request $request, FormBuilderInterface $formBuilder)
    {
        $cmsField =  new CmsFormField();
        $form = $this->createForm(FieldType::class, $cmsField);
        $form->handleRequest($request);

        return [
            'form' => $formBuilder->buildField($cmsField)->createView(),
            'entity' => $cmsField
        ];
    }

    #[Route(path: '/{id}/reorder', name: 'b2b_code_cms_form_ajax_reorder')]
    #[AclAncestor('b2b_code_cms_form_create')]
    public function reorderAction(Request $request, CmsForm $cmsForm, ManagerRegistry $registry)
    {
        // `InputBag::get()` only accepts scalars, so the array payload must be read with `all()`.
        $data = $request->request->all('cms_form_reorder');
        if (!array_key_exists('fields', $data)) {
            return new JsonResponse(['success' => false]);
        }

        foreach ($data['fields'] as $fieldName => $sortOrder) {
            if (!$cmsForm->getField($fieldName) || !array_key_exists('sortOrder', $sortOrder)) {
                continue;
            }

            $cmsForm->getField($fieldName)->setSortOrder((int)$sortOrder['sortOrder']);
        }

        $manager = $registry->getManagerForClass(CmsForm::class);
        $manager->persist($cmsForm);
        $manager->flush();

        return new JsonResponse(['success' => true]);
    }
}
