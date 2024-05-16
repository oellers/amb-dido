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
include_once(plugin_dir_path( __FILE__ ) . 'metabox.php');
include_once(plugin_dir_path( __FILE__ ) . 'frontend.php');
require_once(plugin_dir_path( __FILE__ ) . 'options.php');


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
            'field_label' => 'In welcher Sprache ist der Inhalt verfasst?',
            'options' => [
                ['de' => 'Deutsch'],
                ['en' => 'English'],
                ['fr' => 'Français']
            ]
        ],
        'amb_isAccessibleForFree' => [
            'field_label' => 'Ist der Inhalt kostenfrei zugänglich?',
            'options' => [
                ['true' => 'Ja'],
                ['false' => 'Nein']
            ]
        ],
        'amb_license' => [
            'field_label' => 'Unter welcher Lizenz ist der Inhalt veröffentlicht?',
            'options' => [
                ['http://creativecommons.org/licenses/by/4.0/legalcode.de' => 'CC BY 4.0 Namensnennung'],
                ['http://creativecommons.org/licenses/by-sa/4.0/legalcode.de' => 'CC BY-SA 4.0 Gleiche Bedingungen '],
                ['https://creativecommons.org/licenses/by-nc/4.0/legalcode.de' => 'CC BY-NC 4.0 Nichtkommmerziell'],
                ['https://www.gnu.org/licenses/gpl-3.0.xml' => 'GNU GPL 3 Software']
            ]
        ],
        'amb_conditionsOfAccess' => [
            'field_label' => 'Welche Zugangsbedingungen bestehen?',
            'options' => [
                ['http://w3id.org/kim/conditionsOfAccess/no_login' => 'Keine Anmeldung erforderlich'],
                ['http://w3id.org/kim/conditionsOfAccess/login' => 'Anmeldung erforderlich']
            ]
        ],
        'amb_area' => [
            'field_label' => 'Welcher Leistungsbereich der Hochschule ist betroffen?',
            'options' => [
                ['General' => 'bereichsübergreifend'],
                ['Teaching' => 'Lehre/Studium'],
                ['Research' => 'Forschung'],
                ['Transfer' => 'Transfer/Third Mission']
            ]
        ],
        'amb_type' => [
            'field_label' => 'Was ist der grundlegende Inhaltstyp?',
            'options' => [
                ['LearningResource' => 'Lernressource'],
                ['HowToTip' => 'Anleitung'],
                ['HowToDirection' => 'Anweisung/Vorschrift']
            ]
        ],
        'amb_organisationalContext' => [
            'field_label' => 'Zu welchem Organisationskontext passt der Inhalt?',
            'options' => [
                ['didacticSupport' => 'didaktischer Support'],
                ['legalSupport' => 'rechtlicher Support'],
                ['infrastructureSupport' => 'Support Infrastruktur'],
                ['strategicSupport' => 'strategischer Support']
            ]
        ],
        'amb_educationalLevel' => [
            'field_label' => 'Für welche Stufe im Bildungssystem wurde der Inhalt erstellt?',
            'options' => [
                ['https://w3id.org/kim/educationalLevel/level_A' => 'Hochschule'],
                ['https://w3id.org/kim/educationalLevel/level_B' => 'Vorbereitungsdienst'],
                ['https://w3id.org/kim/educationalLevel/level_C' => 'Fortbildung']
            ]
        ]
    ];
}


/**
 * Wertelisten aus Archiven auslesen
 */

