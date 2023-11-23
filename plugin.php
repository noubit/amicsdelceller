<?php

// Plugin Name: Funció "Amics del Celler"
// Plugin URI: https://cellercornudella.cat
// Description: Aquest plugin afegeix una casella de verificació al formulari de registre i al de checkout perquè els usuaris puguin seleccionar si volen ser afegits al rol "amicsceller". A més, assigna un nombre incremental als usuaris que pertanyen a aquest rol.
// Version: 1.0
// Author: Noubit Informàtica i Web
// Author URI: https://www.noubit.cat

// Registra el plugin
function register_my_plugin() {

    // Registra los hooks para añadir la casilla de verificación al formulario de registro y al de checkout
    add_action('woocommerce_register_form', 'add_my_checkbox_to_register_form');
    add_action('woocommerce_checkout_before_order_review', 'add_my_checkbox_to_checkout_form');

    // Registra el hook para asignar un número incremental a los usuarios que pertenecen al rol "amicsceller"
    add_action('user_register', 'assign_my_incremented_number_to_user');
}

add_action('plugins_loaded', 'register_my_plugin');

// Añade la casilla de verificación al formulario de registro
function add_my_checkbox_to_register_form() {
    echo '<input type="checkbox" name="my_checkbox" value="1"> Vull formar part del club <b>Amics del Celler</b> i beneficiar-me dels seus avantatges';
	
	 // Añade la descripción
    echo '<p style="border: 1px solid #666; background: #efefef; padding: 15px;" class="description" for="my_checkbox">Fes-te <b>Amic del Celler</b> i obtindràs descomptes especials i podràs participar en activitats exclusives. <a href="https://cellercornudella.cat/estrenem-el-club-damics-del-celler/"><b>Consulta les condicions</b></a></p>';
}

// Añade la casilla de verificación al formulario de checkout
function add_my_checkbox_to_checkout_form() {
    echo '<input type="checkbox" name="my_checkbox" value="1"> Vull formar part del club <b>Amics del Celler</b> i beneficiar-me dels seus avantatges';
	
		 // Añade la descripción
    echo '<p style="border: 1px solid #666; background: #efefef; padding: 15px;" class="description" for="my_checkbox">Fes-te <b>Amic del Celler</b> i obtindràs descomptes especials i podràs participar en activitats exclusives. <a href="https://cellercornudella.cat/estrenem-el-club-damics-del-celler/"><b>Consulta les condicions</b></a></p>';
}

// Asigna un número incremental a los usuarios que pertenecen al rol "amicsceller"
function assign_my_incremented_number_to_user($user_id) {
    // Obtén el número de socios actual
    $number_of_members = get_option('number_of_members', 0);

    // Actualiza el número de socios
    update_option('number_of_members', $number_of_members + 1);

    // Asigna el número de socios al usuario
    update_user_meta($user_id, 'my_incremented_number', $number_of_members + 1);

    // Añade el usuario al rol "amicsceller" si ha marcado la casilla
    if (isset($_POST['my_checkbox']) && $_POST['my_checkbox'] == '1') {
        // Añade el usuario al rol "amicsceller"
        $user = get_user_by('id', $user_id);
        $user->set_role('amicsceller');

        // Elimina el rol "customer" del usuario
        $user->remove_role('customer');
    }
}

// Obtiene el número de socios
function get_number_of_members() {
    return get_option('number_of_members', 0);
}

// Obtiene el número de socio de un usuario
function get_user_number($user_id) {
    return get_user_meta($user_id, 'my_incremented_number', true);
}
add_action('show_user_profile', 'my_show_user_meta');
add_action('edit_user_profile', 'my_show_user_meta');

function my_show_user_meta($user) {
    // Obtén el metadato
    $meta_value = get_user_meta($user->ID, 'my_incremented_number', true);

    // Muestra el metadato
    echo '<h2 style="color:red; font-size:20px;">Amic del Celler nº: ' . $meta_value . '</h2>';
}
add_action('admin_menu', 'my_admin_menu');

function my_admin_menu() {
    add_menu_page(
        'Amics del Celler',
        'Amics del Celler',
        'manage_options',
        'amicsceller',
        'my_admin_page',
        'dashicons-groups',
        50
    );
}

function my_admin_page() {
    // Obtén la lista de usuarios
    $users = get_users(array('role' => 'amicsceller'));
    // Añade el título de la página
    echo '<h1>Gestió Amics del Celler</h1>';
    // Muestra la lista de usuarios
    echo '<table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Correu electrònic</th>
                <th>Número de soci</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($users as $user) {
        echo '<tr>
            <td>' . $user->ID . '</td>
            <td>' . $user->first_name . ' ' . $user->last_name . '</td>
            <td>' . $user->user_email . '</td>
            <td>' . get_user_number($user->ID) . '</td>
			<td>
                <a href="' . admin_url('admin-ajax.php?action=my_delete_user&user_id=' . $user->ID) . '" class="button button-danger">Eliminar</a>
            </td>
        </tr>';
    }

    echo '</tbody>
    </table>';
	// Añade los créditos
    echo '<p style="text-align: right;">Desenvolupat per Noubit <a href="https://noubit.cat">(noubit.cat)</a></p>';

}

add_action('wp_ajax_my_delete_user', 'my_delete_user');

function my_delete_user() {
    // Obtén el ID del usuario
    $user_id = $_POST['user_id'];

    // Elimina el usuario
    wp_delete_user($user_id);

    // Redirecciona al usuario a la página de administración
    wp_redirect(admin_url('admin.php?page=amicsceller'));
}

function my_apply_discount_to_amicsceller_users( $price, $product ) {

    // Comprueba si el usuario pertenece al rol "amicsceller"
    $user = wp_get_current_user();
    if ( $user->has_role( 'amicsceller' ) ) {

        // Comprueba si el producto pertenece a la categoría "botiga_online"
        if ( $product->get_category_ids() == array( 'botiga_online' ) ) {

            // Obtén el precio del producto
            $price = woocommerce_get_price( $product );

            // Aplica el descuento
            $price = $price * 0.9;
        }
    }

    return $price;
}

add_filter( 'woocommerce_get_price', 'my_apply_discount_to_amicsceller_users', 10, 2 );


