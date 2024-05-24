<?php
/**
 * Plugin Name: AMB-DidO Plugin 
 * Description: Erstellt Metadaten gemäß AMB-Standard im JSON-Format für didaktische und Organisationsressourcen
 * Version: 0.1
 * Author: Justus Henke 
 */




/** 
 *  Features und Funktionen laden 
 * */
include_once(plugin_dir_path( __FILE__ ) . 'metabox.php');  // Weitere Metaboxen
include_once(plugin_dir_path( __FILE__ ) . 'frontend.php'); // Frontend-Darstellung
include_once(plugin_dir_path( __FILE__ ) . 'search.php');   // Suchfunktionen
require_once(plugin_dir_path( __FILE__ ) . 'options.php');  // Plugin-Einstellungen


/**
 * Abhängige Dateien laden
 */

function amb_dido_enqueue_styles() {
    wp_enqueue_style('amb-dido-styles', plugin_dir_url(__FILE__) . 'styles.css');
}
function amb_dido_enqueue_frontend_styles() {
    wp_register_style('amb_dido_styles_frontend', plugins_url('styles-frontend.css', __FILE__));
    wp_enqueue_style('amb_dido_styles_frontend');
}


function amb_enqueue_scripts() {
    wp_enqueue_script('amb-keywords-js', plugin_dir_url(__FILE__) . 'scripts.js', [], null, true);
}

function amb_enqueue_admin_scripts() {
    wp_enqueue_script('post'); // This script is needed for tag input and other post editing features.
}
add_action('admin_enqueue_scripts', 'amb_enqueue_admin_scripts');

/**
 * Funktionen aufrufen
 */

add_action('admin_enqueue_scripts', 'amb_enqueue_scripts');     // JS laden
add_action('admin_enqueue_scripts', 'amb_dido_enqueue_styles'); // CSS Styles
add_action('wp_enqueue_scripts', 'amb_dido_enqueue_frontend_styles');    // CSS Styles für das Frontend
add_action('admin_menu', 'amb_dido_create_settings_page');      // Optionsseite erstellen
add_action('admin_init', 'amb_dido_register_settings');         // Optionen registrieren

add_action('add_meta_boxes', 'amb_dido_add_custom_box');    // Felder erzeugen
add_action('save_post', 'amb_dido_save_post_meta');         // Werte speichern
add_action('wp_head', 'amb_dido_add_json_ld_to_header');    // JSON schreiben





// Logfile erstellen
function amb_dido_log($message) {
    if (is_array($message) || is_object($message)) {
        $message = print_r($message, true);
    }
    $log_file = plugin_dir_path(__FILE__) . 'amb_dido.log';
    file_put_contents($log_file, current_time('mysql') . " " . $message . "\n", FILE_APPEND);
}




/**
 *  AMB Metabox
 * --------------
 */

/**
 * Registriert die Metabox für die AMB-Attribute.
 */
function amb_dido_add_custom_box() {
    $selected_post_types = get_option('amb_dido_post_types', []);
    foreach ($selected_post_types as $post_type) {
        add_meta_box(
            'amb_dido_meta_box',
            __('AMB Metadaten', 'amb_dido'),
            'amb_dido_meta_box_callback',
            $post_type,
            'normal',
            'high'
        );
    }
}


