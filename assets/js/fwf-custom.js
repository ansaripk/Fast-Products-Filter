(function($) {
    "use strict";
    
    // variables for use in filters
    var ajax_url = ajax_object.ajaxurl;
    var fwf_labels = JSON.parse(ajax_object.labels);
    var fwf_include_slider = ajax_object.slider;
    var fwf_filter = [];
    var fwf_categories = [];
    var fwf_price = {
        minPrice: 10,
        maxPrice: 500
    }

    var fwf_include_price = false;
    var startValues = [ajax_object.min_price, ajax_object.max_price];
    var fwf_total = 0;
    var fwf_next_page = 2;

    fwf_labels.forEach(function(item){
        let arr = {
            tax: 'pa_'+item.attribute_name,
            slug: []
        }
        fwf_filter.push(arr);
    });

    fwf_filter.push({tax: 'min_price', slug: [fwf_price.minPrice]});
    fwf_filter.push({tax: 'max_price', slug: [fwf_price.maxPrice]});
    

    // add categories in filters
    function add_cat(key, value) {
        fwf_categories.push(value);
        let arr = fwf_categories.filter(function(value, index, array) {
            return array.indexOf(value) === index;
        });

        fwf_categories = arr;
        update_query(fwf_filter);
        return;
    }

    // update query
    // This function will make ajax calls based on selected filter options.
    
    function update_query( arr, page = 1 ) {
        var queryString = '';
        let params = [];

        arr.forEach(function(item){
            if( item.slug != '' && item.tax != 'min_price' && item.tax != 'max_price') {
                params.push( item.tax.slice(3) + '=' + decodeURIComponent( item.slug.join(',') ));
            }
        });

        if( fwf_categories.length ) {
            params.push( 'cats=' + decodeURIComponent( fwf_categories.join(',') ));
        }

        if( fwf_include_price ) {
            params.push( 'min_price=' + decodeURIComponent( fwf_price.minPrice ));
            params.push( 'max_price=' + decodeURIComponent( fwf_price.maxPrice ));
        }

        if( params.length ) {
            $('div.clear-filters').css('display', 'flex');
            queryString = window.location.origin + window.location.pathname + '?'
            for( var index in params ) {
                if( index > 0 ) {
                    queryString += '&' + params[index];
                } else {
                    queryString += params[index];
                }
            }
        } else {
            queryString = window.location.origin + window.location.pathname;
            params = [];
            $('div.clear-filters').css('display', 'none');
        }

        window.history.pushState('', '', queryString);
        var overlay = '<div id="overlay"><div class="cv-spinner"><span class="spinner"></span></div></div>';

        
        $.ajax({
            type: 'post',
            url: ajax_url,
            dataType : 'json',
            data: {
                action: 'do_filter',
                nonce: ajax_object.nonce,
                query: params,
                page_num: page,
            },

            beforeSend: function() {
                $('body').append(overlay);
                $("#overlay").fadeIn(300);
            },
            success: function(data) {
                console.log(data);

                if( data.posts ) {
                    $('ul.products').html(data.posts);
                }

                if( data.navi ) {
                    let nav = document.querySelector('.woocommerce-pagination');
                    if( typeof(nav) == 'undefined' || nav == null ) {
                        $('ul.products').after('<nav class="woocommerce-pagination"></nav>');
                    }
                    $('nav.woocommerce-pagination').html(data.navi);
                } else {
                    $('nav.woocommerce-pagination').html('');
                }

                if( data.count ) {
                    $('p.woocommerce-result-count').html(data.count);
                }

                if( data.loadmore ) {
                    fwf_total = data.loadmore.total;
                    $('a#btn-loadmore').attr('data-current', 1);
                    $('a#btn-loadmore').attr('data-total', fwf_total);
                    fwf_next_page = 2;
                }

                $("#overlay").remove();

            }
        });

        params = [];

    }

    /* 
        Update filter function will be called on page refresh
        read url parameters and will set filter options on/off
    */

    function update_filter() {
        
        let params = new URLSearchParams(location.search);
        let min = 0;
        let max = 0;

        params.forEach(function(value, key){ 
            for( let index in fwf_filter ){
                if( fwf_filter[index].tax == 'pa_'+key ) {
                    fwf_filter[index].slug = value.split(',');
                }

                if( fwf_filter[index].tax == 'min_price' && key == 'min_price' ) {
                    fwf_filter[index].slug = [value];
                }

                if( fwf_filter[index].tax == 'max_price' && key == 'max_price' ) {
                    fwf_filter[index].slug = [value];
                }
            }

        });

        params.forEach(function(value, key){
            let n_value = value.split(',');
            if( n_value ) {
                n_value.forEach(function(item){
                    $('input[data-slug='+item+']').prop('checked', true);
                    $('option[data-slug='+item+']').prop('selected', true);
                    $('a[data-slug='+item+']').addClass('active');
                });
            }
            if( key == 'cats' ) {
                if( n_value ) {
                    n_value.forEach(function(item){ 
                        fwf_categories.push(item);
                    });
                }
            }

            if( key == 'min_price') {
               min = value;
            }
            if( key == 'max_price') {
                max = value;
                startValues = [min, max];
            }
        });
        
        //update_query(filter)
    }

    // clear filters function will clear all selected filters

    function clear_filters() {

        for( let index in fwf_filter ) {
            fwf_filter[index].slug = [];
        }
        fwf_categories = [];
        // price.maxPrice = 0;
        // price.minPrice = 0;
        fwf_include_price = false;

        $('input.filter-checkbox').prop('checked', false);
        $('a.filter-button').removeClass('active');
        $('div.clear-filters').css('display', 'none');
        update_query(fwf_filter, 1);
        return;
    }
    
    
    jQuery(document).ready(function($){
        var mobile_filters = $('#mobile-filters-wrap').html();

        update_filter();
        $('body').prepend('<div id="mobile-filters-panel"></div>');
        $('div#mobile-filters-panel').html(mobile_filters);
        $('div#mobile-filters-wrap').remove();
   
        $('ul.filter-list li input[type="checkbox"]').on('click', function(e){

            var chkbox = $(this);
            let tax = chkbox.data('tax');
            let slug = chkbox.data('slug');
            let index;
            chkbox.prop('disabled', true);

            if( chkbox.prop('checked') == true ) {

                for( index in fwf_filter ) {
                    if( fwf_filter[index].tax == tax ) {
                        let uniq = fwf_filter[index].slug;
                        uniq.push(slug);

                        let arr = uniq.filter(function(value, index, array) {
                            return array.indexOf(value) === index;
                        });

                        fwf_filter[index].slug = arr;
                    } 
                }
                //console.log(filter);
            } else {
                for( index in fwf_filter ) { 
                    if( fwf_filter[index].tax == tax ) {
                        let arr = fwf_filter[index].slug;
                        const loc = arr.indexOf(slug);
                        if (loc > -1) { 
                            arr.splice(loc, 1); 
                            fwf_filter[index].slug = arr;
                        }
                    }
                }
            }
            chkbox.prop('disabled', false);
            update_query(fwf_filter);
        });

   
    
   
        $('#filters a').on('click', function(e){
            e.preventDefault();
            let id = $(this).data('id');
            let tax = $(this).data('tax');
            return;
    
            
            $.ajax({
                type: 'post',
                url: ajax_url,
                data: {
                    action: 'do_filter',
                    term: id,
                    tax: tax
                },
                success: function(data) {
                    console.log(data);
                    $('ul.products').html(data);
                }
            });
    
        });


        // if price attribute is included then initialize price sliders
        var el =  document.getElementById('slider');

        if( fwf_include_slider == 'yes' && el != null ) { 

            var slider = document.querySelector('#slider');
            var valueMin = document.querySelector('#min');
            var valueMax = document.querySelector('#max');

            //var slider = $('#price-slider');

            noUiSlider.create(slider, {
                start: startValues,
                step: 20,
                connect: true,
                range: {
                    'min': parseInt(ajax_object.min_price),
                    'max': parseInt(ajax_object.max_price)
                }
            });        

            slider.noUiSlider.on("update", function (values, handle) {
                if (handle) {
                    let val = parseInt(values[handle]);
                    valueMax.innerHTML = val;
                    fwf_price.maxPrice = val;
                } else {
                    let val = parseInt(values[handle]);
                    valueMin.innerHTML = val;
                    fwf_price.minPrice = val;
                }
            });  

            slider.noUiSlider.on("change", function (values, handle) {
                if (handle) {
                    let val = parseInt(values[handle]);
                    valueMax.innerHTML = val;
                    fwf_price.maxPrice = val;
                } else {
                    let val = parseInt(values[handle]);
                    valueMin.innerHTML = val;
                    fwf_price.minPrice = val;
                }
                fwf_include_price = true;
                update_query(fwf_filter);
            });  

            /* ------------------------------------ mobile price slider ---------------------------------------- */

            var m_slider = document.querySelector('#mobile-slider');
            var m_valueMin = document.querySelector('#mobile-min');
            var m_valueMax = document.querySelector('#mobile-max');

            //var slider = $('#price-slider');

            noUiSlider.create(m_slider, {
                start: startValues,
                step: 20,
                connect: true,
                range: {
                    'min': parseInt(ajax_object.min_price),
                    'max': parseInt(ajax_object.max_price)
                }
            });        

            m_slider.noUiSlider.on("update", function (values, handle) {
                if (handle) {
                    let val = parseInt(values[handle]);
                    m_valueMax.innerHTML = val;
                    fwf_price.maxPrice = val;
                } else {
                    let val = parseInt(values[handle]);
                    m_valueMin.innerHTML = val;
                    fwf_price.minPrice = val;
                }
            });  

            m_slider.noUiSlider.on("change", function (values, handle) {
                if (handle) {
                    let val = parseInt(values[handle]);
                    m_valueMax.innerHTML = val;
                    fwf_price.maxPrice = val;
                } else {
                    let val = parseInt(values[handle]);
                    m_valueMin.innerHTML = val;
                    fwf_price.minPrice = val;
                }
                fwf_include_price = true;
                update_query(fwf_filter);
            });          

        } // endif fwf_include_slider
        
        $(document).on("click", 'span.arrow-link', function (e) {
            if (e.target != this) return;
            $('#height-fix').css('height', 'auto');
            $('#height-fix').css('margin-bottom', '20px');
            //$(this).parent("li").next().toggle("slow");
            let div = $(this).parent("li").next();
            if( div.is(':visible') ) {
                $(this).parent("li").next().slideUp(300);
            } else {
                $(this).parent("li").next().slideDown(300);
            }
            
            //console.log('arrow link');
            
            
            //$(this).css('transform', 'rotate(180deg)');
            $(this).toggleClass("arrow-toggle");
            e.stopPropagation();
        });
    
        $(document).on('click', 'ul.filter-cats a', function(e){
            e.preventDefault();
            let id = $(this).parent('li').data('id');
            add_cat('cats', id);
        });
    
        $( 'select.filter-select' ).on('change',function(e){
            let tax = $('option:selected', this).data('tax');
            let slug = $('option:selected', this).data('slug');
            
            for( let index in fwf_filter ) {
                if( fwf_filter[index].tax == tax ) {
   
                    fwf_filter[index].slug = [slug];
                } 
            }
            update_query(fwf_filter);
        });


        $('a.filter-button').on('click', function(e){
            e.preventDefault();
            let tax = $(this).data('tax');
            let slug = $(this).data('slug');
            let index;

            if( !$(this).hasClass('active') ) {
                $(this).addClass('active');
                for( index in fwf_filter ) {
                    if( fwf_filter[index].tax == tax ) {
                        let uniq = fwf_filter[index].slug;
                        uniq.push(slug);

                        let arr = uniq.filter(function(value, index, array) {
                            return array.indexOf(value) === index;
                        });

                        fwf_filter[index].slug = arr;
                    } 
                }
            } else {
                $(this).removeClass('active');
                for( index in fwf_filter ) { 
                    if( fwf_filter[index].tax == tax ) {
                        let arr = fwf_filter[index].slug;
                        const loc = arr.indexOf(slug);
                        if (loc > -1) { 
                            arr.splice(loc, 1); 
                            fwf_filter[index].slug = arr;
                        }
                    }
                }
            }
            update_query(fwf_filter);
        });

        $(document).on('click', 'a.btn-clear-filters', function(e){
            e.preventDefault();
            clear_filters();
            return;
        });

        $(document).on('click', 'a.btn-filters', function(e){
            e.preventDefault();
            $('body').prepend('<div class="af-overlay"></div>');
            $('body').addClass('noscroll');
            $('#mobile-filters-panel').show();
            $('#mobile-filters-panel').addClass('slide-in');
        });

        $(document).on('click', 'a.mobile-panel-close, div.af-overlay', function(e){
            e.preventDefault();
            $('#mobile-filters-panel').removeClass('slide-in');
            $('div.af-overlay').remove();
            $('body').removeClass('noscroll');

        });

        $(document).on('click', 'nav.woocommerce-pagination a', function(e){
            e.preventDefault();
            let page_num = $(this).closest('li').data('pagenum');
            update_query(fwf_filter, page_num);
        });

        

        $(document).on('click', 'a#btn-loadmore', function(e){
            e.preventDefault();
            let current = $(this).data('current');
            let total = $(this).data('total');
            let ppp = $(this).data('ppp');
            let query = $(this).data('query');
            var url = new URL(window.location);
            let params = new URLSearchParams(url.search);
            var param_arr = [];

            for( const [key,value] of params ) {
                param_arr.push({'key' : key, 'value' : value});
            }
            //console.log(param_arr);      return;
   
            if( fwf_next_page > total ) {
                if( !$('p.no-more-load').length ) {
                    $('a#btn-loadmore').after('<p class="no-more-load">No more products to show.</p>');
                    setTimeout(function() { $("p.no-more-load").remove(); }, 5000);
                }
                return;
            }
                
            //console.log(param_arr); return;
    
            let fd = new FormData();
            fd.append('action', 'leo_loadmore');
            fd.append('page', fwf_next_page);
            fd.append('total', total);
            fd.append('params', JSON.stringify(param_arr));
            fd.append('query', query);
            fd.append('ppp', ppp);
            
    
            $.ajax({
                type: "post",
                url: ajax_url,
                contentType: false,
                processData: false,
                dataType : 'json',
                data: fd,

                beforeSend: function() {
                    $('a#btn-loadmore').after('<div class="ajax-loading"></div>');
                },
                success: function(data) {
                    $('ul.products').append(data.posts);
                    $('div.ajax-loading').remove();
                    fwf_next_page = data.next_page;
                    $('a#btn-loadmore').removeClass('loading');
                    $('a#btn-loadmore').attr('data-current', fwf_next_page-1);
                    $('html,body').animate({scrollTop: $('#btn-loadmore').offset().top-400},'slow');

                    //console.log(data);

                }
            });
        });
        


    }); // document ready end


})(jQuery);

