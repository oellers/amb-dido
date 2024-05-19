<?php

defined('ABSPATH') or die('Zugriff verboten');

function add_metadata_to_content($content) {
    $options = get_option('amb_dido_display_metadata');
    amb_dido_log('Options: ' . var_export($options, true)); // Loggen der Options-Werte

    // Anpassung, um is_single oder is_page zu berücksichtigen
    $is_post_or_page = is_single() || is_page();
    $in_the_loop = in_the_loop();
    $is_main_query = is_main_query();

    amb_dido_log('is_post_or_page: ' . var_export($is_post_or_page, true));
    amb_dido_log('in_the_loop: ' . var_export($in_the_loop, true));
    amb_dido_log('is_main_query: ' . var_export($is_main_query, true));

    if ($options == "1" && $is_post_or_page && $in_the_loop && $is_main_query) {
        if (function_exists('show_post_metadata')) {
            $metadata_content = show_post_metadata();
            $content .= $metadata_content;
            amb_dido_log('Metadata added: ' . esc_html($metadata_content)); // Zum Debuggen
        } else {
            amb_dido_log('Function show_post_metadata not found.');
        }
    } else {
        amb_dido_log('Conditions not met for metadata display.');
    }
    return $content;
}

add_filter('the_content', 'add_metadata_to_content', 20); 

function show_post_metadata() {
    global $post;
    if (empty($post)) return '';

    $output = '<div class="post-metadata-box">';
    $options = get_option('amb_dido_metadata_display_options');

    // Dynamisch erstellte Felder basierend auf Benutzerauswahl
    $all_fields = array_merge(amb_get_other_fields(), amb_get_all_external_values());
    foreach ($all_fields as $key => $info) {
        if (!empty($options[$key])) {
            $metadata = get_post_meta($post->ID, $key, true);
            if (!empty($metadata)) {
                $output .= '<h3>' . esc_html($info['field_label']) . ':</h3><ul>';
                foreach ($metadata as $item) {
                    $output .= '<li>' . esc_html($item['prefLabel']['de']) . '</li>';
                }
                $output .= '</ul>';
            }
        }
    }

    $output .= '</div>';
    return $output;
}


/* alt:
function show_post_metadata() {
    global $post;
    if (empty($post)) return '';

    // Metadaten auslesen
    $resourceTypes = get_post_meta($post->ID, 'amb_learningResourceType', true);
    $audience = get_post_meta($post->ID, 'amb_audience', true);

    // Beginn des Infokastens
    $output = '<div class="post-metadata-box">';

    // Ressourcentypen, falls vorhanden
    if (!empty($resourceTypes)) {
        $output .= '<h3>Ressourcentypen:</h3><ul>';
        foreach ($resourceTypes as $type) {
            $output .= '<li>' . esc_html($type['prefLabel']['de']) . '</li>';
        }
        $output .= '</ul>';
    }

    // Zielgruppe, falls vorhanden
    if (!empty($audience)) {
        $output .= '<h3>Zielgruppe:</h3><ul>';
        foreach ($audience as $aud) {
            $output .= '<li>' . esc_html($aud['prefLabel']['de']) . '</li>';
        }
        $output .= '</ul>';
    }

    // Infokasten schließen
    $output .= '</div>';

    return $output;
}
*/

// Hook function für das Theme 
function show_amb_metadata($meta_key) {
    global $post;
    if (empty($post)) return;

    $config = get_metadata_config();
    $field_label = $config[$meta_key]['field_label'] ?? $meta_key;

    $metadata = get_post_meta($post->ID, $meta_key, true);
    if (empty($metadata)) return;

    echo '<div class="amb-metadata-box">';
    echo '<h3>' . esc_html($field_label) . ':</h3><ul>';

    if (is_array($metadata)) {
        foreach ($metadata as $item) {
            if (isset($item['prefLabel']['de'])) {
                echo '<li>' . esc_html($item['prefLabel']['de']) . '</li>';
            }
        }
    } else {
        echo '<li>' . esc_html($metadata) . '</li>';
    }

    echo '</ul></div>';
}

// Nutzung im Theme: <?php output_specific_post_metadata('amb_audience'); ?>