// Hartkodierte Wertelisten
function amb_get_other_fields() {
    return [
        
        'amb_inLanguage' => [
            'field_label' => 'Sprache des Inhalts',
            'options' => [
                ['de' => 'Deutsch'],
                ['en' => 'English'],
                ['fr' => 'Français']
            ]
        ],
        'amb_isAccessibleForFree' => [
            'field_label' => 'Kostenfreier Zugang',
            'options' => [
                ['true' => 'Ja'],
                ['false' => 'Nein']
            ]
        ],
        'amb_license' => [
            'field_label' => 'Lizenz des Inhalts',
            'options' => [
                ['http://creativecommons.org/licenses/by/4.0/legalcode.de' => 'CC BY 4.0 Namensnennung'],
                ['http://creativecommons.org/licenses/by-sa/4.0/legalcode.de' => 'CC BY-SA 4.0 Gleiche Bedingungen '],
                ['https://creativecommons.org/licenses/by-nc/4.0/legalcode.de' => 'CC BY-NC 4.0 Nichtkommmerziell'],
                ['https://www.gnu.org/licenses/gpl-3.0.xml' => 'GNU GPL 3 Software']
            ]
        ],
        'amb_conditionsOfAccess' => [
            'field_label' => 'Zugangsbedingungen',
            'options' => [
                ['http://w3id.org/kim/conditionsOfAccess/no_login' => 'Keine Anmeldung erforderlich'],
                ['http://w3id.org/kim/conditionsOfAccess/login' => 'Anmeldung erforderlich']
            ]
        ]
        /* wird bereits aus Archiv abgerufen
        ,
        'amb_educationalLevel' => [
            'field_label' => 'Stufe im Bildungssystem',
            'options' => [
                ['https://w3id.org/kim/educationalLevel/level_A' => 'Hochschule'],
                ['https://w3id.org/kim/educationalLevel/level_B' => 'Vorbereitungsdienst'],
                ['https://w3id.org/kim/educationalLevel/level_C' => 'Fortbildung']
            ]
        ]
        */
    ];
}


/**
 * Wertelisten aus Archiven auslesen
 */


// Globale Konfiguration der URLs für verschiedene JSON-Daten
function amb_get_json_urls() {
    return [
        'amb_area' => 'https://hof-halle-wittenberg.github.io/vocabs/area/index.json',
        'amb_type' => 'https://hof-halle-wittenberg.github.io/vocabs/type/index.json',
        'amb_organisationalContext' => 'https://hof-halle-wittenberg.github.io/vocabs/organisationalContext/index.json',
        'amb_didacticUseCase' => 'https://hof-halle-wittenberg.github.io/vocabs/didacticUseCase/index.json',
        'amb_learningResourceType' => 'https://skohub.io/dini-ag-kim/hcrt/heads/master/w3id.org/kim/hcrt/scheme.json',
        //'amb_audience' => 'https://vocabs.edu-sharing.net/w3id.org/edu-sharing/vocabs/dublin/educationalAudienceRole/index.json',
        'amb_audience' => 'https://hof-halle-wittenberg.github.io/vocabs/audience/index.json',
        'amb_hochschulfaechersystematik' => 'https://skohub.io/dini-ag-kim/hochschulfaechersystematik/heads/master/w3id.org/kim/hochschulfaechersystematik/scheme.json'
        

    ];
}


// veraltet: Alle Wertelisten abrufen, nur für erste Ebene
function amb_get_all_external_values_broader() {
    $urls = amb_get_json_urls();
    $all_values = [];

    foreach ($urls as $key => $url) {
        $values = amb_get_external_values($key);
        if (!empty($values)) {
            $all_values[$key] = $values;
        }
    }

    return $all_values;
}


// Alle Wertelisten abrufen, incl. aller Ebenen 
function amb_get_all_external_values() {
    $urls = amb_get_json_urls();
    $all_values = [];

    foreach ($urls as $key => $url) {
        $values = amb_fetch_external_values($url);
        if (!empty($values)) {
            $all_values[$key] = $values;
        }
    }

    return $all_values;
}

