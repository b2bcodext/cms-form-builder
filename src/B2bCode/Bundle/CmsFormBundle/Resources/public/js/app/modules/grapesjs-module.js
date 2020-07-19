import ComponentManager from 'orocms/js/app/grapesjs/plugins/components/component-manager';
import CmsFormTypeBuilder from 'b2bcodecmsform/js/app/grapesjs/type-builders/cms-form-type-builder';

ComponentManager.registerComponentTypes({
    'cms-form': {
        Constructor: CmsFormTypeBuilder
    },
});
