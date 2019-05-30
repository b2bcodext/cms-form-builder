define(function(require) {
    'use strict';

    var FieldOptionsComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var routing = require('routing');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var layout = require('oroui/js/layout');

    FieldOptionsComponent = BaseComponent.extend({
        /**
         * @property {jQuery}
         */
        $el: null,

        loadingMask: null,

        /**
         * @property {Object}
         */
        selectors: {
            formName: '[name$="[name]"]',
            formType: '[name$="[type]"]',
            formViewTarget: '#form-view-target',
            formPreviewTrigger: '.cms-field-preview__trigger',
            formPreviewTarget: '.cms-field-preview__target',
            formFieldsSelector: '[name^="field"]'
        },

        selectedFormType: null,

        /**
         * @inheritDoc
         */
        constructor: function FieldOptionsComponent() {
            FieldOptionsComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.$el = $(options._sourceElement);

            this.$el.on('click', this.selectors.formPreviewTrigger, _.bind(this.renderPreview, this));
            this.$el.on('change', this.selectors.formType, _.bind(this.onFormTypeChanged, this));
            this.$el.on('change', this.selectors.formFieldsSelector, _.bind(this.renderPreview, this));

            this.renderPreview();
        },

        onFormTypeChanged: function() {
            if (this.selectedFormType !== this.getFormTypeElement().val()) {
                this.selectedFormType = this.getFormTypeElement().val();
                var data = {};
                data[this.getFormTypeElement().attr('name')] = this.getFormTypeElement().val();

                this._showLoading();

                $.ajax({
                    url: routing.generate('b2b_code_cms_form_frontend_ajax_form_view'),
                    data: data,
                    method: 'POST',
                    success: _.bind(function(response) {
                        this.getFormViewTargetElement().html(response);
                        this.getFormViewTargetElement().inputWidget('seekAndCreate');
                        layout.initPopover(this.getFormViewTargetElement());
                    }, this),
                    complete: _.bind(function() {
                        this._hideLoading();
                    }, this),
                    errorHandlerMessage: __('b2bcode.cmsform.ajax.form_view_error')
                });
            }
        },

        renderPreview: function() {
            // I didn't want to trigger .valid() here on fields in order to do not display errors right away
            var nameInvalid = _.isEmpty(this.getFormNameElement().val()) || this.getFormNameElement().hasClass('error');
            var typeInvalid = _.isEmpty(this.getFormTypeElement().val()) || this.getFormTypeElement().hasClass('error');

            if (nameInvalid || typeInvalid) {
                return;
            }

            $.ajax({
                url: routing.generate('b2b_code_cms_form_frontend_ajax_field_preview'),
                data: this.$el.closest('form[name="field"]').serialize(),
                method: 'POST',
                success: _.bind(function(response) {
                    this.getFormPreviewTargetElement().html(response);
                    this.getFormPreviewTargetElement().inputWidget('seekAndCreate');
                }, this),
                errorHandlerMessage: __('b2bcode.cmsform.ajax.field_preview_error')
            });
        },

        /**
         * @returns {jQuery}
         */
        getFormNameElement: function() {
            if (!this.hasOwnProperty('$formNameElement')) {
                this.$formNameElement = this.$el.find(this.selectors.formName);
            }

            return this.$formNameElement;
        },

        /**
         * @returns {jQuery}
         */
        getFormTypeElement: function() {
            if (!this.hasOwnProperty('$formTypeElement')) {
                this.$formTypeElement = this.$el.find(this.selectors.formType);
            }

            return this.$formTypeElement;
        },

        /**
         * @returns {jQuery}
         */
        getFormViewTargetElement: function() {
            if (!this.hasOwnProperty('$formViewTargetElement')) {
                this.$formViewTargetElement = this.$el.find(this.selectors.formViewTarget);
            }

            return this.$formViewTargetElement;
        },

        /**
         * @returns {jQuery}
         */
        getFormPreviewTargetElement: function() {
            if (!this.hasOwnProperty('$formPreviewTargetElement')) {
                this.$formPreviewTargetElement = this.$el.find(this.selectors.formPreviewTarget);
            }

            return this.$formPreviewTargetElement;
        },

        /**
         * Shows loading indicator
         */
        _showLoading: function() {
            if (this.loadingMask) {
                this.loadingMask.dispose();
            }
            this.loadingMask = new LoadingMaskView({
                container: this.getFormViewTargetElement()
            });
            this.loadingMask.show();
        },

        _hideLoading: function() {
            if (this.loadingMask) {
                this.loadingMask.hide();
                this.loadingMask.dispose();
                this.loadingMask = null;
            }
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el.off();

            FieldOptionsComponent.__super__.dispose.call(this);
        }
    });

    return FieldOptionsComponent;
});
