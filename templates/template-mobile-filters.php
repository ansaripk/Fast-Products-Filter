<div id="filters">
    <div class="clear-filters">
    <a class="btn-clear-filters" href="#"> <?php esc_html_e('Clear Filters', 'fast-products-filter') ?></a>
</div>
<?php

foreach( $filters_order as $index_number ) {

    if( $index_number == 'categories') {
        if( get_option('categories') ) {
            echo esc_html('<h5>' . __('Categories', 'fast-products-filter') .'</h5>');
            $this->categories_filter();
            continue;     
        }

    }elseif ($index_number == 'price') {
        $this->mobile_price_slider();
        continue;
    }

    foreach( $attribute_taxonomies as $attrib ) {
        
        $style = get_option( $attrib->attribute_name . '_style');
        if($style == 'Hide') {
            continue;
        }
        
        if( $attrib->attribute_name === $index_number ) {

            //$terms = get_terms('pa_'.$attrib->attribute_name, array('hide_empty' => false));
            $args = array(
                'taxonomy'      => 'pa_'.$attrib->attribute_name,
                'hide_empty'    => false
            );
            $terms = get_categories( $args );
            $args = [
                'heading'           => $attrib->attribute_label,
                'attribute_name'    => $attrib->attribute_name
            ];

            Filter_Frontend::show($args, $terms);
        }
    }
}

echo '</div>';
