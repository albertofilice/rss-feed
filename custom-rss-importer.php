<?php
/*
Plugin Name: RSS Import
Description: Import RSS and filter by keyword.
Version: 1.0
Author: Alberto Filice
*/

include_once(plugin_dir_path(__FILE__) . 'custom-rss-importer-functions.php');

function custom_rss_importer_menu() {
    add_menu_page(
        'Impostazioni RSS Import', // Titolo della pagina
        'RSS Import', // Nome nel menu
        'manage_options', // Capability richiesta per visualizzare la pagina
        'custom-rss-import-settings', // Slug della pagina
        'custom_rss_importer_settings_page', // Funzione che genera il contenuto della pagina
        'dashicons-rss', // Icona del menu (puoi sostituirla con un'icona a tua scelta)
        80 // Posizione nel menu
    );
}

add_action('admin_menu', 'custom_rss_importer_menu');

function custom_rss_importer_settings_page() {
    // Recupera l'intervallo di pianificazione corrente dalle opzioni
    $current_schedule = get_option('custom_rss_import_schedule');
	$selected_category = get_option('custom_rss_import_category');
	$categories = get_categories();
    if (isset($_POST['run_import'])) {
        // Esegui l'importazione qui
        custom_rss_import_function(get_option('custom_rss_import_sites'),get_option('custom_rss_import_keywords')); // Sostituisci con la tua funzione di importazione RSS
        echo '<div class="updated"><p>Importazione RSS eseguita con successo.</p></div>';
    }    
    ?>
    <div class="wrap">
        <h2>Impostazioni RSS Import</h2>
        <form method="post" action="options.php">
            <?php settings_fields('custom_rss_importer_settings_group'); ?>
            <?php do_settings_sections('custom-rss-import-settings'); ?>
            <?php 
		    echo '<br><br><label style="margin-right: 20px;" for="rss_category">Categoria del post:</label>';	
    		echo '<select id="rss_category" name="custom_rss_import_category">'; // Assicurati che il nome sia coerente	
			foreach ($categories as $category) {
				$selected = ($selected_category == $category->term_id) ? 'selected="selected"' : '';
				echo '<option value="' . $category->term_id . '" ' . $selected . '>' . $category->name . '</option>';
			}

			echo '</select><br><br>';	
			?>
            <label style="margin-right: 20px;" for="rss_schedule">Frequenza di importazione:</label>
            <select id="rss_schedule" name="rss_schedule">
                <option value="daily" <?php selected($current_schedule, 'daily'); ?>>Una volta al giorno</option>
                <option value="3days" <?php selected($current_schedule, '3days'); ?>>Ogni 3 giorni</option>
                <option value="weekly" <?php selected($current_schedule, 'weekly'); ?>>Una volta a settimana</option>
                <option value="monthly" <?php selected($current_schedule, 'monthly'); ?>>Una volta al mese</option>
            </select>
            <br><br>
            <input type="submit" class="button-primary" value="Salva impostazioni">
        </form>
        <br><br><br><br>
        <!-- Aggiungi il pulsante per l'importazione manuale -->
        <form method="post">
            <input type="submit" name="run_import" class="button-secondary" value="Esegui importazione">
        </form>        
    </div>
    <?php
}

function custom_rss_import_settings_section_callback() {
    echo '<p>Questa sezione ti consente di configurare le impostazioni per l\'importazione RSS.</p>';
    echo '<p>Puoi specificare i siti RSS da cui importare i post e le parole chiave per filtrare i post importati.</p>';
    echo '<p>Selezionare anche la categoria in cui desideri salvare i post importati.</p>';
}


function custom_rss_import_sites_callback() {
    $sites = get_option('custom_rss_import_sites');
    echo '<input style="width: 100%;" type="text" name="custom_rss_import_sites" value="' . esc_attr($sites) . '">';
}

function custom_rss_import_keywords_callback() {
    $keywords = get_option('custom_rss_import_keywords');
    echo '<input style="width: 100%;" type="text" name="custom_rss_import_keywords" value="' . esc_attr($keywords) . '">';
}

function custom_rss_importer_register_settings() {
    register_setting('custom_rss_importer_settings_group', 'custom_rss_import_sites');
    register_setting('custom_rss_importer_settings_group', 'custom_rss_import_keywords');
	register_setting('custom_rss_importer_settings_group', 'custom_rss_import_category');
    add_settings_section('custom_rss_import_settings_section', 'Configurazione RSS Import', 'custom_rss_import_settings_section_callback', 'custom-rss-import-settings');
    add_settings_field('custom_rss_import_sites', 'Siti RSS', 'custom_rss_import_sites_callback', 'custom-rss-import-settings', 'custom_rss_import_settings_section');
    add_settings_field('custom_rss_import_keywords', 'Parole chiave', 'custom_rss_import_keywords_callback', 'custom-rss-import-settings', 'custom_rss_import_settings_section');
}
add_action('admin_init', 'custom_rss_importer_register_settings');