function amb_fetch_external_values($url) {
    $response = wp_remote_get($url);
    if (is_wp_error($response)) {
        return [];
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    $field_label = $data['title']['de'] ?? 'Standard-Titel';
    $concepts = $data['hasTopConcept'] ?? [];
    $options = parse_concepts($concepts);

    return [
        'field_label' => $field_label,
        'options' => $options
    ];
}

function parse_concepts($concepts) {
    $options = [];
    foreach ($concepts as $concept) {
        if (isset($concept['id']) && isset($concept['prefLabel']['de'])) {
            $entry = [
                $concept['id'] => $concept['prefLabel']['de']
            ];
            if (isset($concept['narrower'])) {
                $entry['narrower'] = parse_concepts($concept['narrower']);
            }
            $options[] = $entry;
        }
    }
    return $options;
}


// Externe Wertelisten, verallgemeinert für erste Ebene
function amb_get_external_values($key, $field_label = null) {
    $urls = amb_get_json_urls();

    if (!isset($urls[$key])) {
        return []; // Keine Daten, wenn keine URL gefunden wird
    }

    $response = wp_remote_get($urls[$key]);
    if (is_wp_error($response)) {
        return [];
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // Setzen des Feldetiketts, falls nicht explizit übergeben
    if (is_null($field_label)) {
        $field_label = $data['title']['de'] ?? 'Standard-Titel';
    }

    $concepts = $data['hasTopConcept'] ?? [];
    $options = [];

    foreach ($concepts as $concept) {
        if (isset($concept['id']) && isset($concept['prefLabel']['de'])) {
            $options[] = [$concept['id'] => $concept['prefLabel']['de']];
        }
    }

    return [
        'field_label' => $field_label,
        'options' => $options
    ];
}


// Zugriff auf die Defaults und die entsprechenden Labels aus der Optionen-Konfiguration
function amb_dido_display_defaults($field, $options) {
    $defaults = get_option('amb_dido_defaults');

    // Mapping von field auf field_label
    //$all_fields = amb_get_other_fields();
    $all_fields = array_merge(amb_get_other_fields(), amb_get_all_external_values());
    $field_label = isset($all_fields[$field]['field_label']) ? $all_fields[$field]['field_label'] : $field;

    // Ausgabe der Default-Werte oder "Keine Auswahl"
    if (isset($defaults[$field])) {
        if ($defaults[$field] === 'deactivate') {
          echo "<p>{$field_label}: <strong>Feld ausgeblendet</strong></p>";
        } else {
          $default_value = $defaults[$field];
          $label = 'Unbekannte Auswahl';

          // Überprüfe die Optionen und finde das passende Label
          if (isset($all_fields[$field]['options'])) {
            foreach ($all_fields[$field]['options'] as $option) {
              if (isset($option[$default_value])) {
                $label = $option[$default_value];
                break;
              }
            }
          }

          echo "<p>{$field_label}: <strong>" . esc_html($label) . "</strong></p>";
        }
      } else {
        echo "<p>{$field_label}: <strong>Keine Auswahl getroffen</strong></p>";
      }
}

/**
 * Generiert eine Checkbox-Gruppe mit SVG-Icons basierend auf den gegebenen Parametern.
 *
 * @param string $title Der Titel für die Gruppe.
 * @param string $name Der Name des Feldes im Formular.
 * @param array $options Die Optionen als Array von IDs zu Labels.
 * @param array $stored_values Die gespeicherten Werte für die Checkboxen.
 */

// Zeigt erste und zweite Ebene eines Vokabulars an
function generate_checkbox_group_any($name, $options, $stored_values, $title = null) {
    // Falls kein Titel übergeben wurde, versuchen, den Titel aus den Optionen zu extrahieren
    if ($title === null && isset($options['field_label'])) {
        $title = $options['field_label'];
    }
    
    $svg_check = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" role="presentation" class="components-checkbox-control__checked" aria-hidden="true" focusable="false"><path d="M16.7 7.1l-6.3 8.5-3.3-2.5-.9 1.2 4.5 3.4L17.9 8z"></path></svg>';

    echo '<label class="amb-field">' . esc_html($title) . '</label><br />';
    echo '<div class="grid-container">';
    
    foreach ($options['options'] as $option) {
        foreach ($option as $id => $label) {
            if (!isset($option['narrower'])) {
                // Hauptoption ohne "narrower"-Unteroptionen oder falsche Struktur
                $checked = in_array($id, $stored_values) ? 'checked' : '';
                echo '<div class="grid-item components-base-control__field tbroad">';
                echo '<span class="components-checkbox-control__input-container amb-control">';
                echo '<input type="checkbox" name="' . esc_attr($name) . '[]" value="' . esc_attr($id) . '" ' . $checked . ' id="type_' . esc_attr($id) . '" class="components-checkbox-control__input" onchange="toggleSVG(this)">';
                if ($checked) {
                    echo $svg_check;
                }
                echo '</span>';
                echo '<label for="type_' . esc_attr($id) . '" class="label amb-control-label">' . esc_html($label) . '</label>';
                echo '</div>';
            } elseif (isset($option['narrower']) && !is_array($label)) {
                // Option mit "narrower"-Unteroptionen
                $checked = in_array($id, $stored_values) ? 'checked' : '';
                $collapse_class = $checked ? '' : 'collapsed';
                $expanded = $checked ? 'true' : 'false';

                echo '<div class="grid-item components-base-control__field tnarrow">';
                echo '<span class="components-checkbox-control__input-container amb-control">';
                echo '<input type="checkbox" name="' . esc_attr($name) . '[]" value="' . esc_attr($id) . '" ' . $checked . ' id="type_' . esc_attr($id) . '" class="components-checkbox-control__input" onchange="toggleSVG(this)">';
                if ($checked) {
                    echo $svg_check;
                }
                echo '</span>';
                echo '<label for="type_' . esc_attr($id) . '" class="label amb-control-label">' . esc_html($label) . '</label>';
                
                // Erste Ebene von "narrower"-Unteroptionen anzeigen
                echo '<button type="button" onclick="toggleNarrower(this);" class="amb-narrower ' . $collapse_class . '" aria-expanded="' . $expanded . '"></button>';
                echo '<div class="narrower-container grid-container" style="display: ' . ($checked ? 'block' : 'none') . ';">';

                foreach ($option['narrower'] as $narrower_option) {
                    if (is_array($narrower_option)) {
                        foreach ($narrower_option as $narrower_id => $narrower_label) {
                            if (!is_array($narrower_label)) {
                                $checked = in_array($narrower_id, $stored_values) ? 'checked' : '';
                                echo '<div class="grid-item components-base-control__field tsub">';
                                echo '<span class="components-checkbox-control__input-container amb-control">';
                                echo '<input type="checkbox" name="' . esc_attr($name) . '[]" value="' . esc_attr($narrower_id) . '" ' . $checked . ' id="type_' . esc_attr($narrower_id) . '" class="components-checkbox-control__input" onchange="toggleSVG(this)">';
                                if ($checked) {
                                    echo $svg_check;
                                }
                                echo '</span>';
                                echo '<label for="type_' . esc_attr($narrower_id) . '" class="label amb-control-label">' . esc_html($narrower_label) . '</label>';
                                echo '</div>';
                            }
                        }
                    }
                }
                echo '</div>'; // Ende der narrower-container
                echo '</div>'; // Ende der grid-item tnarrow
            }
        }
    }
    
    echo '</div>';
}


// veraltet: Zeigt nur erste Ebene an
function generate_checkbox_group_any_broader($name, $options, $stored_values, $title = null) {
    // Falls kein Titel übergeben wurde, versuchen, den Titel aus den Optionen zu extrahieren
    if ($title === null && isset($options['field_label'])) {
        $title = $options['field_label'];
    }
    var_dump($options);
    echo '<label class="amb-field">' . esc_html($title) . '</label><br />';
    echo '<div class="grid-container">';
    
    foreach ($options['options'] as $option) {
        foreach ($option as $id => $label) {
            //$checked = in_array($id, array_column($stored_values, 'id')) ? 'checked' : '';
            $checked = in_array($id, $stored_values) ? 'checked' : '';
            echo '<div class="grid-item components-base-control__field">';
            echo '<span class="components-checkbox-control__input-container amb-control">';
            echo '<input type="checkbox" name="' . esc_attr($name) . '[]" value="' . esc_attr($id) . '" ' . $checked . ' id="type_' . esc_attr($id) . '" class="components-checkbox-control__input" onchange="toggleSVG(this)">';
            if ($checked) {
                echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" role="presentation" class="components-checkbox-control__checked" aria-hidden="true" focusable="false"><path d="M16.7 7.1l-6.3 8.5-3.3-2.5-.9 1.2 4.5 3.4L17.9 8z"></path></svg>';
            }
            echo '</span>';
            echo '<label for="type_' . esc_attr($id) . '" class="label amb-control-label">' . esc_html($label) . '</label>';
            echo '</div>';
        }
    }

    echo '</div>';
}


// veraltet: Zeigt nur erste Ebene an
function generate_checkbox_group($title, $name, $options, $stored_values) {
    echo '<label class="amb-field">' . esc_html($title) . '</label><br />';
    echo '<div class="grid-container">';
    
    foreach ($options as $id => $label) {
        $checked = in_array($id, array_column($stored_values, 'id')) ? 'checked' : '';
        echo '<div class="grid-item components-base-control__field">';
        echo '<span class="components-checkbox-control__input-container amb-control">';
        echo '<input type="checkbox" name="' . esc_attr($name) . '[]" value="' . esc_attr($id) . '" ' . $checked . ' id="type_' . esc_attr($id) . '" class="components-checkbox-control__input" onchange="toggleSVG(this)">';
        if ($checked) {
            echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" role="presentation" class="components-checkbox-control__checked" aria-hidden="true" focusable="false"><path d="M16.7 7.1l-6.3 8.5-3.3-2.5-.9 1.2 4.5 3.4L17.9 8z"></path></svg>';
        }
        echo '</span>';
        echo '<label for="type_' . esc_attr($id) . '" class="label amb-control-label">' . esc_html($label) . '</label>';
        echo '</div>';
    }

    echo '</div>';
}


/* Hilfsfunktion um ids aus Arrays zu extrahieren */ 
function get_selected_ids($meta_field) {
    $stored_values = get_post_meta(get_the_ID(), $meta_field, true);
    $stored_values = is_array($stored_values) ? $stored_values : [];
    $ids = [];

    foreach ($stored_values as $value) {
        if (isset($value['id'])) {
            $ids[] = $value['id'];
        }
    }

    return $ids;
}


/**
 * Generiert das HTML für die AMB Metabox.
 */
function amb_dido_meta_box_callback($post) {
    // Sicherheit: Einfügen eines Nonce-Feldes für Verifizierung
    wp_nonce_field('amb_dido_save_meta_box_data', 'amb_dido_meta_box_nonce');

    // Generierung Description
    $description = get_post_meta($post->ID, 'amb_description', true);
    echo '<label for="amb_description" class="amb-field">Beschreibung des Inhalts</label><br />';
    echo '<p class="components-form-token-field__help">In zwei bis drei Sätzen den Inhalt beschreiben.</p>';
    echo '<textarea name="amb_description" class="components-textarea-control__input amb-textarea" rows="4" cols="50">' . esc_textarea($description) . '</textarea><br />';
    

    // Generierung Autoren
    $creator = get_post_meta($post->ID, 'amb_creator', true);
    echo '<label for="amb_keywords" class="amb-field">Autor/innen</label><br />';
    echo '<p class="components-form-token-field__help">Namen mit Kommas trennen.</p>';
    echo '<input type="text" name="amb_creator" size="80" value="' . esc_attr($creator) . '" class="amb-textinput" /><br />';


    // Generierung der Checkbox-Felder
    $defaults = get_option('amb_dido_defaults');
    $checkbox_options = array_merge(amb_get_other_fields(), amb_get_all_external_values());
    // Zieht die Feldstruktur korrekt mit allen verfügbaren Ebenen

    foreach ($checkbox_options as $field => $data) {
        if (isset($defaults[$field]) && !empty($defaults[$field])) {
            amb_dido_display_defaults($field, $data);
        } 
        /* elseif (isset($defaults[$field]) && $defaults[$field] == 'deactivate') {
            // do nothing 
        }*/ 
        else {
            $stored_ids = get_selected_ids($field);  
            generate_checkbox_group_any($field, $data, $stored_ids);
        }
    }
    
}

/**
 * Speichert die Post-Metadaten.
 */

function amb_dido_save_post_meta($post_id) {
    // Überprüfen der Berechtigungen
    if (!isset($_POST['amb_dido_meta_box_nonce']) || !wp_verify_nonce($_POST['amb_dido_meta_box_nonce'], 'amb_dido_save_meta_box_data')) {
        return;
    }

    // Überprüfen, ob dies ein Autosave ist. Falls ja, abbrechen.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Überprüfen, ob der Benutzer die Berechtigung zum Bearbeiten des Beitrags hat.
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Speicherung der offenen Felder
    $open_fields = [
        'amb_description',
        'amb_creator'
    ];

    foreach ($open_fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        } else {
            delete_post_meta($post_id, $field);
        }
    }

    // Alle Wertelisten speichern
    save_all_checkbox_data($post_id);
}

function save_all_checkbox_data($post_id) {
    // Alle verfügbaren externen Werte abrufen
    $all_options = amb_get_all_external_values();

    if (empty($all_options)) {
        return;
    }

    // Rekursive Funktion zur Suche und Hinzufügung von Labels
    function find_label_and_add($type_id, $options, &$to_save) {
        foreach ($options as $option) {
            foreach ($option as $id => $label) {
                if ($id == $type_id && !is_array($label)) {
                    $to_save[] = [
                        'id' => $type_id,
                        'prefLabel' => ['de' => $label],
                        'type' => 'Concept'
                    ];
                    return true;
                } elseif (isset($option['narrower']) && is_array($option['narrower'])) {
                    if (find_label_and_add($type_id, $option['narrower'], $to_save)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    // Durch alle Felder iterieren und die Werte speichern
    foreach ($all_options as $field_key => $field_data) {
        if (isset($_POST[$field_key])) {
            $selected_options = $_POST[$field_key];
            $to_save = [];

            foreach ($selected_options as $type_id) {
                find_label_and_add($type_id, $field_data['options'], $to_save);
            }

            // Prüfen, ob `to_save` nicht leer ist, bevor es gespeichert wird
            if (!empty($to_save)) {
                update_post_meta($post_id, $field_key, $to_save);
            } else {
                delete_post_meta($post_id, $field_key);
            }
        } else {
            delete_post_meta($post_id, $field_key);
        }
    }
}





// creator-Objekte vorbereiten
function generate_creator_objects($creators) {
    $creator_objects = [];
    foreach ($creators as $creator) {
        $creator_objects[] = [
            'type' => 'Person',
            'id' => $creator,
        ];
    }
    return $creator_objects;
}

/**
 * Bindet Custom-Fields in das JSON-LD-Format ein.
 */
function amb_dido_add_json_ld_to_header() {
    if (is_singular(get_option('amb_dido_post_types', []))) {
        global $post;
        
        // Voreinstellungen aus Optionseite rufen                
        $defaults = get_option('amb_dido_defaults');

        // Hartkodierte Felder rufen
        $fields = amb_get_other_fields();

        // Keywords auslesen
        $terms = get_the_terms($post->ID, 'ambkeywords');
        $keywords = [];

        if ($terms && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                $keywords[] = $term->name;
            }
        }

        // creator Werte holen 
        $creators = explode(',', get_post_meta($post->ID, 'amb_creator', true));

        // JSON Elemente zusammenstellen
        $amb_data_core = [
            'description' => get_post_meta($post->ID, 'amb_description', true),
            'creator' => generate_creator_objects($creators),
            'keywords' => !empty($keywords) ? $keywords : '',
            'inLanguage' => get_post_meta($post->ID, 'amb_inLanguage', true) ?: $defaults['amb_inLanguage'] ?: ['de'],
            'publisher' => get_bloginfo('name'),
            'isAccessibleForFree' => get_post_meta($post->ID, 'amb_isAccessibleForFree', true) ?: $defaults['amb_isAccessibleForFree'] ?: 'true',
            'license' => get_post_meta($post->ID, 'amb_license', true) ?: $defaults['amb_license'],
            'conditionsOfAccess' => get_post_meta($post->ID, 'amb_conditionsOfAccess', true) ?: $defaults['amb_conditionsOfAccess']
        ];
        $amb_data = [
            'type' => get_post_meta($post->ID, 'amb_type', true) ?: $defaults['amb_type'] ?: ['LearningResource'],
            'area' => get_post_meta($post->ID, 'amb_area', true) ?: $defaults['amb_area'],
            'learningResourceType' => get_post_meta($post->ID, 'amb_learningResourceType', true) ?: $defaults['amb_learningResourceType'],
            'audience' => get_post_meta($post->ID, 'amb_audience', true) ?: $defaults['amb_audience'],
            'educationalLevel' => get_post_meta($post->ID, 'amb_educationalLevel', true) ?: $defaults['amb_educationalLevel'],
            'aboutSubject' => get_post_meta($post->ID, 'amb_hochschulfaechersystematik', true) ?: $defaults['amb_hochschulfaechersystematik'],
            'aboutContext' => get_post_meta($post->ID, 'amb_organisationalContext', true) ?: $defaults['amb_organisationalContext'],
            'aboutUseCase' => get_post_meta($post->ID, 'amb_didacticUseCase', true) ?: $defaults['amb_didacticUseCase']
        ];

        // Kombinieren der Felder "aboutSubject" und "aboutContext" und "aboutUseCase"
        $about = [];

        function createConceptArray($concepts) {
            $result = [];
            foreach ($concepts as $concept) {
                $result[] = [
                    "id" => $concept['id'],
                    "prefLabel" => ["de" => $concept['prefLabel']['de']],
                    "type" => "concept"
                ];
            }
            return $result;
        }

        if (!empty($amb_data['aboutSubject'])) {
            $about[] = createConceptArray($amb_data['aboutSubject']);
        }
        if (!empty($amb_data['aboutContext'])) {
            $about[] = createConceptArray($amb_data['aboutContext']);
        }
        if (!empty($amb_data['aboutUseCase'])) {
            $about[] = createConceptArray($amb_data['aboutUseCase']);
        }



        // JSON-LD-Struktur vorbereiten
        $json_ld_data = [
            "@context" => ["https://w3id.org/kim/amb/context.jsonld", "https://schema.org", ["@language" => "de"]],
            "id" => get_permalink($post->ID),
            "dateCreated" => get_the_date('c', $post),
            "datePublished" => get_the_date('c', $post),
            "dateModified" => get_the_modified_date('c', $post),
            "publisher" => [["type" => "Organization", "name" => $amb_data['publisher']]],
            "creator" => $amb_data_core['creator'],
            "name" => get_the_title($post),
            "description" => $amb_data_core['description'],
            "keywords" => $amb_data_core['keywords'],
            "image" => get_the_post_thumbnail_url($post, 'full'),
            "inLanguage" => $amb_data_core['inLanguage'],
            "license" => $amb_data_core['license'],
            "isAccessibleForFree" => filter_var($amb_data_core['isAccessibleForFree'], FILTER_VALIDATE_BOOLEAN),
            "conditionsOfAccess" => ["id" => $amb_data_core['conditionsOfAccess'], "type" => "Concept"],
            "area" => $amb_data['area'],    
            "type" => $amb_data['type'],   
            "learningResourceType" => $amb_data['learningResourceType'], 
            "audience" => $amb_data['audience'],
            "educationalLevel" => $amb_data['educationalLevel'],
            "about" => $about
            //"aboutSubject" => $amb_data['aboutSubject'],
            //"aboutContext" => $amb_data['aboutContext'],
            
        ];

        // JSON-LD-Ausgabe im Header
        echo '<script type="application/ld+json">' . json_encode($json_ld_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }
}


