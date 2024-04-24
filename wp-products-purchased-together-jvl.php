<?php
/**
 * Plugin Name: WP Products Purchased Together JVL
 * Plugin URI: https://github.com/javiervilchezl/wp-products-purchased-together-jvl
 * Description: Shows other products that were purchased along with the current product. If none are found, it shows related products.
 * Version: 1.1
 * Requires at least: 5.8
 * Requires PHP: 5.6
 * Author: Javier Vílchez Luque
 * Author URI: https://github.com/javiervilchezl
 * Licence: License MIT
 *
 * Copyright 2023-2024 WP Products Purchased Together JVL - Javier Vílchez Luque (javiervilchezl)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}

/**
 * Verifica si WooCommerce está activo
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    /**
     * Añadir el menú de configuración al admin de WordPress
     */
    function wppptjvl_add_admin_menu() {
        add_menu_page(
            'WP Products Purchased Together JVL',
            'Purchased Together JVL',
            'manage_options',
            'wp_products_purchased_together_jvl',
            'wppptjvl_settings_page',
            'dashicons-cart'
        );
    }
    add_action('admin_menu', 'wppptjvl_add_admin_menu');

    /**
     * Función de activación del plugin
     */
    function wppptjvl_activate() {
        $default_options = array(
            'wppptjvl_text_field_0' => 'Otros usuarios también compraron',
            'wppptjvl_number_field_1' => 6,
            'wppptjvl_select_field_2_render' => 'h2'
        );
        add_option('wppptjvl_settings', $default_options);
    }
    register_activation_hook(__FILE__, 'wppptjvl_activate');

    /**
     * Registrar opciones de configuración
     */
    function wppptjvl_settings_init() {
        register_setting('wppptjvl', 'wppptjvl_settings');

        add_settings_section(
            'wppptjvl_wppptjvl_section',
            __('Configuración de WP Products Purchased Together JVL', 'wp_products_purchased_together_jvl'),
            'wppptjvl_settings_section_callback',
            'wppptjvl'
        );

        add_settings_field(
            'wppptjvl_text_field_0',
            __('Título del bloque de productos', 'wp_products_purchased_together_jvl'),
            'wppptjvl_text_field_0_render',
            'wppptjvl',
            'wppptjvl_wppptjvl_section'
        );

        add_settings_field(
            'wppptjvl_number_field_1',
            __('Cantidad máxima de productos', 'wp_products_purchased_together_jvl'),
            'wppptjvl_number_field_1_render',
            'wppptjvl',
            'wppptjvl_wppptjvl_section'
        );

        add_settings_field(
            'wppptjvl_select_field_2',
            __('Etiqueta para el título', 'wp_products_purchased_together_jvl'),
            'wppptjvl_select_field_2_render',
            'wppptjvl',
            'wppptjvl_wppptjvl_section'
        );
    }
    add_action('admin_init', 'wppptjvl_settings_init');

    /**
     * Renderizar campo de texto para el título
     */
    function wppptjvl_text_field_0_render() {
        $options = get_option('wppptjvl_settings');
        ?>
        <input size="50" type='text' name='wppptjvl_settings[wppptjvl_text_field_0]' value='<?php echo $options['wppptjvl_text_field_0']; ?>'>
        <?php
    }

    /**
     * Renderizar campo numérico para la cantidad máxima de productos
     */
    function wppptjvl_number_field_1_render() {
        $options = get_option('wppptjvl_settings');
        ?>
        <input min="1" max="999" type='number' name='wppptjvl_settings[wppptjvl_number_field_1]' value='<?php echo $options['wppptjvl_number_field_1']; ?>'>
        <?php
    }

    /**
     * Renderizar campo select para la etiqueta del título
     */
    function wppptjvl_select_field_2_render() {
        $options = get_option('wppptjvl_settings');
        $tag = $options['wppptjvl_select_field_2'] ?? 'h2';
        ?>
        <select name='wppptjvl_settings[wppptjvl_select_field_2]'>
            <option value='h1' <?php selected($tag, 'h1'); ?>>h1</option>
            <option value='h2' <?php selected($tag, 'h2'); ?>>h2</option>
            <option value='h3' <?php selected($tag, 'h3'); ?>>h3</option>
            <option value='h4' <?php selected($tag, 'h4'); ?>>h4</option>
            <option value='h5' <?php selected($tag, 'h5'); ?>>h5</option>
            <option value='h6' <?php selected($tag, 'h6'); ?>>h6</option>
            <option value='p' <?php selected($tag, 'p'); ?>>p</option>
            <option value='span' <?php selected($tag, 'span'); ?>>span</option>
            <option value='div' <?php selected($tag, 'div'); ?>>div</option>
        </select>
        <?php
    }

    /**
     * Callback para la sección de configuraciones
     */
    function wppptjvl_settings_section_callback() {
        echo __('Personaliza el título y la cantidad de productos mostrados.', 'wp_products_purchased_together_jvl');
    }

    /**
     * Página de configuración
     */
    function wppptjvl_settings_page() {
        ?>
        <form action='options.php' method='post'>
            <h2>WP Products Purchased Together JVL</h2>
            <?php
            settings_fields('wppptjvl');
            do_settings_sections('wppptjvl');
            submit_button();
            ?>
        </form>
        <?php
    }

    /**
     * Mostrar productos relacionados en la página del producto.
     */
    function wppptjvl_display_related_products() {
        global $product, $wpdb;

        if (!$product) {
            return;
        }

        $options = get_option('wppptjvl_settings');
        $title = isset($options['wppptjvl_text_field_0']) ? $options['wppptjvl_text_field_0'] : 'Otros usuarios también compraron';
        $max_products = isset($options['wppptjvl_number_field_1']) ? (int) $options['wppptjvl_number_field_1'] : 6;
        $title_tag = $options['wppptjvl_select_field_2'] ?? 'h2';

        $product_id = $product->get_id();

        // Consulta SQL para encontrar IDs de productos comprados junto con este producto.
        $query = "
            SELECT meta.meta_value AS product_id
            FROM {$wpdb->prefix}woocommerce_order_itemmeta meta
            JOIN {$wpdb->prefix}woocommerce_order_items items ON meta.order_item_id = items.order_item_id
            WHERE items.order_id IN (
                SELECT order_id FROM {$wpdb->prefix}woocommerce_order_items items
                JOIN {$wpdb->prefix}woocommerce_order_itemmeta meta ON items.order_item_id = meta.order_item_id
                WHERE meta.meta_key = '_product_id' AND meta.meta_value = %d
            )
            AND meta.meta_key = '_product_id' AND meta.meta_value != %d
            GROUP BY meta.meta_value
            ORDER BY COUNT(*) DESC
            LIMIT {$max_products}
        ";

        $related_ids = $wpdb->get_col($wpdb->prepare($query, $product_id, $product_id));

        if (empty($related_ids)) {
            woocommerce_output_related_products();
            return;
        }

        // Mostrar los productos utilizando el formato estándar de WooCommerce
        echo '<' . esc_html($title_tag) . '>' . esc_html($title) . '</' . esc_html($title_tag) . '>';
        woocommerce_product_loop_start();
        foreach ($related_ids as $id) {
            $related_product = wc_get_product($id);
            if (!$related_product) {
                continue;
            }
            $post_object = get_post($related_product->get_id());
            setup_postdata($GLOBALS['post'] =& $post_object);
            wc_get_template_part('content', 'product');
            wp_reset_postdata();
        }
        woocommerce_product_loop_end();
    }

    add_action('woocommerce_after_single_product', 'wppptjvl_display_related_products', 15);
}
