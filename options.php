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
    register_setting('amb_dido_settings_group', 'amb_dido_display_metadata', 'absint'); // Neue Einstellung für die Anzeige der Metadaten

    // Abschnitte und Felder hinzufügen
    add_settings_section('amb_dido_main_section', 'Post-Typen Einstellungen', null, 'amb_dido');
    add_settings_field('amb_dido_post_types_field', 'Aktivierte Post-Typen', 'amb_dido_post_types_field_html', 'amb_dido', 'amb_dido_main_section');

    add_settings_section('amb_dido_display_metadata_section', 'Anzeigeoptionen', null, 'amb_dido');
    add_settings_field('amb_dido_display_metadata_field', 'Metadaten im Frontend anzeigen', 'amb_dido_display_metadata_field_html', 'amb_dido', 'amb_dido_display_metadata_section');
    
    add_settings_section('amb_dido_default_section', 'Voreinstellungen für Metadaten', 'amb_dido_default_section_description', 'amb_dido');
    $fields = amb_get_other_fields();
    foreach ($fields as $key => $value) {
        add_settings_field($key, $value['field_label'], 'amb_dido_default_field_callback', 'amb_dido', 'amb_dido_default_section', ['id' => $key, 'options' => $value['options']]);
    }
    $fields2 = amb_get_all_external_values();
    foreach ($fields2 as $key => $value) {
        add_settings_field($key, $value['field_label'], 'amb_dido_default_field_callback', 'amb_dido', 'amb_dido_default_section', ['id' => $key, 'options' => $value['options']]);
    }



}


function amb_dido_display_metadata_field_html() {
    $options = get_option('amb_dido_display_metadata');
    $checked = $options ? 'checked' : '';
    echo '<input type="checkbox" id="amb_dido_display_metadata" name="amb_dido_display_metadata" value="1" ' . $checked . '>';
    echo '<label for="amb_dido_display_metadata"> Metadaten im Frontend anzeigen</label>';
}


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

function amb_dido_audience_roles_html() {
    $fields = amb_get_audience_roles();
    foreach ($fields as $key => $value) {
        add_settings_field($key, $value['field'], 'amb_dido_default_field_callback', 'amb_dido', 'amb_dido_audience_roles_section', ['id' => $key, 'options' => $value['options']]);
    }
}

function amb_dido_post_types_field_html() {
    $selected_post_types = get_option('amb_dido_post_types', []);
    $all_post_types = get_post_types(['public' => true], 'objects');

    foreach ($all_post_types as $post_type) {
        $is_checked = in_array($post_type->name, $selected_post_types) ? 'checked' : '';
        echo '<input type="checkbox" name="amb_dido_post_types[]" value="' . esc_attr($post_type->name) . '" ' . $is_checked . '> ' . esc_html($post_type->label) . '<br>';
    }
}

/**
 * Sanitize Callback für Post-Typ-Einstellungen.
 */
function amb_dido_sanitize_post_types($input) {
    $valid_post_types = get_post_types(['public' => true]);
    return array_intersect($valid_post_types, $input);
}