# Table of Contents

 - [How to add new field type](#how-to-add-new-field-type)
 - [How to add type-specific options to a field type](#how-to-add-type-specific-options-to-a-field-type)
 - [Validation](#validation)
 
## How to add new field type

First, create a new `FieldTypeProvider` that implements `FieldTypeProviderInterface`.

```php
<?php

namespace B2bCode\Bundle\AcmeBundle\Provider;

use B2bCode\Bundle\CmsFormBundle\Provider\FieldTypeProviderInterface;
use B2bCode\Bundle\CmsFormBundle\ValueObject\CmsFieldType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class FieldTypeProvider implements FieldTypeProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getAvailableTypes(): array
    {
        return [
            new CmsFieldType('hidden', HiddenType::class),
        ];
    }
}
```

And define a service in `services.yml`. Don't forget about `b2b_code_cms_form.field_type_provider` tag.

```yaml
B2bCode\Bundle\AcmeBundle\Provider\FieldTypeProvider:
    class: B2bCode\Bundle\AcmeBundle\Provider\FieldTypeProvider
    tags:
        - { name: b2b_code_cms_form.field_type_provider }
```

Then, just create a translation label in `messages.en.yml` and you can use your newly added field in a form.
Form engine is searching for a label named `b2bcode.cmsform.field_type.FIELD_TYPE_NAME.label`, where `FIELD_TYPE_NAME` in our example is `hidden`.

```yaml
b2bcode.cmsform.field_type.hidden.label: Hidden
``` 

## How to add type-specific options to a field type

Let's have a look at out example of hidden type. It's not very useful yet. Hidden field adds no value if
there is no data associated with it. We can easily fix it by adding type-specific option to this field type.

First, create a form extension for a `FieldType` class.
```php
<?php

namespace B2bCode\Bundle\AcmeBundle\Form\Extension;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormField;
use B2bCode\Bundle\CmsFormBundle\Form\Type\FieldType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class HiddenFieldExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return FieldType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $form = $event->getForm();
                /** @var CmsFormField $formField */
                $formField = $event->getData();

                if ($formField && $formField->getType() === 'hidden') {
                    $this->addField($form);
                }
            });

        $builder->get('type')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                /** @var CmsFormField $formField */
                $form = $event->getForm()->getParent();
                $formType = $event->getData();

                if ($formType && $formType === 'hidden') {
                    $this->addField($form);
                }
            }
        );
    }

    /**
     * @param FormInterface $form
     */
    protected function addField(FormInterface $form)
    {
        // default value for a field is stored in a `data` option
        $form
            ->add(
                'data',
                TextType::class,
                [
                    'required'      => false,
                    'label'         => 'b2bcode.cmsform.cmsformfield.options.data.label',
                    'property_path' => 'options[data]'
                ]
            );
    }
}
```

And register it in `services.yml`.
```yaml
B2bCode\Bundle\AcmeBundle\Form\Extension\HiddenFieldExtension:
    class: B2bCode\Bundle\AcmeBundle\Form\Extension\HiddenFieldExtension
    tags:
        - { name: form.type_extension, extended_type: B2bCode\Bundle\CmsFormBundle\Form\Type\FieldType}
```

Type-specific `data` option is now visible:
![Hidden data option](./images/hidden_data_value.png "Hidden data")

Let's fill it with `campaign-123` assuming this value is important when gathering responses of a form.

Rendered hidden field now contains data from the field configuration:
`<input type="hidden" value="campaign-123">`

## Validation

Validation is handled via `FormConstraintCollection` class. There are a few methods to add constraints to a form.

### Yaml configuration

Create `Resources/config/form_validation.yml` file in your bundle and add your configuration:
```yaml
# Resources/config/form_validation.yml
forms:
    form-alias: # B2bCode\Bundle\CmsFormBundle\Entity\CmsForm::getAlias()
        fields:
            field-name-1:  # B2bCode\Bundle\CmsFormBundle\Entity\CmsFormField::getName()
                # list of constraints. Sam as in standard symfony validation component
                - Symfony\Component\Validator\Constraints\NotBlank: ~
            field-name-2:
                - Symfony\Component\Validator\Constraints\NotBlank: ~
```

Example of the validation configuration you can find [here](./../config/form_validation.yml).

Validation rules are cached, so after changing yaml file make sure to clear cache `bin/console cache:clear`.

### Event

After loading configuration from the `form_validation.yml` file (or from cache) there's an event triggered which you can
use to inject your own constraints to the form.

```php
$event = new ConstraintBuild($collection, $form);
$this->eventDispatcher->dispatch($event);

return $event->getConstraintCollection();
```

`B2bCode\Bundle\CmsFormBundle\Event\ConstraintBuild` (`b2b_code_cms_form.constraints.build`).

### New field type type

Instead of configuring validation for an existing field, you can [add new field type](#how-to-add-new-field-type) and configure validation constraints directly in the type.
