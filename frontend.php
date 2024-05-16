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

add_filter('the_content', 'add_metadata_to_content', 20); // Priorität geändert, um sicherzustellen, dass es nach den meisten anderen Anpassungen läuft





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
