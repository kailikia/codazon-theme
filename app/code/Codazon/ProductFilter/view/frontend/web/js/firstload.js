/**
 * Copyright © 2020 Codazon, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    "jquery", "jquery-ui-modules/widget", "domReady!",
],function ($) {
    $.widget('codazon.firstLoad', {
        options: {
            formKeyInputSelector: 'input[name="form_key"]'
        },
        _checkVisible: function() {
            var $element = this.element;
            var cond1 = ($element.get(0).offsetWidth > 0) && ($element.get(0).offsetHeight > 0),
            cond2 = ($element.is(':visible'));
            var winTop = $(window).scrollTop(),
            winBot = winTop + window.innerHeight,
            elTop = $element.offset().top, elHeight = $element.outerHeight(true),
            elBot = elTop + elHeight;
            var cond3 = (elTop <= winTop) && (elBot >= winTop),
            cond4 = (elTop >= winTop) && (elTop <= winBot), cond5 = (elTop >= winTop) && (elBot <= winBot),
            cond6 = (elTop <= winBot) && (elBot >= winBot), cond7 = true;
            if ($element.parents('md-tab-content').length) {
                cond7 = $element.parents('md-tab-content').first().hasClass('md-active');
            }
            return cond1 && cond2 && (cond3 || cond4 || cond5 || cond6) && cond7;
        },
        _create: function() {
            var self = this, conf = this.options;
            if (typeof window.visibleProductBlocks === 'undefined') {
                window.visibleProductBlocks = true;
                this._loadVisibleProductBlocks();
            }
            var data = conf.jsonData;
            if (this.element.hasClass('js-products') && data.cache_key_info) {
                var cacheId = data.cache_key_info[0];
                if (window.productGrpData[cacheId]) {
                    var html = window.productGrpData[cacheId].html;
                    if (typeof html == 'undefined') {
                        $(window).on('visibleProductBlocksLoaded', function(e, rs) {
                            self._bindEvents(rs[cacheId].html);
                        })
                    } else {
                        self._bindEvents(html);
                    }
                } else {
                    this._bindEvents();
                }
            } else {
                this._bindEvents();
            }
        },
        _loadVisibleProductBlocks: function() {
            var self = this, conf = this.options;
            window.productGrpData = {};
            let i = 0;
            $('[data-jsproducts]').each(function() {
                var $block = $(this);
                self.element.removeAttr('jsproducts');
                if ($block.is(':visible')) {
                    i++;
                    let data = $block.data('jsproducts');
                    window.productGrpData[data.cache_key_info[0]] = data;
                    if (i > 3) return false;
                }
            });
            if (window.productGrpData) {
                $.get(conf.ajaxUrl, {block_groups: JSON.stringify(window.productGrpData)}, function(rs){
                    $(rs).appendTo('body');
                    $.each(window.productGrpData, function(id) {
                        window.productGrpData[id].html = $('#pd-' + id).val();
                        $('#pd-' + id).remove();
                    });
                    $(window).trigger('visibleProductBlocksLoaded', [window.productGrpData]);
                });
            }
            return window.productGrpData;
        },
        _bindEvents: function(html) {
            var self = this;
            this._checkVisible() ? this._ajaxFirstLoad(html) : setTimeout(function() {
                self._bindEvents(html);
            }, 100);
        },
        _ajaxFirstLoad: function(html) {
            var self = this, conf = this.options;
            if (html) self._attachHtml(html);
            else $.get(conf.ajaxUrl, conf.jsonData, function(html) {
                self._attachHtml(html);
            });
        },
        _attachHtml: function(html) {
            var self = this, conf = this.options, formKey = $(conf.formKeyInputSelector).first().val();
            self.element.html(html).removeClass('no-loaded');
            $('body').trigger('contentUpdated');
            self.element.find('[name="form_key"]').each(function() {
                var $field = $(this).val(formKey);
            });
            $('body').trigger('ajaxProductFirstTimeLoaded');
        }
    });
    return $.codazon.firstLoad;
});
