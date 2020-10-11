define(function(require) {
    'use strict';

    var SortableFieldsComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var BaseComponent = require('oroui/js/app/components/base/component');
    require('jquery-ui/widgets/sortable');

    SortableFieldsComponent = BaseComponent.extend({
        options: {
            formSelector: 'form[name="cms_form_reorder"]',
            sortableContainer: '#cms-form-fields-sortable',
        },

        /**
         * @property {jQuery}
         */
        $el: null,

        /**
         * @inheritDoc
         */
        constructor: function SortableFieldsComponent() {
            SortableFieldsComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.$el = $(options._sourceElement);
            this.$form = this.$el.find(this.options.formSelector);
            this.initSortable();
        },

        reindexValues: function() {
            var index = 1;
            this.$el.find('[name$="[sortOrder]"]').each(function() {
                $(this).val(index++);
            });

            this.sendAjax();
        },

        sendAjax: function() {
            this._showLoading();

            var $form = this.$el.find(this.options.formSelector);

            $.ajax({
                url: $form.attr('action'),
                data: $form.serialize(),
                method: 'POST',
                success: _.bind(function(response) {
                    mediator.trigger('datagrid:doRefresh:b2bcode-cms-form-fields-grid');
                }, this),
                complete: _.bind(function() {
                    this._hideLoading();
                }, this),
                errorHandlerMessage: __('b2bcode.cmsform.ajax.reorder.error')
            });
        },

        initSortable: function() {
            this.$el.find(this.options.sortableContainer).sortable({
                items: '> .sortable-item',
                handle: '[data-name="sortable-handle"]',
                cancel: '',
                placeholder: 'ui-state-highlight',
                forcePlaceholderSize: true,
                tolerance: 'pointer',
                delay: 100,
                stop: _.bind(this.reindexValues, this)
            });
        },

        _showLoading: function() {
            if (this.loadingMask) {
                this.loadingMask.dispose();
            }
            this.loadingMask = new LoadingMaskView({
                container: this.$el.find(this.options.formSelector)
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
    });

    return SortableFieldsComponent;
});
