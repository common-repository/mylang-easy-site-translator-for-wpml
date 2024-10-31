<?php
/*
 * Settings for mylang.
 *
 */

defined('ABSPATH') || exit;
?>
<div class="wrap">
    <h2>myLang</h2>
    <br>
    <ul class="nav nav-tabs">
        <li class="active"><a href="#tab-mass">Mass Translation</a></li>
        <li><a href="#tab-glossary">Glossary</a></li>
    </ul>
    <div class="tab-content">
        <div id="tab-mass" class="tab-pane active">
            <?php
            require_once MYLANG_PLUGIN_PATH . '/admin/settings.php';
            require_once MYLANG_PLUGIN_PATH . '/admin/translate.php';
            ?>
            <div class="feedback">Please leave your feedback at <a href="https://wordpress.org/plugins/mylang-easy-site-translator-for-wpml/">myLang Easy Site Translator for WPML</a>. Thank you!</div>
        </div>
        <div id="tab-glossary" class="tab-pane">
            <?php
                require_once MYLANG_PLUGIN_PATH . '/admin/glossary.php';
            ?>
            <div class="feedback">Please leave your feedback at <a href="https://wordpress.org/plugins/mylang-easy-site-translator-for-wpml/">myLang Easy Site Translator for WPML</a>. Thank you!</div>
        </div>
    </div>
</div>

<?php
$langCodes = [];
foreach ($active_languages as $language) {
    $langCodes[] = $language['language_code'];
}
?>

