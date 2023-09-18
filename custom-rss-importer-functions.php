<?php
// Inizializza l'oggetto SimplePie
require_once(ABSPATH . WPINC . '/class-simplepie.php');

if ( !function_exists('wp_get_current_user') ) {
    include(ABSPATH . "wp-includes/pluggable.php"); 
}

function custom_rss_import_function($sites_array,$keywords_array) {
    // Ciclo attraverso i siti RSS
	$sites_ar = explode(",", $sites_array);
	$keywords_ar = explode(',', $keywords_array);
	$current_user = wp_get_current_user();
	$user=esc_html( $current_user->ID );
    $category = get_option('custom_rss_import_category');
	
    foreach ($sites_ar as $site_url) {
		$feed = new SimplePie();
		$feed->set_cache_location(WP_CONTENT_DIR . '/cache');

        $feed->set_feed_url($site_url);
        $feed->init();
        $feed->handle_content_type();

        // Ciclo attraverso i post nel feed RSS
        foreach ($feed->get_items() as $item) {
            $title = $item->get_title();
            $content = $item->get_content();

            // Verifica se uno qualsiasi delle parole chiave è presente nel titolo o nel contenuto
            $found_keyword = false;
            foreach ($keywords_ar as $keyword) {
                if (stripos($title, $keyword) !== false || stripos($content, $keyword) !== false) {
                    $found_keyword = true;
                    break;
                }
            }

            // Se una parola chiave è stata trovata, salva il post nel database di WordPress
            if ($found_keyword) {
                #$existing_post = get_page_by_title($title, OBJECT, 'post');

				$query = new WP_Query(
					array(
						'post_type'              => 'post',
						'title'                  => $title,
						'posts_per_page'         => 1,
						'no_found_rows'          => true,
						'ignore_sticky_posts'    => true,
					)
				);				

				if ( ! empty( $query->post ) ) {
					$fetched_page = $query->post;
				} else {
					$page_got_by_title = null;
				}				
				
                if (empty($query->post)) {

                    // Il post non esiste, quindi crea il nuovo post

					$post_data = array(
						'post_title' => $title,
						'post_content' => $content,
						'post_status' => 'publish',
						'post_author' => $user, // Imposta l'autore come l'utente corrente
						'post_category' => array($category), // Usa il valore selezionato dalla select

					);

					// Crea il post
					$post_id = wp_insert_post($post_data);

					// Salva l'URL originale del post come meta dato personalizzato (opzionale)
					add_post_meta($post_id, '_original_url', $item->get_permalink(), true);

					// Recupera e salva l'immagine
					$enclosure = $item->get_enclosure();
					if ($enclosure) {
						$image_url = $enclosure->get_link();
						if ($image_url) {
							// Scarica e carica l'immagine come allegato al post
							$image = media_sideload_image($image_url, $post_id, '', 'id');
							if (!is_wp_error($image)) {
								// Imposta l'immagine come immagine in primo piano del post
								set_post_thumbnail($post_id, $image);
							}
						}
					}

					// Recupera e salva il link di riferimento
					$link = $item->get_link();

					if ($link) {
						add_post_meta($post_id, '_rss_reference_link', $link, true);
					}

					// Aggiungi il pulsante "Leggi l'articolo completo" al contenuto del post
					$content .= '<p><a href="' . $link . '" target="_blank" rel="noopener noreferrer">Leggi l\'articolo completo</a></p>';

					// Aggiorna il contenuto del post con il pulsante aggiunto
					wp_update_post(array('ID' => $post_id, 'post_content' => $content));				

                } else {
                    // Il post con lo stesso titolo esiste già, puoi gestire questa situazione in base alle tue esigenze
                    // Ad esempio, puoi aggiornare il post esistente anziché crearne uno nuovo
                }					
				
                // Ripristina le query di WordPress
                wp_reset_postdata();				
            }
        }
    }
}


function custom_rss_import_schedule() {
    // Recupera l'intervallo di pianificazione dalla configurazione
    $schedule = get_option('custom_rss_import_schedule');

    // Verifica l'intervallo di pianificazione e importa i feed RSS quando necessario
    if ($schedule === 'daily') {
        // Esegui l'importazione giornaliera
        custom_rss_import_function(); // Sostituisci con il nome della tua funzione di importazione
    } elseif ($schedule === '3days') {
        // Esegui l'importazione ogni 3 giorni
        custom_rss_import_function(); // Sostituisci con il nome della tua funzione di importazione
    } elseif ($schedule === 'weekly') {
        // Esegui l'importazione settimanale
        custom_rss_import_function(); // Sostituisci con il nome della tua funzione di importazione
    } elseif ($schedule === 'monthly') {
        // Esegui l'importazione mensile
        custom_rss_import_function(); // Sostituisci con il nome della tua funzione di importazione
    }
}

// Registra l'evento pianificato quando le opzioni vengono salvate
function custom_rss_importer_save_settings() {
    if (isset($_POST['rss_schedule'])) {
        $schedule = $_POST['rss_schedule'];

        // Registra l'intervallo di pianificazione nelle opzioni
        update_option('custom_rss_import_schedule', $schedule);

        // Pianifica l'esecuzione della funzione in base all'intervallo selezionato
        wp_clear_scheduled_hook('custom_rss_import_schedule');
        if ($schedule === 'daily') {
            wp_schedule_event(time(), 'daily', 'custom_rss_import_schedule');
        } elseif ($schedule === '3days') {
            wp_schedule_event(time(), '3days', 'custom_rss_import_schedule');
        } elseif ($schedule === 'weekly') {
            wp_schedule_event(time(), 'weekly', 'custom_rss_import_schedule');
        } elseif ($schedule === 'monthly') {
            wp_schedule_event(time(), 'monthly', 'custom_rss_import_schedule');
        }
    }
}
add_action('admin_init', 'custom_rss_importer_save_settings');
