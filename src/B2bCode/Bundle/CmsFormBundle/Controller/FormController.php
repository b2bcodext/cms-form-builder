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
use B2bCode\Bundle\CmsFormBundle\Form\Type\FormType;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FormController extends Controller
{
    /**
     * @Route("/", name="b2b_code_cms_form_index")
     * @AclAncestor("b2b_code_cms_form_view")
     * @Template
     */
    public function indexAction()
    {
        return [];
    }

    /**
     * @Route("/view/{id}", name="b2b_code_cms_form_view", requirements={"id"="\d+"})
     * @AclAncestor("b2b_code_cms_form_view")
     * @Template
     */
    public function viewAction(CmsForm $cmsForm)
    {
        $form = $this->get(FormBuilderInterface::class)->getForm($cmsForm->getAlias());

        return ['entity' => $cmsForm, 'form' => $form->createView()];
    }

    /**
     * @Route("/create", name="b2b_code_cms_form_create")
     * @AclAncestor("b2b_code_cms_form_create")
     * @Template("B2bCodeCmsFormBundle:Form:update.html.twig")
     * @param Request $request
     * @return array|Response
     */
    public function createAction(Request $request)
    {
        $form = new CmsForm();

        $result = $this->update($request, $form);

        // for better UX redirect directly to field creation page
        if ($result instanceof RedirectResponse && $form->getId()) {
            return new RedirectResponse($this->generateUrl('b2b_code_cms_form_field_create', ['id' => $form->getId()]));
        }

        return $result;
    }

    /**
     * @Route("/update/{id}", name="b2b_code_cms_form_update", requirements={"id"="\d+"})
     * @AclAncestor("b2b_code_cms_form_update")
     * @Template("B2bCodeCmsFormBundle:Form:update.html.twig")
     * @param Request $request
     * @param CmsForm $form
     * @return array|Response
     */
    public function updateAction(Request $request, CmsForm $form)
    {
        return $this->update($request, $form);
    }

    /**
     * @param Request $request
     * @param CmsForm $form
     * @return Response|array
     */
    protected function update(Request $request, CmsForm $form)
    {
        $updateResult = $this->get('oro_form.update_handler')->update(
            $form,
            $this->createForm(FormType::class, $form),
            $this->get('translator')->trans('b2bcode.cmsform.saved_message'),
            $request
        );

        return $updateResult;
    }

    /**
     * @Route("/responses/{id}", name="b2b_code_cms_form_responses", requirements={"id"="\d+"})
     * @AclAncestor("b2b_code_cms_form_view")
     * @Template
     */
    public function responsesAction(CmsForm $cmsForm)
    {
        return ['entity' => $cmsForm];
    }

    /**
     * @Route("/{id}/field/create", name="b2b_code_cms_form_field_create", requirements={"id"="\d+"})
     * @AclAncestor("b2b_code_cms_form_field_create")
     * @Template("B2bCodeCmsFormBundle:Field:update.html.twig")
     * @param Request $request
     * @param CmsForm $cmsForm
     * @return array|Response
     */
    public function createFieldAction(Request $request, CmsForm $cmsForm)
    {
        $field = new CmsFormField();
        $field->setForm($cmsForm);

        return $this->updateField($request, $field);
    }

    /**
     * @Route("/field/update/{id}", name="b2b_code_cms_form_field_update", requirements={"id"="\d+"})
     * @AclAncestor("b2b_code_cms_form_field_update")
     * @Template("B2bCodeCmsFormBundle:Field:update.html.twig")
     * @param Request      $request
     * @param CmsFormField $field
     * @return array|Response
     */
    public function updateFieldAction(Request $request, CmsFormField $field)
    {
        return $this->updateField($request, $field);
    }

    /**
     * @param Request      $request
     *
     * @param CmsFormField $formField
     * @return Response|array
     */
    protected function updateField(Request $request, CmsFormField $formField)
    {
        $updateResult = $this->get('oro_form.update_handler')->update(
            $formField,
            $this->createForm(FieldType::class, $formField),
            $this->get('translator')->trans('b2bcode.cmsform.cmsformfield.saved_message'),
            $request
        );

        return $updateResult;
    }
}
