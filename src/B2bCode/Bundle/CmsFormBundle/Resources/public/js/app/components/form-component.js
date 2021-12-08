define(function(require) {
    'use strict';

    var FormComponent;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var messenger = require('oroui/js/messenger');
    var BaseComponent = require('oroui/js/app/components/base/component');

    FormComponent = BaseComponent.extend({
        options: {
            formSelector: 'form[name="cms_form"]'
        },

        /**
         * @inheritDoc
         */
        constructor: function FormComponent() {
            FormComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.$el = this.options._sourceElement;
            this.$form = this.$el.find(this.options.formSelector);

            this.$el.on('submit', this.options.formSelector, _.bind(this.onSubmit, this));
        },

        onSubmit: function(e) {
            e.preventDefault();
            var that = this;

            if (this.$form.data('validator')) {
                this.$form.validate();

                if (!this.$form.valid()) {
                    return false;
                }
            }

            mediator.execute('showLoading');

            $.ajax({
                url: this.$form.attr('action'),
                type: this.$form.attr('method'),
                data: this.$form.serialize(),
                success: function(response) {
                    if (response.success === false) {
                        that.onErrorHandler(response);
                        return;
                    }

                    that.onSuccessHandler(response);
                },
                complete: function() {
                    mediator.execute('hideLoading');
                }
            });
        },

        onSuccessHandler: function(response) {
            messenger.notificationFlashMessage(
                'success',
                __('b2bcode.cmsform.ajax.form_respond.success'),
                {flash: true}
            );
            if (response.redirectUrl) {
                window.location.href = response.redirectUrl;
            } else {
                // to reset form
                mediator.execute('refreshPage');
            }
        },

        onErrorHandler: function(response) {
            if (!response.hasOwnProperty('errors')) {
                return;
            }
            var field;
            for (field in response.errors) {
                var $field = this.$form.find('[data-name="field__' + field + '"]');

                if ($field.length && response.errors.hasOwnProperty(field)) {
                    $field.parent().append(
                        '<span class="validation-failed"><span>' + response.errors[field] + '</span></span>'
                    );
                }
            }
        },
    });

    return FormComponent;
});
