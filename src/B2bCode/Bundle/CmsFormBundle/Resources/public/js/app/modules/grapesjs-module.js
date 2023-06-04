import ComponentManager from 'orocms/js/app/grapesjs/plugins/components/component-manager';
import CmsFormType from 'b2bcodecmsform/js/app/grapesjs/types/cms-form-type';

ComponentManager.registerComponentTypes({
    'cms-form': {
        Constructor: CmsFormType
    },
});
