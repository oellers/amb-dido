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
    // Vorhandene Einstellungen
    register_setting('amb_dido_settings_group', 'amb_dido_post_types', 'amb_dido_sanitize_post_types');
    register_setting('amb_dido_settings_group', 'amb_dido_defaults', 'amb_dido_sanitize_defaults');
    register_setting(
        'amb_dido_settings_group',
        'amb_dido_metadata_display_options',
        'amb_dido_sanitize_options'
    );

    // Abschnitte und Felder hinzufügen
    add_settings_section('amb_dido_main_section', 'Post-Typen Einstellungen', null, 'amb_dido');
    add_settings_field('amb_dido_post_types_field', 'Aktivierte Post-Typen', 'amb_dido_post_types_field_html', 'amb_dido', 'amb_dido_main_section');

    //add_settings_section('amb_dido_display_metadata_section', 'Anzeigeoptionen', null, 'amb_dido');
    //add_settings_field('amb_dido_display_metadata_field', 'Metadaten im Frontend anzeigen', 'amb_dido_display_metadata_field_html', 'amb_dido', 'amb_dido_display_metadata_section');
    
    add_settings_section('amb_dido_default_section', 'Voreinstellungen für Metadaten', 'amb_dido_default_section_description', 'amb_dido');
    $fields = amb_get_other_fields();
    foreach ($fields as $key => $value) {
        add_settings_field($key, $value['field_label'], 'amb_dido_default_field_callback', 'amb_dido', 'amb_dido_default_section', ['id' => $key, 'options' => $value['options']]);
    }
    $fields2 = amb_get_all_external_values();
    foreach ($fields2 as $key => $value) {
        add_settings_field($key, $value['field_label'], 'amb_dido_default_field_callback', 'amb_dido', 'amb_dido_default_section', ['id' => $key, 'options' => $value['options']]);
    }

    add_settings_section(
        'amb_dido_metadata_section',
        'Anzeige der Metadaten im Frontend',
        'amb_dido_metadata_section_callback',
        'amb_dido'
    );

    // Die Felder für die Anzeige der Metadaten generieren
    $all_fields = array_merge(amb_get_other_fields(), amb_get_all_external_values());
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



}


/** 
 * Anzeige von Metadaten im Frontend
**/


function amb_dido_metadata_section_callback() {
    echo '<p>Wählen Sie die Metadatenfelder, die im Frontend angezeigt werden sollen.</p>';
    echo '<p>Sie können auch beliebige Felder in Ihrem Theme mit <span class="amb-code">show_amb_metadata("NAME_DES_FELDS")</span> aufrufen.</p>';
    echo '<p>Folgende Felder können Sie dafür verwenden:</p>';

    $all_fields = array_merge(amb_get_other_fields(), amb_get_all_external_values());


    foreach ($all_fields as $field => $data) {

        echo $all_fields[$field]['field_label'] . ": <span class='code'>" . $field . "</span> | ";
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
    echo '<p>Die Voreinstellungen hier vornehmen, wenn sie für alle Ressourcen gesetzt werden sollen. Diese Felder werden dann im Editor nicht mehr angezeigt.</p>';
}

function amb_dido_default_field_callback($args) {
    $options = get_option('amb_dido_defaults');
    echo "<select name='amb_dido_defaults[{$args['id']}]'>";
    echo "<option value=''>Keine Auswahl</option>"; // Option "Keine Auswahl" hinzufügen
    foreach ($args['options'] as $option_array) {
        foreach ($option_array as $id => $label) {
            $selected = isset($options[$args['id']]) && $options[$args['id']] == $id ? 'selected="selected"' : '';
            echo "<option value='$id' $selected>$label</option>";
        }
    }
    echo "</select>";
}

function amb_dido_default_field_html() {
    echo '<p>Die Voreinstellungen hier vornehmen, wenn sie für alle Ressorcen gesetzt werden sollen. Diese Felder werden dann im Editor nicht mehr angezeigt.';
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