/* HCRT LearningResourceType */
function amb_get_learning_resource_types() {
    $response = wp_remote_get('https://skohub.io/dini-ag-kim/hcrt/heads/master/w3id.org/kim/hcrt/scheme.json');
    if (is_wp_error($response)) {
        return [];
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    $types = $data['hasTopConcept'] ?? [];
    $options = [];

    foreach ($types as $type) {
        if (isset($type['id']) && isset($type['prefLabel']['de'])) {
            $options[$type['id']] = $type['prefLabel']['de'];
        }
    }
    asort($options);

    return $options;
}

/* LRMI educationalAudienceRole */
function amb_get_audience_roles() {
    $response = wp_remote_get('https://vocabs.edu-sharing.net/w3id.org/edu-sharing/vocabs/dublin/educationalAudienceRole/index.json');
    if (is_wp_error($response)) {
        return [];
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    $roles = $data['hasTopConcept'] ?? [];
    $options = [];

    foreach ($roles as $role) {
        if (isset($role['id']) && isset($role['prefLabel']['de'])) {
            $options[$role['id']] = $role['prefLabel']['de'];
        }
    }
    asort($options);

    return $options;
}

/* AMB hochschulfaechersystematik */
function amb_get_hochschulfaechersystematik() {
    $response = wp_remote_get('https://skohub.io/dini-ag-kim/hochschulfaechersystematik/heads/master/w3id.org/kim/hochschulfaechersystematik/scheme.json');
    if (is_wp_error($response)) {
        return [];
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    $roles = $data['hasTopConcept'] ?? [];
    $options = [];

    foreach ($roles as $role) {
        if (isset($role['id']) && isset($role['prefLabel']['de'])) {
            $options[$role['id']] = $role['prefLabel']['de'];
        }
    }
    asort($options);
    
    return $options;
}

function amb_get_hochschulfaechersystematik_with_narrower() {
    $response = wp_remote_get('https://skohub.io/dini-ag-kim/hochschulfaechersystematik/heads/master/w3id.org/kim/hochschulfaechersystematik/scheme.json');
    if (is_wp_error($response)) {
        return [];
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    $roles = $data['hasTopConcept'] ?? [];
    $options = [];

    foreach ($roles as $role) {
        if (isset($role['id']) && isset($role['prefLabel']['de'])) {
            $options[$role['id']] = [
                'label' => $role['prefLabel']['de'],
                'narrower' => isset($role['narrower']) ? array_reduce($role['narrower'], function($carry, $item) {
                    if (isset($item['id']) && isset($item['prefLabel']['de'])) {
                        $carry[$item['id']] = [
                            'label' => $item['prefLabel']['de'],
                            'narrower' => isset($item['narrower']) ? array_reduce($item['narrower'], function($carryInner, $itemInner) {
                                if (isset($itemInner['id']) && isset($itemInner['prefLabel']['de'])) {
                                    $carryInner[$itemInner['id']] = $itemInner['prefLabel']['de'];
                                }
                                return $carryInner;
                            }, []) : []
                        ];
                    }
                    return $carry;
                }, []) : []
            ];
        }
    }
    return $options;
}

// Zugriff auf die Defaults und die entsprechenden Labels aus der Optionen-Konfiguration
function amb_dido_display_defaults($field, $options) {
    $defaults = get_option('amb_dido_defaults');

    // Mapping von field auf field_label
    $all_fields = amb_get_other_fields();
    $field_label = isset($all_fields[$field]['field_label']) ? $all_fields[$field]['field_label'] : $field;

    // Ausgabe der Default-Werte oder "Keine Auswahl"
    if (isset($defaults[$field]) && $defaults[$field] !== '') {
        $label = $options[$defaults[$field]] ?? 'Unbekannte Auswahl';
        echo "<p>{$field_label}: <strong>" . esc_html($label) . "</strong></p>";
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

function generate_checkbox_group_with_narrower($title, $name, $options, $stored_values) {
    echo '<label class="amb-field">' . esc_html($title) . '</label><br />';
    echo '<div class="grid-container">';
    
    // Extrahiere IDs aus den gespeicherten Werten
    $stored_ids = extract_ids_recursive($stored_values);
    
    // Generiere die Checkbox-Gruppe rekursiv
    render_checkbox_group_recursive($name, $options, $stored_ids);
    
    echo '</div>';
}

function extract_ids_recursive($stored_values) {
    $ids = array();
    foreach ($stored_values as $value) {
        if (isset($value['id'])) {
            $ids[] = $value['id'];
        }
        if (isset($value['narrower'])) {
            $ids = array_merge($ids, extract_ids_recursive($value['narrower']));
        }
    }
    return $ids;
}

function render_checkbox_group_recursive($name, $options, $stored_ids, $is_narrower = false) {
    foreach ($options as $id => $details) {
        $checked = in_array($id, $stored_ids) ? 'checked' : '';
        $label = isset($details['label']) ? esc_html($details['label']) : null;

        // Skip rendering if label is not defined
        if ($label === null) {
            continue;
        }

        echo '<div class="grid-item components-base-control__field">';
        echo '<span class="components-checkbox-control__input-container amb-control">';
        echo '<input type="checkbox" name="' . esc_attr($name) . '[]" value="' . esc_attr($id) . '" ' . $checked . ' id="type_' . esc_attr($id) . '" class="components-checkbox-control__input" onchange="toggleSVG(this);">';
        if ($checked) {
            echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" role="presentation" class="components-checkbox-control__checked" aria-hidden="true" focusable="false">';
            echo '<path d="M16.7 7.1l-6.3 8.5-3.3-2.5-.9 1.2 4.5 3.4L17.9 8z"></path>';
            echo '</svg>';
        }
        echo '</span>';
        echo '<label for="type_' . esc_attr($id) . '" class="label amb-control-label">' . $label . '</label>';

        if (!empty($details['narrower']) && is_array($details['narrower'])) {
            // Skip empty narrower
            $has_valid_narrower = false;
            foreach ($details['narrower'] as $narrow_id => $narrow_details) {
                if (isset($narrow_details['label'])) {
                    $has_valid_narrower = true;
                    break;
                }
            }
            if ($has_valid_narrower) {
                $expanded = $checked ? 'true' : 'false';
                $collapse_class = $checked ? '' : 'collapsed';
                echo '<button type="button" onclick="toggleNarrower(this);" class="amb-narrower ' . $collapse_class . '" aria-expanded="' . $expanded . '"></button>';
                echo '<div class="narrower-container grid-container" style="display: ' . ($checked ? 'block' : 'none') . ';">';
                render_checkbox_group_recursive($name, $details['narrower'], $stored_ids, true);
                echo '</div>';
            }
        }
        
        echo '</div>';
    }
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
    $checkbox_options = amb_get_other_fields();

    foreach ($checkbox_options as $field => $data) {
        // Prüfe, ob ein Default-Wert gesetzt und nicht leer ist
        if (isset($defaults[$field]) && !empty($defaults[$field])) {
            $option_map = [];
            foreach ($data['options'] as $option_array) {
                foreach ($option_array as $id => $label) {
                    $option_map[$id] = $label; // Bereite eine einfache Zuordnung von ID zu Label vor
                }
            }
            amb_dido_display_defaults($field, $option_map);
        } else {

        $stored_ids = get_selected_ids($field);  

            echo '<label class="amb-field">' . esc_html($data['field_label']) . '</label><br />';
            echo '<div class="grid-container">';
            foreach ($data['options'] as $option) {
                foreach ($option as $id => $label) {
                    $checked = in_array($id, $stored_ids) ? 'checked' : '';  // Überprüfe, ob die ID im Array der gespeicherten IDs ist
                    echo '<div class="grid-item components-base-control__field">';
                    echo '<span class="components-checkbox-control__input-container amb-control"><input type="checkbox" name="' . esc_attr($field) . '[]" value="' . esc_attr($id) . '" ' . $checked . ' class="components-checkbox-control__input" onchange="toggleSVG(this)">';
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
    }

    


    // Learning Resource Type Checkbox-Gruppe
    $learning_resource_types = amb_get_learning_resource_types();
    $stored_learning_resource_types = get_post_meta($post->ID, 'amb_learningResourceType', true) ?: [];
    generate_checkbox_group('Welche Form hat der Inhalt?', 'amb_learningResourceType', $learning_resource_types, $stored_learning_resource_types);

    // Audience Role Checkbox-Gruppe
    $audience_types = amb_get_audience_roles();
    $stored_audience_types = get_post_meta($post->ID, 'amb_audience', true) ?: [];
    generate_checkbox_group('An welche Zielgruppen richtet sich der Inhalt?', 'amb_audience', $audience_types, $stored_audience_types);

    // Hochschulfaechersystematik Checkbox-Gruppe
    /* 
    $hochschulfaechersystematik_types = amb_get_hochschulfaechersystematik();
    $stored_hochschulfaechersystematik_types = get_post_meta($post->ID, 'amb_hochschulfaechersystematik', true) ?: [];
    generate_checkbox_group('Welche Fächer betreffen den Inhalt?', 'amb_hochschulfaechersystematik', $hochschulfaechersystematik_types, $stored_hochschulfaechersystematik_types);
    */
    
    // Hochschulfaechersystematik Checkbox-Gruppe mit Narrower
    $hochschulfaechersystematik_types = amb_get_hochschulfaechersystematik_with_narrower();
    $stored_hochschulfaechersystematik_types = get_post_meta($post->ID, 'amb_hochschulfaechersystematik', true) ?: [];
    generate_checkbox_group_with_narrower('Welche Fächer betreffen den Inhalt?', 'amb_hochschulfaechersystematik', $hochschulfaechersystematik_types, $stored_hochschulfaechersystematik_types);

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




// Hilfsfunktion zum Durchsuchen und Hinzufügen verschachtelter Werte
function find_label_and_add($type_id, $available_types, &$to_save) {
    if (isset($available_types[$type_id])) {
        $to_save[] = [
            'id' => $type_id,
            'prefLabel' => ['de' => $available_types[$type_id]['label']],
            'type' => 'Concept'
        ];
        return true;
    }
    foreach ($available_types as $option_id => $details) {
        if (!empty($details['narrower'])) {
            if (find_label_and_add($type_id, $details['narrower'], $to_save)) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Speichert die Post-Metadaten.
 */
// Generische Hilfs-Funktion zum Speichern von Checkbox-Daten
function save_checkbox_data($post_id, $post_key, $fetch_function, $meta_key) {
    if (isset($_POST[$post_key])) {
        $selected_types = $_POST[$post_key];
        $available_types = $fetch_function();  // Funktion zum Abrufen der Optionen aufrufen
        $to_save = [];

        foreach ($selected_types as $type_id) {
            if (isset($available_types[$type_id])) {
                $to_save[] = [
                    'id' => $type_id,
                    'prefLabel' => ['de' => $available_types[$type_id]],
                    'type' => 'Concept'
                ];
            }
        }

        update_post_meta($post_id, $meta_key, $to_save);
    } else {
        delete_post_meta($post_id, $meta_key);
    }
}

function save_checkbox_data_with_narrower($post_id, $post_key, $fetch_function, $meta_key) {
    if (isset($_POST[$post_key])) {
        $selected_types = $_POST[$post_key];
        $available_types = $fetch_function();
        $to_save = [];

        foreach ($selected_types as $type_id) {
            find_label_and_add($type_id, $available_types, $to_save);
        }

        // Prüfen ob `to_save` nicht leer ist, bevor es gespeichert wird
        if (!empty($to_save)) {
            update_post_meta($post_id, $meta_key, $to_save);
        } else {
            delete_post_meta($post_id, $meta_key);
        }
    } else {
        delete_post_meta($post_id, $meta_key);
    }
}


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


    // Speicherung der hartkodierten Wertelisten-Auswahl
    $checkbox_options = amb_get_other_fields();
    foreach ($checkbox_options as $field_key => $field_data) {
        if (isset($_POST[$field_key])) {
            $selected_options = $_POST[$field_key];
            $option_map = [];

            // Innere Schleife zur Zuweisung der IDs zu Labels
            foreach ($field_data['options'] as $option) {
                foreach ($option as $id => $label) {
                    $option_map[$id] = $label;
                }
            }

            // Liste zum Speichern vorbereiten
            $to_save = [];
            foreach ($selected_options as $option_id) {
                if (isset($option_map[$option_id])) {
                    $to_save[] = [
                        'id' => $option_id,
                        'prefLabel' => ['de' => $option_map[$option_id]],
                        'type' => 'concept'
                    ];
                }
            }

            // Meta-Daten des Beitrags speichern
            update_post_meta($post_id, $field_key, $to_save);
        } else {
            // Meta-Daten löschen, wenn keine Optionen ausgewählt sind
            delete_post_meta($post_id, $field_key);
        }
    }

    // Aufruf der generischen Funktion für verschiedene Checkbox-Felder
    save_checkbox_data($post_id, 'amb_learningResourceType', 'amb_get_learning_resource_types', 'amb_learningResourceType');
    save_checkbox_data($post_id, 'amb_audience', 'amb_get_audience_roles', 'amb_audience');
    save_checkbox_data_with_narrower($post_id, 'amb_hochschulfaechersystematik', 'amb_get_hochschulfaechersystematik_with_narrower', 'amb_hochschulfaechersystematik');

    
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
        $amb_data = [
            'type' => get_post_meta($post->ID, 'amb_type', true) ?: $defaults['amb_type'] ?: ['LearningResource'],
            'description' => get_post_meta($post->ID, 'amb_description', true),
            'creator' => generate_creator_objects($creators),
            'keywords' => !empty($keywords) ? $keywords : '',
            'inLanguage' => get_post_meta($post->ID, 'amb_inLanguage', true) ?: $defaults['amb_inLanguage'] ?: ['de'],
            'publisher' => get_bloginfo('name'),
            'isAccessibleForFree' => get_post_meta($post->ID, 'amb_isAccessibleForFree', true) ?: $defaults['amb_isAccessibleForFree'] ?: 'true',
            'license' => get_post_meta($post->ID, 'amb_license', true) ?: $defaults['amb_license'],
            'conditionsOfAccess' => get_post_meta($post->ID, 'amb_conditionsOfAccess', true) ?: $defaults['amb_conditionsOfAccess'],
            'area' => get_post_meta($post->ID, 'amb_area', true) ?: $defaults['amb_area'],
            'learningResourceType' => get_post_meta($post->ID, 'amb_learningResourceType', true) ?: $defaults['amb_learningResourceType'],
            'audience' => get_post_meta($post->ID, 'amb_audience', true) ?: $defaults['amb_audience'],
            'educationalLevel' => get_post_meta($post->ID, 'amb_educationalLevel', true) ?: $defaults['amb_educationalLevel'],
            'aboutSubject' => get_post_meta($post->ID, 'amb_hochschulfaechersystematik', true) ?: $defaults['amb_hochschulfaechersystematik'],
            'aboutContext' => get_post_meta($post->ID, 'amb_organisationalContext', true) ?: $defaults['amb_organisationalContext']
        ];

        // Kombinieren der Felder "aboutSubject" und "aboutContext"
        $about = [];
        if ($amb_data['aboutSubject']) {
            $about[] = ["type" => "Concept", "name" => $amb_data['aboutSubject']];
        }
        if ($amb_data['aboutContext']) {
            $about[] = ["type" => "Concept", "name" => $amb_data['aboutContext']];
        }


        // JSON-LD-Struktur vorbereiten
        $json_ld_data = [
            "@context" => ["https://w3id.org/kim/amb/context.jsonld", "https://schema.org", ["@language" => "de"]],
            "id" => get_permalink($post->ID),
            "type" => $amb_data['type'],
            "creator" => $amb_data['creator'],
            "name" => get_the_title($post),
            "description" => $amb_data['description'],
            "keywords" => $amb_data['keywords'],
            "area" => $amb_data['area'],
            "inLanguage" => $amb_data['inLanguage'],
            "image" => get_the_post_thumbnail_url($post, 'full'),
            "dateCreated" => get_the_date('c', $post),
            "datePublished" => get_the_date('c', $post),
            "dateModified" => get_the_modified_date('c', $post),
            "publisher" => [["type" => "Organization", "name" => $amb_data['publisher']]],
            "isAccessibleForFree" => filter_var($amb_data['isAccessibleForFree'], FILTER_VALIDATE_BOOLEAN),
            "license" => ["id" => $amb_data['license']],
            "conditionsOfAccess" => ["id" => $amb_data['conditionsOfAccess'], "type" => "Concept"],
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


