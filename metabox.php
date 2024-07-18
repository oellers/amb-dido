<?php

/**
**  Keywords Wordpress Taxonomy und Metabox
**/

add_action('init', 'amb_dido_register_taxonomy'); 
add_action('do_meta_boxes', 'amb_dido_move_ambkeywords_metabox_location');

function amb_dido_register_taxonomy() {
    //$show_ui = "yes";
    $show_ui = get_option('show_ambkeywords_in_menu', 'yes') === 'yes';
    $selected_post_types = get_option('amb_dido_post_types', []);
    register_taxonomy('ambkeywords', $selected_post_types, [
        'hierarchical' => false,
        'labels' => [
            'name' => 'AMB Keywords',
            'singular_name' => 'AMB Keyword',
            'menu_name' => 'AMB Keywords',
            'all_items' => 'Alle AMB Keywords',
            'edit_item' => 'AMB Keyword bearbeiten',
            'view_item' => 'AMB Keyword ansehen',
            'update_item' => 'AMB Keyword aktualisieren',
            'add_new_item' => 'Neues AMB Keyword hinzufügen',
            'new_item_name' => 'Neuer AMB Keyword Name',
            'search_items' => 'AMB Keywords suchen',
            'popular_items' => 'Beliebte AMB Keywords',
            'separate_items_with_commas' => 'AMB Keywords mit Kommas trennen',
            'add_or_remove_items' => 'AMB Keywords hinzufügen oder entfernen',
            'choose_from_most_used' => 'Aus den am meisten verwendeten AMB Keywords wählen',
            'not_found' => 'Keine AMB Keywords gefunden',
        ],
        'show_ui' => $show_ui,
        'show_in_menu' => $show_ui,
        'show_in_nav_menus' => false,
        'query_var' => true,
        'rewrite' => ['slug' => 'ambkeyword'],
        'show_in_rest' => true, // Um die Unterstützung des Gutenberg-Editors zu gewährleisten
    ]);
}

function amb_dido_register_keywords_meta_box() {
    add_meta_box(
        'amb_dido_keywords_meta_box',
        'AMB Keywords',
        'amb_dido_keywords_meta_box_callback',
        'post',
        'normal',
        'default'
    );
}

function amb_dido_move_ambkeywords_metabox_location(){
    global $wp_meta_boxes;
    // Stelle sicher, dass die Standard-Metabox entfernt wird, bevor du deine hinzufügst
    unset($wp_meta_boxes['post']['normal']['core']['tagsdiv-ambkeywords']);
    $selected_post_types = get_option('amb_dido_post_types', []);
    add_meta_box(
        'tagsdiv-ambkeywords',
        __('AMB Keywords', 'text-domain'),
        'post_tags_meta_box', 
        $selected_post_types, 
        'normal',
        'high',
        ['taxonomy' => 'ambkeywords']
    );
}
