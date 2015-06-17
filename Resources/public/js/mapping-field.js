define(
    ['jquery', 'backbone', 'underscore', 'oro/translator', 'bootstrap-tooltip'],
    function ($, Backbone, _, __) {
        'use strict';
        var MappingItem = Backbone.Model.extend({
            defaults: {
                source    : null,
                target    : null,
                deletable : true
            }
        });

        var MappingCollection = Backbone.Collection.extend({
            model: MappingItem
        });

        var MappingItemView = Backbone.View.extend({
            tagName: 'tr',
            className: 'mapping-row',
            template: _.template(
                '<td>' +
                    '<input type="text" class="mapping-source" required="required" value="<%= mappingItem.source %>"/>' +
                '</td>' +
                '<td>' +
                    '<i class="icon-arrow-right"></i>' +
                '</td>' +
                '<td>' +
                    '<input type="text" class="mapping-target" required="required" required="required" value="<%= mappingItem.target %>"/>' +
                    '<i class="validation-tooltip" data-placement="right" data-toggle="tooltip" data-original-title="<%= notBlankError %>"></i>' +
                '</td>' +
                '<td>' +
                    '<a href="javascript:void(0);" class="btn remove-btn <% if (!mappingItem.deletable) { %>disabled<% } %>">' +
                        '<i class="icon-remove-sign"></i>' +
                    '</a>' +
                '</td>'
            ),
            events: {
                'change input.mapping-source': 'updateSource',
                'change input.mapping-target': 'updateTarget',
                'click a.remove-btn':          'removeMappingItem'
            },
            sources: [],
            targets: [],
            initialize: function(options) {
                this.sources = options.sources;
                this.targets = options.targets;

                this.listenTo(this.model, "destroy", this.remove);

                this.render();
            },
            render: function() {
                this.$el.html(this.template({mappingItem: this.model.toJSON(), notBlankError: __('pim_connector_mapping.mapping.not_blank')}));
                this.$el.find('.validation-tooltip').hide();
                return this;
            },
            updateSource: function(e) {
                this.model.set('source', e.currentTarget.value);
            },
            updateTarget: function(e) {
                this.model.set('target', e.currentTarget.value);
            },
            removeMappingItem: function(e) {
                if (this.model.attributes.deletable) {
                    this.model.destroy();
                }
            }
        });

        var MappingView = Backbone.View.extend({
            tagName: 'table',
            className: 'table table-bordered mapping-table',
            mappingTemplate: _.template(
                '<thead>' +
                    '<tr>' +
                        '<td><%= sourceTitle %></td>' +
                        '<td></td>' +
                        '<td><%= targetTitle %></td>' +
                        '<td></td>' +
                    '</tr>' +
                '</thead>' +
                '<tbody>' +
                '</tbody>' +
                '<tfoot>' +
                    '<tr>' +
                        '<td colspan="4">' +
                            '<a href="javascript:void(0);" class="btn add-btn">' +
                                '<i class="icon-plus"></i><%= addButton %>' +
                            '</a>' +
                        '</td>' +
                    '</tr>' +
                '</tfoot>'
            ),
            emptyTemplate: _.template(
                '<thead>' +
                    '<tr>' +
                        '<td colspan="4"><%= emptyMessage %></td>' +
                    '</tr>' +
                '</thead>'
            ),
            events: {
                'click a.add-btn': 'addMappingItem',
            },
            $target: null,
            sources: [],
            targets: [],
            name: null,
            mappingItemViews: [],
            initialize: function(options) {
                this.$target = options.$target;
                this.sources = options.sources;
                this.targets = options.targets;
                this.name    = options.name;

                this.listenTo(this.collection, "change add remove", this.save);
                this.render();

                if (this.collection.length === 0) {
                    this.addMappingItem();
                }
            },
            render: function() {
                this.$el.empty();

                if (!this.targets.allowAddition && this.targets.targets.length === 0) {
                    this.$el.html(this.emptyTemplate({
                       emptyMessage : __('pim_connector_mapping.mapping.' + this.name + '.empty')
                    }));
                } else {
                    this.$el.html(this.mappingTemplate({
                       sourceTitle : __('pim_connector_mapping.mapping.' + this.name + '.source'),
                       targetTitle : __('pim_connector_mapping.mapping.' + this.name + '.target'),
                       addButton   : __('pim_connector_mapping.mapping.add')
                    }));


                    _.each(this.collection.models, function(mappingItem) {
                        this.addMappingItem({mappingItem: mappingItem});
                    }, this);
                }

                if (!this.$target.data('rendered')) {
                    this.$target.after(this.$el)
                    this.$target.hide();
                }

                this.$target.data('rendered', true);

                return this;
            },
            save: function() {
                var values = {};
                _.each(this.collection.toJSON(), function(value) {
                    values[value.source] = {source:value.source, target: value.target};
                });

                this.$target.html(JSON.stringify(values));

                if (this.collection.length === 0) {
                    this.addMappingItem();
                }
            },
            createMappingItem: function() {
                var mappingItem = new MappingItem({source: '', target: '', deletable: true});
                this.collection.add(mappingItem);

                return mappingItem;
            },
            createMappingItemView: function(mappingItem) {
                var mappingItemView = new MappingItemView({
                    model: mappingItem,
                    sources: this.sources,
                    targets: this.targets
                });

                this.mappingItemViews.push(mappingItemView);

                return mappingItemView;
            },
            addMappingItem: function(options) {
                var options = options || {};

                if (!options.mappingItem) {
                    var mappingItem = this.createMappingItem();
                } else {
                    var mappingItem = options.mappingItem;
                }

                var mappingItemView = this.createMappingItemView(mappingItem);

                this.$el.children('tbody').append(mappingItemView.$el);

                var select2TargetConfig = {};

                if (this.targets.allowAddition) {
                    select2TargetConfig.maximumSelectionSize = 1;
                    select2TargetConfig.tags = this.targets.targets;
                } else {
                    select2TargetConfig.data        = this.targets.targets;
                    select2TargetConfig.placeholder = __('pim_connector_mapping.mapping.' + this.name + '.placeholder');
                }

                mappingItemView.$el.find('.mapping-source').select2({
                    tags: this.sources.sources,
                    maximumSelectionSize: 1
                });

                mappingItemView.$el.find('.mapping-target').select2(select2TargetConfig)
                    .enable(mappingItemView.model.get('deletable'));
            }
        });

        return function($element) {
            if ($element.data('rendered') == true) {
                return;
            }

            if ($element.find('.mapping-field').length > 0) {
                $element = $element.find('.mapping-field');
            }

            var fieldValues = JSON.parse($element.html());

            var mappingCollection = [];
            for (var field in fieldValues) {
                if (fieldValues[field]['target'] != '') {
                    mappingCollection.push(fieldValues[field]);
                }
            }

            new MappingView({
                collection: new MappingCollection(mappingCollection),
                $target: $element,
                sources: $element.data('sources'),
                targets: $element.data('targets'),
                name:    $element.data('name')
            });

            $element.parents('form').on('submit', function() {
                var isValid = true;
                var $error = $('<i class="validation-tooltip" data-placement="right" data-toggle="tooltip" ' +
                            'data-original-title="' + __('pim_connector_mapping.mapping.not_blank') + '"></i>').tooltip();

                $('.mapping-row').each(function() {
                    $(this).find('.validation-tooltip').hide();

                    var $source = $(this).find('input.mapping-source');
                    var $target = $(this).find('input.mapping-target')

                    if (($source.val() == '') !=
                        ($target.val() == '')
                    ) {

                        if ($source.val() == '') {
                            $source.next('.validation-tooltip').show();
                        } else {
                            $target.next('.validation-tooltip').show();
                        }

                        isValid = false;
                    }
                });

                return isValid;
            });
        };
    }
);
