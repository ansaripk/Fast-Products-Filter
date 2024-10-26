<?php

class Settings_Sorting {

    public function __construct() {
        //add_action( 'admin_init', [$this, 'setup_fields' ] );
    }


    public function setup_fields() {

        add_settings_section(
            'fwf_filter_section_id', // ID
            __('Filters Order', 'fast-products-filter'), // Title
            [$this, 'filter_orders_callback'], // Callback
            'filters-order-tab' // Page
        );  

	
	    add_settings_field(
            'fwf_filters_order_field', // ID
            __('Filters Order', 'fast-products-filter'), // Title 
            [$this, 'filter_orders_field_callback'], // Callback
            'filters-order-tab', // Page
            'fwf_filter_section_id' // Section           
        );        

	    add_settings_field(
            'fwf_filters_saved_field', // ID
            __('Filters Saved', 'fast-products-filter'), // Title 
            [$this, 'filters_saved_field_callback'], // Callback
            'filters-order-tab', // Page
            'fwf_filter_section_id', // Section           
        );        

        register_setting( 'filters-order-tab', 'fwf_filters_order_field' );
        register_setting( 'filters-order-tab', 'fwf_filters_saved_field' );
    }

    public function filter_orders_callback() {
        
    }

    public function filter_orders_field_callback() {
        $val = get_option('fwf_filters_order_field');
        echo '<input id="fwf_filters_order_field" type="text" name="fwf_filters_order_field" value="'. esc_attr($val) . '" />';
    }

    public function filters_saved_field_callback() {
        $val = get_option('fwf_filters_saved_field');
        echo '<input id="fwf_filters_saved_field" type="hidden" name="fwf_filters_saved_field" value="'. esc_attr($val) . '" />';
    }

