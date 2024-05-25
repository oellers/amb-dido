<?php

/**
 *  OPTIONEN
 * --------------
 */

/**
 * Erstellt eine Optionsseite für das Plugin.
 */
function amb_dido_create_settings_page() {
    add_options_page(
        'AMB-DidO Einstellungen',
        'AMB-DidO Einstellungen',
        'manage_options',
        'amb_dido',
        'amb_dido_settings_page'
    );
}

/**
 * Ausgabe der Optionsseite.
 */
function amb_dido_settings_page() {
    ?>
    <div class="wrap">
        <h1>AMB-DidO Einstellungen</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('amb_dido_settings_group');
            do_settings_sections('amb_dido');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * Registriert die Einstellungen und Sektionen.
 */
function amb_dido_register_settings() {
    // Einstellungen registrieren
    register_setting('amb_dido_settings_group', 'amb_dido_post_types', 'amb_dido_sanitize_post_types');  // aktivierte Post-Typen
    register_setting('amb_dido_settings_group', 'amb_dido_defaults', 'amb_dido_sanitize_defaults'); // Standard-Werte für Felder
    register_setting('amb_dido_settings_group', 'amb_dido_metadata_display_options', 'amb_dido_sanitize_options'); // Frontend-Darstellung

    // Abschnitte und Felder hinzufügen
    add_settings_section('amb_dido_main_section', 'Post-Typen Einstellungen', null, 'amb_dido');
    add_settings_field('amb_dido_post_types_field', 'Aktivierte Post-Typen', 'amb_dido_post_types_field_html', 'amb_dido', 'amb_dido_main_section');

    // Alle verfügbaren Felder abrufen 
    $all_fields = array_merge(amb_get_other_fields(), amb_get_all_external_values());
    
    add_settings_section('amb_dido_default_section', 'Voreinstellungen für Metadaten', 'amb_dido_default_section_description', 'amb_dido');

    foreach ($all_fields as $key => $value) {
        add_settings_field($key, $value['field_label'], 'amb_dido_default_field_callback', 'amb_dido', 'amb_dido_default_section', ['id' => $key, 'options' => $value['options']]);
    } 

    add_settings_section(
        'amb_dido_metadata_section',
        'Anzeige der Metadaten im Frontend',
        'amb_dido_metadata_section_callback',
        'amb_dido'
    );

    // Die Felder für die Anzeige der Metadaten generieren
    foreach ($all_fields as $key => $info) {
        add_settings_field(
            $key,
            $info['field_label'],
            'amb_dido_checkbox_field_callback',
            'amb_dido',
            'amb_dido_metadata_section',
            ['id' => $key]
        );
    }

    // Neue Sektion für benutzerdefinierte Wertelisten hinzufügen
    add_settings_section(
        'amb_dido_custom_fields_section',
        'Benutzerdefinierte Wertelisten',
        'amb_dido_custom_fields_section_callback',
        'amb_dido'
    );

    // Feld für benutzerdefinierte Wertelisten hinzufügen
    add_settings_field(
        'amb_dido_custom_fields_field',
        '',
        'amb_dido_custom_fields_field_callback',
        'amb_dido',
        'amb_dido_custom_fields_section'
    );

    // Einstellung für benutzerdefinierte Wertelisten registrieren
    register_setting('amb_dido_settings_group', 'amb_dido_custom_fields', 'amb_dido_sanitize_custom_fields');



}


/** 
 * Anzeige von Metadaten im Frontend
**/


function amb_dido_metadata_section_callback() {
    echo '<p>Wählen Sie die Metadatenfelder, die im Frontend angezeigt werden sollen.</p>';
    echo '<p> Alternativ können Metadatenfelder im Editor per Shortcodes aufgerufen werden: <span class="amb-code">[show_amb_metadata field="amb_audience"]</span> oder <span class="amb-code">[show_amb_metadata]</span> für alle aktivierten Felder.</p>';
    echo '<p>Sie können auch beliebige Felder in Ihrem Theme mit <span class="amb-code">show_amb_metadata("NAME_DES_FELDS")</span> aufrufen.</p>';
    echo '<p>Folgende Felder können Sie dafür verwenden:</p>';

    $all_fields = array_merge(amb_get_other_fields(), amb_get_all_external_values());


    foreach ($all_fields as $field => $data) {

        echo $all_fields[$field]['field_label'] . ": <span class='amb-code'>" . $field . "</span> | ";
    }

}

function amb_dido_checkbox_field_callback($args) {
    $options = get_option('amb_dido_metadata_display_options');
    $checked = isset($options[$args['id']]) ? checked(1, $options[$args['id']], false) : '';
    echo '<input type="checkbox" id="'. esc_attr($args['id']) .'" name="amb_dido_metadata_display_options['. esc_attr($args['id']) .']" value="1" '. $checked .' />';
}

function amb_dido_sanitize_options($input) {
    $new_input = [];
    foreach($input as $key => $value) {
        if (isset($input[$key])) {
            $new_input[$key] = $value ? 1 : 0;
        }
    }
    return $new_input;
}

// veraltet:
function amb_dido_display_metadata_field_html() {
    $options = get_option('amb_dido_display_metadata');
    $checked = $options ? 'checked' : '';
    echo '<input type="checkbox" id="amb_dido_display_metadata" name="amb_dido_display_metadata" value="1" ' . $checked . '>';
    echo '<label for="amb_dido_display_metadata"> Metadaten im Frontend anzeigen</label>';
}


/** 
 * Standardwerte für Metadaten
**/

function amb_dido_default_section_description() {
    echo '<p>Die Voreinstellungen hier vornehmen, wenn sie für alle Ressourcen gesetzt werden sollen. Diese Felder werden dann im Editor nicht mehr angezeigt. Sie können Felder auch ausblenden, ohne einen Standardwert zu setzen. </p>';
}

function amb_dido_default_field_callback($args) {
  $options = get_option('amb_dido_defaults');
  echo "<select name='amb_dido_defaults[{$args['id']}]'>";
  echo "<option value=''>--Keine Auswahl--</option>"; // Option "Keine Auswahl" hinzufügen
  echo "<option value='deactivate'" . ($options[$args['id']] === 'deactivate' ? ' selected="selected"' : '') . ">--Feld ausblenden--</option>"; // Option "Feld ausblenden" hinzufügen
  foreach ($args['options'] as $option_array) {
    foreach ($option_array as $id => $label) {
        if(!is_array($label)) {
          $selected = isset($options[$args['id']]) && $options[$args['id']] == $id ? 'selected="selected"' : '';
          echo "<option value='$id' $selected>$label</option>";
      }
    }
  }
  echo "</select>";
}


function amb_dido_sanitize_defaults($value) {
  if (!isset($value) || empty($value)) {
    return ''; // Return empty string for invalid values (except "deactivate")
  } elseif ($value === 'deactivate') {
    return 'deactivate'; // Return "deactivate" as is
  } else {
    return $value; // Return the sanitized value
  }
}


register_setting('amb_dido_settings_group', 'amb_dido_defaults', 'amb_dido_sanitize_defaults');

// veraltet:
function amb_dido_default_field_html() {
    echo '<p>Die Voreinstellungen hier vornehmen, wenn sie für alle Ressourcen gesetzt werden sollen. Diese Felder werden dann im Editor nicht mehr angezeigt. ';
    $fields = amb_get_other_fields();
    foreach ($fields as $key => $value) {
        add_settings_field($key, $value['field_label'], 'amb_dido_default_field_callback', 'amb_dido', 'amb_dido_default_section', ['id' => $key, 'options' => $value['options']]);
    }
}


/** 
 * Post-Typen Auswahl 
**/

function amb_dido_post_types_field_html() {
    $selected_post_types = get_option('amb_dido_post_types', []);
    $all_post_types = get_post_types(['public' => true], 'objects');

    foreach ($all_post_types as $post_type) {
        $is_checked = in_array($post_type->name, $selected_post_types) ? 'checked' : '';
        echo '<input type="checkbox" name="amb_dido_post_types[]" value="' . esc_attr($post_type->name) . '" ' . $is_checked . '> ' . esc_html($post_type->label) . '<br>';
    }
}


function amb_dido_sanitize_post_types($input) {
    $valid_post_types = get_post_types(['public' => true]);
    return array_intersect($valid_post_types, $input);
}


/**
 * Callback-Funktion für die Sektion der benutzerdefinierten Wertelisten.
 */
function amb_dido_custom_fields_section_callback() {
    echo '<p>Fügen Sie hier benutzerdefinierte Wertelisten hinzu, indem Sie eine URL und einen AMB-Schlüssel angeben.</p>';
}

/**
 * Callback-Funktion für die Formularfelder der benutzerdefinierten Wertelisten.
 */
function amb_dido_custom_fields_field_callback() {
    $options = get_option('amb_dido_custom_fields', []);
    $amb_keys = ['about', 'teaches', 'assesses', 'audience', 'interactivityType'];

    echo '<div id="amb_dido_custom_fields_container">';
    $counter = 1;
    foreach ($options as $custom_field) {
        amb_dido_render_custom_field($custom_field['url'], $custom_field['key'], $counter);
        $counter++;
    }
    echo '</div>';
    echo '<button type="button" id="amb_dido_add_custom_field">Mehr hinzufügen</button>';
}

/**
 * Funktion zum Rendern eines einzelnen Formularfeldes für benutzerdefinierte Wertelisten.
 */
function amb_dido_render_custom_field($url, $key, $counter) {
    $amb_keys = ['about', 'teaches', 'assesses', 'audience', 'interactivityType', 'competencyRequired', 'educationalLevel'];
    $meta_key = 'amb_custom' . $counter;

    echo '<div class="amb_dido_custom_field_row">';
    echo '<div class="amb_dido_custom_field_column">';
    echo '<div class="amb_dido_custom_field_header">JSON-URL der Wertliste</div>';
    echo '<input type="url" name="amb_dido_custom_fields[' . esc_attr($meta_key) . '][url]" value="' . esc_attr($url) . '" placeholder="URL eingeben" />';
    echo '</div>';
    echo '<div class="amb_dido_custom_field_column">';
    echo '<div class="amb_dido_custom_field_header">AMB-Feld</div>';
    echo '<select name="amb_dido_custom_fields[' . esc_attr($meta_key) . '][key]">';
    foreach ($amb_keys as $amb_key) {
        $selected = ($key === $amb_key) ? 'selected' : '';
        echo '<option value="' . esc_attr($amb_key) . '" ' . $selected . '>' . esc_html($amb_key) . '</option>';
    }
    echo '</select>';
    echo '<input type="hidden" name="amb_dido_custom_fields[' . esc_attr($meta_key) . '][meta_key]" value="' . esc_attr($meta_key) . '" />';
    echo '</div>';
    echo '</div>';
}

/**
 * Sanitize-Funktion für benutzerdefinierte Wertelisten.
 */
function amb_dido_sanitize_custom_fields($input) {
    $sanitized_input = [];
    foreach ($input as $custom_field) {
        $sanitized_url = esc_url_raw($custom_field['url']);
        $sanitized_key = in_array($custom_field['key'], ['about', 'teaches', 'assesses', 'audience', 'interactivityType']) ? $custom_field['key'] : '';
        $meta_key = isset($custom_field['meta_key']) ? $custom_field['meta_key'] : '';

        if (!empty($sanitized_url) && !empty($sanitized_key) && !empty($meta_key)) {
            $sanitized_input[$meta_key] = [
                'url' => $sanitized_url,
                'key' => $sanitized_key,
                'meta_key' => $meta_key,
            ];
        }
    }

    return $sanitized_input;
}

// JavaScript-Funktion für dynamische Formularfelder
function amb_dido_custom_fields_js() {
    ?>
    <script>
        (function($) {
            var counter = <?php echo count(get_option('amb_dido_custom_fields', [])) + 1; ?>;

            $('#amb_dido_add_custom_field').on('click', function() {
                var newField = amb_dido_render_custom_field('', '', counter);
                $('#amb_dido_custom_fields_container').append(newField);
                counter++;
            });

            function amb_dido_render_custom_field(url, key, counter) {
                var $row = $('<div>', { 'class': 'amb_dido_custom_field_row' });

                var $columnUrl = $('<div>', { 'class': 'amb_dido_custom_field_column' });
                var $headerUrl = $('<div>', { 'class': 'amb_dido_custom_field_header', text: 'JSON-URL der Wertliste' });
                var $urlInput = $('<input>', { type: 'url', name: 'amb_dido_custom_fields[' + counter + '][url]', placeholder: 'URL eingeben', value: url });
                $columnUrl.append($urlInput);

                var $columnKey = $('<div>', { 'class': 'amb_dido_custom_field_column' });
                var $headerKey = $('<div>', { 'class': 'amb_dido_custom_field_header', text: 'AMB-Feld' });
                var $keySelect = $('<select>', { name: 'amb_dido_custom_fields[' + counter + '][key]' });
                var ambKeys = ['about', 'teaches', 'assesses', 'audience', 'interactivityType'];
                $.each(ambKeys, function(index, ambKey) {
                    var $option = $('<option>', { value: ambKey, text: ambKey });
                    if (ambKey === key) {
                        $option.attr('selected', 'selected');
                    }
                    $keySelect.append($option);
                });
                $columnKey.append($keySelect);

                var $metaKeyInput = $('<input>', { type: 'hidden', name: 'amb_dido_custom_fields[' + counter + '][meta_key]', value: 'amb_custom' + counter });
                $row.append($columnUrl, $columnKey, $metaKeyInput);

                return $row;
            }
        })(jQuery);
    </script>
    <?php
}
add_action('admin_footer', 'amb_dido_custom_fields_js');