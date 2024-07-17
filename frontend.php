<?php

defined('ABSPATH') or die('Zugriff verboten');


/**
 * Metadaten-Box in das Frontend einspielen
 **/

function add_metadata_to_content($content) {
    $options = get_option('amb_dido_display_metadata');

    // Anpassung, um is_single oder is_page zu berücksichtigen
    $is_post_or_page = is_single() || is_page();
    $in_the_loop = in_the_loop();
    $is_main_query = is_main_query();

    if ($options && $is_post_or_page && $in_the_loop && $is_main_query) {
        if (function_exists('amb_dido_show_post_metadata')) {
            $metadata_content = amb_dido_show_post_metadata();
            $content .= $metadata_content;
        } else {
            // do nothing
        }
    } else {
        // do nothing
    }
    return $content;
}

aassa



add_filter('the_content', 'add_metadata_to_content', 20); 


/**
* Anzeigen der ausgewählten Metadaten im Frontend
**/ 

function amb_dido_show_post_metadata() {
    global $post;
    if (empty($post)) return '';

    $output = '<div class="amb-metadata-box">';
    $options = get_option('amb_dido_metadata_display_options');

    $all_fields = array_merge(amb_dido_get_other_fields(), amb_dido_get_all_external_values());
    foreach ($all_fields as $key => $info) {
        if (!empty($options[$key])) {
            $metadata = get_post_meta($post->ID, $key, true);
            if (!empty($metadata)) {
                $output .= '<h4>' . esc_html($info['field_label']) . ':</h4><ul>';
                foreach ($metadata as $item) {
                    if (isset($item['prefLabel']['de'])) {
                        $label = esc_html($item['prefLabel']['de']);
                        $search_url = esc_url(add_query_arg('s', urlencode("$key: $label"), home_url('/')));
                        $output .= '<li class="is-style-pill wp-block-post-terms"><a href="' . $search_url . '">' . $label . '</a></li>';
                    }
                }
                $output .= '</ul>';
            }
        }
    }

    $output .= '</div>';
    return $output;
}



/** 
 * Hook function für das Theme
 *
 * @deprecated no usage found
 **/ 
function show_amb_metadata($meta_key) {
    global $post;
    if (empty($post)) return;

    $all_fields = array_merge(amb_dido_get_other_fields(), amb_dido_get_all_external_values());

    if (!isset($all_fields[$meta_key])) return; 

    $metadata = get_post_meta($post->ID, $meta_key, true);
    if (empty($metadata)) return; 

    $field_label = $all_fields[$meta_key]['field_label'] ?? $meta_key; 

    echo '<div class="amb-metadata-box">';
    echo '<h4>' . esc_html($field_label) . ':</h4><ul>';

    if (is_array($metadata)) {
        foreach ($metadata as $item) {
            if (isset($item['prefLabel']['de'])) {
                $label = esc_html($item['prefLabel']['de']);
                $search_url = esc_url(add_query_arg('s', urlencode("$meta_key: $label"), home_url('/')));
                echo '<li><a href="' . $search_url . '">' . $label . '</a></li>';
            }
        }
    } else {
        $label = esc_html($metadata);
        $search_url = esc_url(add_query_arg('s', urlencode("$meta_key: $label"), home_url('/')));
        echo '<li><a href="' . $search_url . '">' . $label . '</a></li>';
    }

    echo '</ul></div>';
}
/* Nutzung im Theme: show_amb_metadata('amb_audience');  */


/** 
 * Shortcode für das Theme
 **/ 
function amb_dido_show_amb_metadata_shortcode($atts) {
    global $post;
    if (empty($post)) return '';

    // Standardattributwerte setzen
    $atts = shortcode_atts([
        'field' => '', // Kein Standardfeld, benutze Optionen
    ], $atts);

    // Holen Sie sich die gesamte Feldkonfiguration
    $all_fields = array_merge(amb_dido_get_other_fields(), amb_dido_get_all_external_values());

    $output = '';
    $options = get_option('amb_dido_metadata_display_options', []);

    if ($atts['field']) {
        // Ein spezifisches Feld anzeigen, wenn angegeben
        $fields = [$atts['field']];
    } else {
        // Hole die aktivierten Felder aus den Optionen
        $fields = array_keys(array_filter($options, function($value) { return $value == true; }));
    }

    foreach ($fields as $field) {
        if (!isset($all_fields[$field])) continue; // Überspringe, falls keine Feldkonfiguration vorhanden

        $metadata = get_post_meta($post->ID, $field, true);
        if (!empty($metadata)) {
            $field_label = $all_fields[$field]['field_label'] ?? $field; // Benutze das Feldlabel oder den Schlüssel als Fallback
            $output .= '<div class="amb-metadata-box">';
            $output .= '<h4>' . esc_html($field_label) . ':</h4><ul>';

            if (is_array($metadata)) {
                foreach ($metadata as $item) {
                    if (isset($item['prefLabel']['de'])) {
                        $label = esc_html($item['prefLabel']['de']);
                        $search_url = esc_url(add_query_arg('s', urlencode("$field: $label"), home_url('/')));
                        $output .= '<li><a href="' . $search_url . '">' . $label . '</a></li>';
                    }
                }
            } else {
                $label = esc_html($metadata);
                $search_url = esc_url(add_query_arg('s', urlencode("$field: $label"), home_url('/')));
                $output .= '<li><a href="' . $search_url . '">' . $label . '</a></li>';
            }

            $output .= '</ul></div>';
        }
    }

    return $output;
}


function amb_dido_register_amb_metadata_shortcode() {
    add_shortcode('show_amb_metadata', 'amb_dido_show_amb_metadata_shortcode');
}
add_action('init', 'amb_dido_register_amb_metadata_shortcode');
/* Nutzung im Editor: [show_amb_metadata field="amb_audience"] oder [show_amb_metadata] für alle aktivierten Felder */
/* Nutzung im Theme: echo do_shortcode('[show_amb_metadata field="amb_learningResourceType"]'); */