    public function sortable() { 
        
        $attrib = FWF_Helper::get_attributes();
        $filters_sections = [];

        foreach( $attrib as $item ) {
            $filters_sections[] = array(
                'id' => $item['id'], 
                'label' => $item['label']
            );
        }
        ?>
<div id="sorting">

    <div class="col">
        <h3><?php esc_html_e('Available Taxonomies', 'fast-products-filter') ?></h3>
        <p class="big">
            <?php esc_html_e('Add items from available options by clicking on + sign.', 'fast-products-filter') ?></p>
        <div id="source-wrap">
            <ul id="source">
                <li data-id="categories" class="ui-state-default">Categories<a class="add-option" href="#"></a></li>
                <?php foreach( $filters_sections as $item ): ?>
                <li data-id="<?php echo esc_attr($item['id']) ?>" class="ui-state-default">
                    <?php echo esc_attr($item['label']) ?><a class="add-option" href="#"></a></li>
                <?php endforeach ?>

                <li data-id="price" class="ui-state-default">Price<a class="add-option" href="#"></a></li>
            </ul>
        </div>

    </div>

    <div class="col">
        <h3><?php esc_html_e('Filter Taxonomies', 'fast-products-filter') ?></h3>
        <p class="big">
            <?php esc_html_e('Sort items in desired order by click and drag up/down.', 'fast-products-filter') ?><br>
            <?php esc_html_e('Don\'t forget to save settings after adding items.', 'fast-products-filter') ?></p>
        <div id="sortable-wrap">
            <ul id="sortable">
                <?php 
                    $args = array(
                        'li'     => array(
                            'data-id' => array(),
                            'class'     => array(),
                            'style'     => array(),
                            'a'         => array(
                                'class' => array()
                            )
                        )
                    );
                    if( get_option('fwf_filters_saved_field') ) {
                        $sortable = base64_decode(get_option('fwf_filters_saved_field'));
                        echo wp_kses($sortable, $args);
                    } 
                ?>

            </ul>
        </div>
    </div>
</div>
<script>
jQuery(document).ready(function($) {
    jQuery('form h2').hide();
    jQuery('form table').hide();

    jQuery('a.add-option').on('click', function(e) {
        e.preventDefault();
        var li = $(this).closest('li').clone();
        li.find('a').remove();
        var txt = $.trim(li.html());
        var dup = false;
        $.each($('ul#sortable li'), function(index) {
            if ($.trim($(this).html()) == txt) {
                dup = true;
            }
        });

        if (dup == false) {
            li.append('<a class="remove-option"></a>');
            $('ul#sortable').append(li);
            $("ul#sortable").sortable({
                refresh: sortable
            });
            show_orders();
        } else {
            alert('option is already included.');
        }

    });

    jQuery(document).on('click', 'a.remove-option', function(e) {
        e.preventDefault();
        var li = $(this).closest('li');
        li.remove();
        $("ul#sortable").sortable({
            refresh: sortable
        });
        show_orders();

        return;

    });

    jQuery("#sortable").sortable();
    jQuery("#sortable").sortable({
        stop: function(event, ui) {
            var new_order = jQuery(this).sortable('serialize');
            show_orders();
        }
    });

    function show_orders() {
        var Base64 = {
            _keyStr: "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",
            encode: function(e) {
                var t = "";
                var n, r, i, s, o, u, a;
                var f = 0;
                e = Base64._utf8_encode(e);
                while (f < e.length) {
                    n = e.charCodeAt(f++);
                    r = e.charCodeAt(f++);
                    i = e.charCodeAt(f++);
                    s = n >> 2;
                    o = (n & 3) << 4 | r >> 4;
                    u = (r & 15) << 2 | i >> 6;
                    a = i & 63;
                    if (isNaN(r)) {
                        u = a = 64
                    } else if (isNaN(i)) {
                        a = 64
                    }
                    t = t + this._keyStr.charAt(s) + this._keyStr.charAt(o) + this._keyStr.charAt(u) +
                        this._keyStr.charAt(a)
                }
                return t
            },
            decode: function(e) {
                var t = "";
                var n, r, i;
                var s, o, u, a;
                var f = 0;
                e = e.replace(/[^A-Za-z0-9\+\/\=]/g, "");
                while (f < e.length) {
                    s = this._keyStr.indexOf(e.charAt(f++));
                    o = this._keyStr.indexOf(e.charAt(f++));
                    u = this._keyStr.indexOf(e.charAt(f++));
                    a = this._keyStr.indexOf(e.charAt(f++));
                    n = s << 2 | o >> 4;
                    r = (o & 15) << 4 | u >> 2;
                    i = (u & 3) << 6 | a;
                    t = t + String.fromCharCode(n);
                    if (u != 64) {
                        t = t + String.fromCharCode(r)
                    }
                    if (a != 64) {
                        t = t + String.fromCharCode(i)
                    }
                }
                t = Base64._utf8_decode(t);
                return t
            },
            _utf8_encode: function(e) {
                e = e.replace(/\r\n/g, "\n");
                var t = "";
                for (var n = 0; n < e.length; n++) {
                    var r = e.charCodeAt(n);
                    if (r < 128) {
                        t += String.fromCharCode(r)
                    } else if (r > 127 && r < 2048) {
                        t += String.fromCharCode(r >> 6 | 192);
                        t += String.fromCharCode(r & 63 | 128)
                    } else {
                        t += String.fromCharCode(r >> 12 | 224);
                        t += String.fromCharCode(r >> 6 & 63 | 128);
                        t += String.fromCharCode(r & 63 | 128)
                    }
                }
                return t
            },
            _utf8_decode: function(e) {
                var t = "";
                var n = 0;
                var r = c1 = c2 = 0;
                while (n < e.length) {
                    r = e.charCodeAt(n);
                    if (r < 128) {
                        t += String.fromCharCode(r);
                        n++
                    } else if (r > 191 && r < 224) {
                        c2 = e.charCodeAt(n + 1);
                        t += String.fromCharCode((r & 31) << 6 | c2 & 63);
                        n += 2
                    } else {
                        c2 = e.charCodeAt(n + 1);
                        c3 = e.charCodeAt(n + 2);
                        t += String.fromCharCode((r & 15) << 12 | (c2 & 63) << 6 | c3 & 63);
                        n += 3
                    }
                }
                return t
            }
        }
        let temp = [];
        $.each($('ul#sortable li'), function(item) {
            let id = $(this).data('id');
            temp.push(id);
        });

        if ($('ul#sortable li').length == 0) {
            temp = [];
        }

        $('#fwf_filters_order_field').val(temp);
        let html = Base64.encode($('ul#sortable').html());
        $('#fwf_filters_saved_field').val(html);
    }

});
</script>


<?php

    }
    
    public function render_page() {
        $this->sortable();
        settings_fields( 'filters-order-tab' );
        do_settings_sections('filters-order-tab');
        submit_button();
    }
}

//new Settings_Sorting;