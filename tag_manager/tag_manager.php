<?php
    /*
        Plugin Name: Tag Manager
        Plugin URI: https://github.com/fubrzzik/tag_manager
        Description: Simple tag manager
        Version: 1.0
        Author: fubrzzik
    */

    // styles
    function tag_manager_enqueue() {
        wp_enqueue_style('tag-manager', plugins_url('tag-manager.css', __FILE__));
    }
    add_action('admin_enqueue_scripts', 'tag_manager_enqueue');

    // post type
    function tag_manager_post_type() {
        $labels = array(
            'name' => __('Tags', 'tag_manager'),
            'singular_name' => __('Tag', 'tag_manager'),
            'add_new' => __('Add new tag', 'tag_manager'),
            'add_new_item' => __('Add new tag', 'tag_manager'),
            'edit_item' => __('Edit tag', 'tag_manager'),
            'new_item' => __('New tag', 'tag_manager'),
            'view_item' => __('Show tag', 'tag_manager'),
            'search_items' => __('Search tags', 'tag_manager'),
            'not_found' => __('No tags found', 'tag_manager'),
            'not_found_in_trash' => __('No tags found in trash', 'tag_manager'),
            'menu_name' => __('HTML Tags', 'tag_manager')
        );

        $capabilities = array(
            'publish_posts' => 'publish_tag_manager',
            'edit_posts' => 'edit_tag_manager',
            'edit_others_posts' => 'edit_others_tag_manager',
            'delete_posts' => 'delete_tag_manager',
            'delete_others_posts' => 'delete_others_tag_manager',
            'read_private_posts' => 'read_private_tag_manager',
            'edit_post' => 'edit_tag_manager',
            'delete_post' => 'delete_tag_manager',
            'read_post' => 'read_tag_manager',
        );

        $args = array(
            'labels' => $labels,
            'public' => false,
            'exclude_from_search' => true,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => array('title', 'editor'),
            'capability_type' => 'tag_manager',
            'capabilities' => $capabilities
        );
        register_post_type('tag_manager', $args);
    }
    add_action('init', 'tag_manager_post_type');

    // privilages
    function tag_manager_assign_capabilities() {
        $administrator = get_role('administrator');
        $administrator->add_cap('publish_tag_manager');
        $administrator->add_cap('edit_tag_manager');
        $administrator->add_cap('edit_others_tag_manager');
        $administrator->add_cap('delete_tag_manager');
        $administrator->add_cap('delete_others_tag_manager');
        $administrator->add_cap('read_private_tag_manager');
        $administrator->add_cap('edit_post');
        $administrator->add_cap('delete_post');
        $administrator->add_cap('read_post');
    }
    add_action('init', 'tag_manager_assign_capabilities');

    // custom fields
    function tag_manager_custom_field_metabox() {
        add_meta_box(
            'tag_manager_settings',
            __('Tag settings', 'tag_manager'),
            'tag_manager_settings_callback',
            'tag_manager',
            'normal',
            'high'
        );
    }
    add_action('add_meta_boxes', 'tag_manager_custom_field_metabox');

    function tag_manager_settings_callback($post) {
        $status = get_post_meta($post->ID, 'tag_status', true);

        ?>
            <label for="tag_status"><b>Status:</b></label>
            <select name="tag_status" id="tag_status">
                <option value="active" <?php selected($status, 'active'); ?>><?php _e('Active', 'tag_manager'); ?></option>
                <option value="inactive" <?php selected($status, 'inactive'); ?>><?php _e('Inactive', 'tag_manager'); ?></option>
            </select>

            <br/><br/>
        <?php

        $code_type = get_post_meta($post->ID, 'code_type', true);

        ?>
            <label for="code_type"><b>Rodzaj kodu:</b></label>
            <select name="code_type" id="code_type">
                <option value="html" <?php selected($code_type, 'html'); ?>>HTML</option>
                <option value="script" <?php selected($code_type, 'script'); ?>>Tag &lt;script&gt;</option>
            </select>

            <br/><br/>
        <?php

        $selected_subpages = get_post_meta($post->ID, 'tag_manager_subpages', true);
        $all_subpages = get_pages();

        if (empty($selected_subpages)) {
            $selected_subpages = array();
        }

        echo '<label><b>' . __('Select the subpages where the tag should be displayed', 'tag_manager') . ':</b></label>';
        echo '<br/><br/>';

        echo '<label><input type="checkbox" name="tag_manager_subpages[]" value="-1" ' . checked(in_array('-1', $selected_subpages), true, false) . '> ' . __('Display on all subpages (wildcard)', 'tag_manager') . '</label><br>';

        foreach ($all_subpages as $subpage) {
            $is_selected = in_array($subpage->ID, $selected_subpages) ? 'checked' : '';
            echo '<label><input type="checkbox" name="tag_manager_subpages[]" value="' . esc_attr($subpage->ID) . '" ' . $is_selected . '> ' . esc_html($subpage->post_title) . '</label><br>';
        }
    }

    function tag_manager_save_settings($post_id) {
        if (isset($_POST['tag_status'])) {
            $status = sanitize_text_field($_POST['tag_status']);
            update_post_meta($post_id, 'tag_status', $status);
        }

        if (isset($_POST['code_type'])) {
            $code_type = sanitize_text_field($_POST['code_type']);
            update_post_meta($post_id, 'code_type', $code_type);
        }

        if (isset($_POST['tag_manager_subpages'])) {
            $selected_subpages = $_POST['tag_manager_subpages'];

            $is_wildcard_selected = in_array('-1', $selected_subpages);

            if ($is_wildcard_selected) {
                // wildcard
            } else {
                $selected_subpages = array_map('intval', $selected_subpages);
            }

            update_post_meta($post_id, 'tag_manager_subpages', $selected_subpages);
        }
    }
    add_action('save_post', 'tag_manager_save_settings');

    // display status
    function tag_manager_post_columns($columns) {
        $new_columns = array(
            'title' => $columns['title'],
            'tag_status' => __('Status', 'tag_manager'),
            'date' => $columns['date'],
        );

        return $new_columns;
    }
    add_filter('manage_tag_manager_posts_columns', 'tag_manager_post_columns');

    function tag_manager_post_column_content($column_name, $post_id) {
        if ($column_name === 'tag_status') {
            $tag_status = get_post_meta($post_id, 'tag_status', true);
            echo ($tag_status === 'active') ? '<span class="tag_status_active">' . __('Active', 'tag_manager') . '</span>' : '<span class="tag_status_inactive">' . __('Inactive', 'tag_manager') . '</span>';
        }
    }
    add_action('manage_tag_manager_posts_custom_column', 'tag_manager_post_column_content', 10, 2);

    // insert tags
    function get_custom_tags() {
        $tags = array();

        $args = array(
            'post_type' => 'tag_manager',
            'posts_per_page' => -1,
        );

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();

                $tag_status = get_post_meta(get_the_ID(), 'tag_status', true);
                $code_type = get_post_meta(get_the_ID(), 'code_type', true);
                $subpages = get_post_meta(get_the_ID(), 'tag_manager_subpages', true);

                if (!empty($code_type) && !empty($subpages)) {
                    $tags[] = array(
                        'content' => get_the_content(),
                        'tag_status' => $tag_status,
                        'code_type' => $code_type,
                        'subpages' => $subpages,
                    );
                }
            }

            wp_reset_postdata();
        }

        return $tags;
    }

    function insert_tag_manager_in_footer() {
        $tags = get_custom_tags();

        foreach ($tags as $tag_data) {
            $content = $tag_data['content'];
            $tag_status = $tag_data['tag_status'];
            $code_type = $tag_data['code_type'];
            $subpages = $tag_data['subpages'];

            if ($tag_status === 'active' && (in_array('-1', $subpages) || is_page($subpages))) {
                if ($code_type === 'script') {
                    echo '<script type="text/javascript">' . $content . '</script>';
                } else {
                    echo $content;
                }
            }
        }
    }
    add_action('wp_footer', 'insert_tag_manager_in_footer');
?>