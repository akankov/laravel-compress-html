<?php

declare(strict_types=1);

/*
 * Defaults mirror Akankov\HtmlMin\Config\MinifierOptions verbatim.
 * Snake_case keys are mapped to MinifierOptions's camelCase properties
 * by HtmlMinServiceProvider::optionsFromConfig().
 */
return [
    'optimize_via_html_dom_parser' => true,
    'optimize_attributes' => true,
    'remove_comments' => true,
    'remove_whitespace_around_tags' => false,
    'remove_omitted_quotes' => true,
    'remove_omitted_html_tags' => true,
    'remove_http_prefix_from_attributes' => false,
    'remove_https_prefix_from_attributes' => false,
    'keep_http_and_https_prefix_on_external_attributes' => false,
    'sort_css_class_names' => true,
    'sort_html_attributes' => true,
    'remove_deprecated_script_charset_attribute' => true,
    'remove_default_attributes' => false,
    'remove_deprecated_anchor_name' => true,
    'remove_deprecated_type_from_stylesheet_link' => true,
    'remove_deprecated_type_from_style_and_link_tag' => true,
    'remove_default_media_type_from_style_and_link_tag' => true,
    'remove_default_type_from_button' => false,
    'remove_deprecated_type_from_script_tag' => true,
    'remove_value_from_empty_input' => true,
    'remove_empty_attributes' => true,
    'sum_up_whitespace' => true,
    'remove_spaces_between_tags' => false,
    'keep_broken_html' => false,
    'local_domains' => [],
    'special_html_comments_starting_with' => [],
    'special_html_comments_ending_with' => [],
    'special_script_tags' => null,
    'template_logic_syntax_in_special_script_tags' => null,
];
