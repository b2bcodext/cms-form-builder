import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';
import DialogWidget from 'oro/dialog-widget';
import template from 'tpl-loader!b2bcodecmsform/templates/grapesjs-cms-form.html';
import routing from 'routing';

/**
 * CMS form type builder
 */
const CmsFormTypeBuilder = BaseTypeBuilder.extend({
    button: {
        label: __('b2bcode.cmsform.wysiwyg.component.cms_form.label'),
        category: 'Basic',
        attributes: {
            'class': 'fa fa-file-text'
        }
    },

    editorEvents: {
        'canvas:drop': 'onDrop'
    },

    commands: {
        'cms-form-settings': (editor, sender, componentModel) => {
            const datagridName = 'b2bcode-cms-forms-grid';
            const container = editor.Commands.isActive('fullscreen') ? editor.getEl() : 'body';
            const routeParams = {
                gridName: datagridName
            };

            const dialog = new DialogWidget({
                title: __('b2bcode.cmsform.wysiwyg.component.cms_form.dialog.title'),
                url: routing.generate(
                    'oro_datagrid_widget',
                    routeParams
                ),
                loadingElement: container,
                dialogOptions: {
                    modal: true,
                    resizable: true,
                    autoResize: true,
                    appendTo: container,
                    close: function() {
                        if (componentModel.cid && !componentModel.get('cmsForm')) {
                            componentModel.remove();
                        }
                    }
                }
            });

            dialog.on('contentLoad', function(data, widget) {
                const gridWidget = widget.componentManager.get(datagridName);
                gridWidget.grid.columns.remove(_.last(gridWidget.grid.columns.models));
            });

            dialog.on('grid-row-select', function(data) {
                let selected = editor.getSelected();

                if (componentModel.cid) {
                    selected = componentModel;
                }

                selected.set('cmsForm', data.model);
                dialog.remove();
            });

            dialog.render();
        }
    },

    modelMixin: {
        defaults: {
            tagName: 'div',
            classes: ['cms-form', 'content-placeholder'],
            cmsForm: null,
            droppable: false
        },

        initialize(...args) {
            this.constructor.__super__.initialize.call(this, ...args);

            const toolbar = this.get('toolbar');
            const commandExists = _.some(toolbar, {
                command: 'cms-form-settings'
            });

            if (!commandExists) {
                toolbar.unshift({
                    attributes: {
                        'class': 'fa fa-gear',
                        'label': __('b2bcode.cmsform.wysiwyg.component.cms_form.block_setting')
                    },
                    command: 'cms-form-settings'
                });

                this.set('toolbar', toolbar);
            }

            this.listenTo(this, 'change:cmsForm', this.onCmsFormChange, this);
        },

        onCmsFormChange(model, cmsForm) {
            this.set('attributes', {
                'data-title': cmsForm.get('name')
            });

            this.set('content', '{{ b2b_code_form("' + cmsForm.get('alias') + '") }}');
            this.view.render();
        }
    },

    viewMixin: {
        onRender() {
            let title;
            const cmsForm = this.model.get('cmsForm');

            if (cmsForm) {
                title = cmsForm.cid ? cmsForm.get('name') : cmsForm.name;
            } else {
                title = this.$el.attr('data-title');
            }

            this.$el.html(template({title}));
        }
    },

    /**
     * @inheritDoc
     */
    constructor: function CmsFormTypeBuilder(options) {
        CmsFormTypeBuilder.__super__.constructor.call(this, options);
    },

    onDrop(DataTransfer, model) {
        if (model instanceof this.Model) {
            this.editor.runCommand('cms-form-settings', model);
        }
    },

    isComponent(el) {
        let result = null;

        if (el.tagName === 'DIV' && el.classList.contains('cms-form')) {
            result = {
                type: this.componentType
            };
        }

        return result;
    }
});

export default CmsFormTypeBuilder;