<script>
    let glossary = {
        $_table: null,
        $_paginator: null,
        _paginatorInitialized: false,
        _search: '',
        _searchTimeout: null,
        // _lang: [{% for language in languages %}{% if not loop.first %},{% endif %}{{ language.language_id }}{% endfor %}],
        _lang: [<?php echo '"'.implode('","', $langCodes).'"' ?>],

        _showPage: function(page) {
            let that = this;
            jQuery.ajax({
                type: 'GET',
                url: ajax_object.ajax_url,
                data: {
                    action: 'get_page',
                    page: page,
                    search: this._search
                },
                contentType: "application/json;charset=utf-8",
                dataType: "json",
                success: function(data) {
                    that.$_table.html('');
                    for (let i in data.items) {
                        that._addItemToTable(data.items[i]);
                    }
                    that._refreshPaginator(data.page, data.pageCount);
                }
            });
        },

        _showSearchResult: function(search) {
            if (this._searchTimeout) {
                clearTimeout(this._searchTimeout);
            }
            this._searchTimeout = setTimeout(() => {
                this._search = search;
                this._showPage(0);
                this._searchTimeout = null;
            }, 1000);
        },

        _addItemToTable: function(data) {
            let $tr = jQuery('<tr></tr>');
            jQuery('<td data-id="original"></td>').text(data.original).appendTo($tr);
            if (data.translate.substr(0, 1) === '{') {
                data.translate = JSON.parse(data.translate);
            }
            for (let i = 0; i < this._lang.length; i++) {
                let id = this._lang[i];
                jQuery('<td data-id="' + id + '"></td>').data('name', 'translate[' + id + ']').text(typeof data.translate === 'object' ? data.translate[id] : data.translate).appendTo($tr);
            }
            jQuery('<td></td>').html('<button class="button button-sm button-default glossary-edit"><i class="fa fa-edit"></i></button> <button class="button button-sm button-danger glossary-remove"><i class="fa fa-trash"></i></button><input type="hidden" name="action" value="edit_item" /><input type="hidden" name="id" value="' + data.id + '" />').appendTo($tr);
            $tr.appendTo(this.$_table);
        },

        _refreshPaginator: function(page, count) {
            this.$_paginator.html('');
            if (page > 0) {
                this.$_paginator.append('<li class="page-item"><a class="page-link" href="#" data-page="' + (page - 1) + '">&laquo;</a></li>');
            }
            let start = Math.max(0, page - 5),
                end = Math.min(count - 1, page + 5);
            if (start > 0) {
                this.$_paginator.append('<li class="page-item"><a class="page-link" href="#" data-page="0">1</a></li><li class="page-item"><a class="page-link">...</a></li>');
            }
            for (let i = start; i <= end; i++) {
                this.$_paginator.append('<li class="page-item ' + (page === i ? 'active' : '') + '"><a class="page-link" href="#" data-page="' + i + '">' + (i + 1) + '</a></li>');
            }
            if (end < count - 1) {
                this.$_paginator.append('<li class="page-item"><a class="page-link">...</a></li><li class="page-item"><a class="page-link" href="#" data-page="' + (count - 1) + '">' + count + '</a></li>');
            }
            if (page < count - 1) {
                this.$_paginator.append('<li class="page-item"><a class="page-link" href="#" data-page="' + (page + 1) + '">&raquo;</a></li>');
            }
            if (!this._paginatorInitialized) {
                let that = this;
                this.$_paginator.on('click', 'a', function() {
                    let page = jQuery(this).data('page');
                    that._showPage(page);
                    return false;
                });
                this._paginatorInitialized = true;
            }
        },

        _addFormToTable: function() {
            let $tr = jQuery('<tr></tr>'),
                that = this;
            $tr.append('<td data-id="original"><input type="text" class="form-control" name="original" /></td>');
            for (let i = 0; i < this._lang.length; i++) {
                $tr.append('<td data-id="' + this._lang[i] + '"><input type="text" class="form-control" name="translate[' + this._lang[i] + ']" /></td>');
            }
            $tr.append('<td><input type="hidden" name="action" value="add_item" /><button class="button button-primary button-sm"><i class="fa fa-save"></i></button></td>');
            $tr.appendTo(this.$_table);
            $tr.find('.button-primary').click(function() {
                let $btn = jQuery(this);
                $btn.prop('disabled', true);
                $tr.find('.error').remove();
                jQuery.ajax({
                    type: 'POST',
                    url: ajax_object.ajax_url,
                    data: $tr.find('input').serialize(),
                    dataType: "json",
                    success: function(data) {
                        $btn.prop('disabled', false);
                        if (data.success) {
                            that._showPage(data.page);
                        } else {
                            for (let i in data.errors) {
                                if (!data.errors.hasOwnProperty(i)) {
                                    continue;
                                }
                                $tr.find('td[data-id=' + i + ']').append(jQuery('<small class="error"></small>').text(data.errors[i]));
                            }
                        }
                    }
                });
                return false;
            });
        },

        _editRow: function($tr) {
            $tr.find('td').each(function() {
                let $el = jQuery(this),
                    $input = jQuery('<input type="text" class="form-control" />'),
                    id = $el.data('id');
                if (id) {
                   if (id !== 'original') {
                        id = 'translate[' + id + ']';
                   }
                    $input.val($el.text());
                    $input.attr('name', id);
                    $el.html('').append($input);
                } else {
                    $el.find('button').remove();
                    jQuery('<button class="button button-primary button-sm"><i class="fa fa-save"></i></button>').prependTo($el);
                }
            });
            let that = this;
            $tr.find('.button-primary').click(function() {
                let $btn = jQuery(this);
                $btn.prop('disabled', true);
                $tr.find('.error').remove();
                jQuery.ajax({
                    type: 'POST',
                    url: ajax_object.ajax_url,
                    data: $tr.find('input').serialize(),
                    dataType: "json",
                    success: function(data) {
                        $btn.prop('disabled', false);
                        if (data.success) {
                            that._showPage(data.page);
                        } else {
                            for (let i in data.errors) {
                                if (!data.errors.hasOwnProperty(i)) {
                                    continue;
                                }
                                $tr.find('td[data-id=' + i + ']').append(jQuery('<small class="error"></small>').text(data.errors[i]));
                            }
                        }
                    }
                });
                return false;
            });
        },

        _initTable: function() {
            let that = this;
            this.$_table.on('click', '.glossary-edit', function() {
                that._editRow(jQuery(this).closest('tr'));
                return false;
            });
            this.$_table.on('click', '.glossary-remove', function() {
                if (!confirm('Are you sure you want to remove this item?')) {
                    return false
                }
                jQuery(this).prop('disabled', true);
                jQuery.ajax({
                    type: 'POST',
                    url: ajax_object.ajax_url,
                    data: jQuery(this).closest('tr').find('input').serialize().replace('edit_item', 'remove_item'),
                    success: function(data) {
                        that._showPage(data.page);
                    }
                });
                return false;
            });
        },

        init: function() {
            if (this.$_table) {
                return;
            }
            this.$_table = jQuery('#glossary-table tbody');
            this.$_paginator = jQuery('#glossary-paginator');
            this._showPage(0);
            let that = this;
            jQuery('#glossary-table_add').click(() => {
                this._addFormToTable();
                return false;
            });
            jQuery('#glossary-table_search').keyup(function() {
                that._showSearchResult(jQuery(this).val());
            });
            this._initTable();
        }
    };
    jQuery('.nav-tabs [href="#tab-glossary"]').on('click', () => {
        glossary.init();
    });

    // Put this in the shown event
    //glossary.init();
</script>