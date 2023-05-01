<?php 
/*
    * Plugin Name: Bulk Update Product Stock
    * Author: Shojib Khan
    * Plugin URI: https://wa.link/cwtr6b
    * Description: Bulk Update Product Stock
    * Version: 1.0
    * Author URI: https://wa.link/cwtr6b
    * Text Domain: bus

*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// if woo is not active then add notice
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    add_action( 'admin_notices', 'bus_woo_not_active_notice' );
    return;
}

// if woo is active then add menu
add_action( 'admin_menu', 'bus_add_menu' );

function bus_add_menu() {
    add_menu_page( 'Bulk Update Product Stock', 'Bulk Update Product Stock', 'manage_options', 'bus', 'bus_page', 'dashicons-cart', 6 );
}

function bus_page() {
    ?>
    <div class="wrap bus-ajax-wrap">
        <h1>Bulk Update Product Stock</h1>
        <form action="" method="post">
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="manage_stock">Manage Stock</label>
                        </th>
                        <td>
                            <select name="manage_stock" id="manage_stock">
                                <option value="yes">Yes</option>
                                <option value="no">No</option>
                            </select>
                            <p class="description">Select manage stock</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="bus_stock">Stock</label>
                        </th>
                        <td>
                            <input type="number" name="bus_stock" id="bus_stock" class="regular-text" placeholder="Enter stock">
                            <p class="description">Enter stock</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="bus_stock_status">Stock Status</label>
                        </th>
                        <td>
                            <select name="bus_stock_status" id="bus_stock_status">
                                <option value="instock">In Stock</option>
                                <option value="outofstock">Out of Stock</option>
                            </select>
                            <p class="description">Select stock status</p>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p class="submit">
                <input type="submit" name="bus_submit" id="bus_submit" class="button button-primary" value="Update">
            </p>
        </form>

        <div class="update">
        </div>

        <div class="plugin-copyright">
            <p>Plugins was developed by <a href="https://wa.link/cwtr6b" target="_blank">Shojib Khan</a>. Contact me on <a href="https://wa.link/cwtr6b" target="_blank">WhatsApp</a> for any help.</p>
        </div>
    </div>
    <?php
}

function bus_woo_not_active_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php _e( 'WooCommerce is not active. Please activate WooCommerce to use Bulk Update Product Stock plugin.', 'bus' ); ?></p>
    </div>
    <?php
}

// style 
add_action( 'admin_head', 'bus_admin_head' );
function bus_admin_head() {
    ?>
    <style>
        .update{
            background: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            margin-top: 20px;
            height: 150px;
            overflow: auto;

        }
        .bus-ajax-wrap form.loading{
            background: url(<?php echo admin_url( 'images/spinner.gif' ); ?>) no-repeat center center;
            opacity: 0.5;   
            pointer-events: none;
        }
    </style>
    <?php
}

// script
add_action( 'admin_footer', 'bus_admin_footer' );
function bus_admin_footer() {
    ?>
    <script>
        jQuery(document).ready(function($){

            function bus_update(offset = 0){
                var container = $('.bus-ajax-wrap form');

                container.addClass('loading');

                $.ajax({
                    url: ajaxurl,
                    type: 'post',
                    data: {
                        action: 'bus_update',
                        bus_stock: $('#bus_stock').val(),
                        bus_stock_status: $('#bus_stock_status').val(),
                        manage_stock: $('#manage_stock').val(),
                        offset: offset
                    },
                    success: function(response){

                        console.log(response);

                        $('.update').append(response.message);

                        // scroll to bottom
                        $('.update').animate({
                            scrollTop: $('.update').get(0).scrollHeight
                        }, 200);



                        if(response.offset){
                            bus_update(offset + 1);
                        }else{
                            container.removeClass('loading');
                        }
                    }
                });
            }


            $('#bus_submit').click(function(e){
                e.preventDefault();
                bus_update();
            });
        });
    </script>
    <?php
}

add_action( 'wp_ajax_bus_update', 'bus_update' );
function bus_update() {
    $response = array();

    $response['message'] = '';


    $bus_stock = $_POST['bus_stock'];
    $bus_stock_status = $_POST['bus_stock_status'];
    $manage_stock = $_POST['manage_stock'];
    $offset = $_POST['offset'] ? $_POST['offset'] : 0;

    // if bus_product_ids is empty then get all product ids
    $args = array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'offset' => $offset,
    );
    
    $bus_product_ids = get_posts( $args );
    
    // loop through product ids, if variable product then get all variations and update stock

    if ( ! empty( $bus_product_ids ) ) {
        foreach ( $bus_product_ids as $bus_product_id ) {
            $product = wc_get_product( $bus_product_id );

            $product_type = $product->get_type();

            if ( $product_type == 'variable' ) {
                $variations = $product->get_available_variations();

                foreach ( $variations as $variation ) {
                    $variation_id = $variation['variation_id'];
                    $variation_product = wc_get_product( $variation_id );

                    $variation_product->set_manage_stock( $manage_stock );
                    if($bus_stock){
                        $variation_product->set_stock_quantity( $bus_stock );
                    }
                    $variation_product->set_stock_status( $bus_stock_status );
                    $variation_product->save();

                    $response['status'] = 'success';
                    $response['message'] .= 'Variation product id: ' . $variation_id . ' updated <br>';


                }
            } else {
                $product->set_manage_stock( $manage_stock );
                if($bus_stock){
                    $product->set_stock_quantity( $bus_stock );
                }
                $product->set_stock_status( $bus_stock_status );
                $product->save();

                $response['status'] = 'success';
                $response['message'] .= 'Product id: ' . $bus_product_id . ' updated <br>';
            }
        }

        $response['offset'] = $offset + 1;

    }else{
        $response['status'] = 'error';
        $response['message'] = 'No product found';

    }

    wp_send_json( $response );

    die();
}



