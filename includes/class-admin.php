<?php
/**
 * Admin functionality
 *
 * @package Scoped_CSS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin class - handles CPT UI, meta boxes, and saves
 */
class Scoped_CSS_Admin {

    /**
     * Initialize admin hooks
     */
    public function init(): void {
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post_scoped_css', array( $this, 'save_post' ), 10, 2 );
        add_filter( 'manage_scoped_css_posts_columns', array( $this, 'custom_columns' ) );
        add_action( 'manage_scoped_css_posts_custom_column', array( $this, 'column_content' ), 10, 2 );
        add_filter( 'post_row_actions', array( $this, 'row_actions' ), 10, 2 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_filter( 'enter_title_here', array( $this, 'title_placeholder' ), 10, 2 );
        
        // Quick status toggle via AJAX
        add_action( 'wp_ajax_scoped_css_toggle_status', array( $this, 'ajax_toggle_status' ) );
    }

    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        // CSS Editor meta box
        add_meta_box(
            'scoped_css_editor',
            __( 'CSS Code', 'scoped-css' ),
            array( $this, 'render_editor_meta_box' ),
            'scoped_css',
            'normal',
            'high'
        );

        // Output Location meta box
        add_meta_box(
            'scoped_css_location',
            __( 'Output Location', 'scoped-css' ),
            array( $this, 'render_location_meta_box' ),
            'scoped_css',
            'side',
            'default'
        );

        // Conditions meta box
        add_meta_box(
            'scoped_css_conditions',
            __( 'Display Conditions', 'scoped-css' ),
            array( $this, 'render_conditions_meta_box' ),
            'scoped_css',
            'normal',
            'default'
        );
    }

    /**
     * Render CSS editor meta box
     *
     * @param WP_Post $post Post object.
     */
    public function render_editor_meta_box( $post ) {
        wp_nonce_field( 'scoped_css_save', 'scoped_css_nonce' );
        wp_nonce_field( 'scoped_css_minify', 'scoped_css_minify_nonce' );
        $css_content = $post->post_content;
        ?>
        <div class="scoped-css-editor-wrap">
            <div style="margin-bottom: 10px; display: flex; gap: 8px;">
                <button type="button" id="scoped-css-minify-btn" class="button" title="<?php esc_attr_e( 'Minify CSS', 'scoped-css' ); ?>">
                    <span class="dashicons dashicons-screenoptions" style="vertical-align: middle; margin-right: 4px;"></span>
                    <?php esc_html_e( 'Minify', 'scoped-css' ); ?>
                </button>
                <button type="button" id="scoped-css-unminify-btn" class="button" title="<?php esc_attr_e( 'Format CSS', 'scoped-css' ); ?>">
                    <span class="dashicons dashicons-list-view" style="vertical-align: middle; margin-right: 4px;"></span>
                    <?php esc_html_e( 'Format', 'scoped-css' ); ?>
                </button>
                <span id="scoped-css-minify-message" style="display: none; color: #0a8619; font-weight: 500; align-content: center;"></span>
            </div>
            <textarea 
                id="scoped-css-editor" 
                name="scoped_css_content" 
                class="widefat" 
                rows="20"
            ><?php echo esc_textarea( $css_content ); ?></textarea>
            <p class="description">
                <?php esc_html_e( 'Enter your CSS code. The editor supports syntax highlighting and linting. Use Minify to reduce file size or Format to make it readable.', 'scoped-css' ); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Render output location meta box
     *
     * @param WP_Post $post Post object.
     */
    public function render_location_meta_box( $post ) {
        $location = get_post_meta( $post->ID, '_scoped_css_location', true );
        if ( empty( $location ) ) {
            $location = 'head';
        }

        $locations = array(
            'head'   => __( 'Head (wp_head)', 'scoped-css' ),
            'footer' => __( 'Footer (wp_footer)', 'scoped-css' ),
            'body'   => __( 'Body (wp_body_open)', 'scoped-css' ),
        );
        ?>
        <fieldset>
            <?php foreach ( $locations as $value => $label ) : ?>
                <label style="display: block; margin-bottom: 8px;">
                    <input 
                        type="radio" 
                        name="scoped_css_location" 
                        value="<?php echo esc_attr( $value ); ?>"
                        <?php checked( $location, $value ); ?>
                    >
                    <?php echo esc_html( $label ); ?>
                </label>
            <?php endforeach; ?>
        </fieldset>
        <p class="description" style="margin-top: 12px;">
            <?php esc_html_e( 'Choose where the CSS should be output. Head is recommended for most cases.', 'scoped-css' ); ?>
        </p>
        <?php
    }

    /**
     * Render conditions meta box
     *
     * @param WP_Post $post Post object.
     */
    public function render_conditions_meta_box( $post ) {
        $conditions = get_post_meta( $post->ID, '_scoped_css_conditions', true );
        
        // Default conditions
        if ( empty( $conditions ) || ! is_array( $conditions ) ) {
            $conditions = array(
                'match_type' => 'any',
                'rules' => array(
                    array( 'type' => 'entire_site', 'value' => '' )
                )
            );
        }

        $match_type = isset( $conditions['match_type'] ) ? $conditions['match_type'] : 'any';
        $rules = isset( $conditions['rules'] ) ? $conditions['rules'] : array();

        // Get post types for dropdown
        $post_types = get_post_types( array( 'public' => true ), 'objects' );
        ?>
        <div class="scoped-css-conditions-wrap" id="scoped-css-conditions">
            <div class="scoped-css-match-type" style="margin-bottom: 15px;">
                <label>
                    <strong><?php esc_html_e( 'Match:', 'scoped-css' ); ?></strong>
                </label>
                <select name="scoped_css_match_type" id="scoped-css-match-type">
                    <option value="any" <?php selected( $match_type, 'any' ); ?>>
                        <?php esc_html_e( 'ANY rule (OR)', 'scoped-css' ); ?>
                    </option>
                    <option value="all" <?php selected( $match_type, 'all' ); ?>>
                        <?php esc_html_e( 'ALL rules (AND)', 'scoped-css' ); ?>
                    </option>
                </select>
            </div>

            <div class="scoped-css-rules" id="scoped-css-rules">
                <?php 
                if ( ! empty( $rules ) ) :
                    foreach ( $rules as $index => $rule ) :
                        $this->render_condition_row( $index, $rule, $post_types );
                    endforeach;
                endif;
                ?>
            </div>

            <div class="scoped-css-add-rule" style="margin-top: 15px;">
                <button type="button" class="button" id="scoped-css-add-rule">
                    <span class="dashicons dashicons-plus-alt2" style="vertical-align: middle;"></span>
                    <?php esc_html_e( 'Add Condition', 'scoped-css' ); ?>
                </button>
            </div>

            <!-- Template for new rules (hidden) -->
            <script type="text/template" id="scoped-css-rule-template">
                <?php $this->render_condition_row( '{{INDEX}}', array( 'type' => 'entire_site', 'value' => '' ), $post_types, true ); ?>
            </script>

            <!-- Post types data for JS -->
            <script type="application/json" id="scoped-css-post-types">
                <?php 
                $pt_data = array();
                foreach ( $post_types as $pt ) {
                    $pt_data[ $pt->name ] = $pt->labels->singular_name;
                }
                echo wp_json_encode( $pt_data );
                ?>
            </script>
        </div>
        <?php
    }

    /**
     * Render a single condition row
     *
     * @param int|string $index Row index.
     * @param array      $rule  Rule data.
     * @param array      $post_types Available post types.
     * @param bool       $is_template Whether this is a JS template.
     */
    private function render_condition_row( $index, $rule, $post_types, $is_template = false ) {
        $type = isset( $rule['type'] ) ? $rule['type'] : 'entire_site';
        $value = isset( $rule['value'] ) ? $rule['value'] : '';

        $condition_types = array(
            'entire_site'  => __( 'Entire Site', 'scoped-css' ),
            'front_page'   => __( 'Front Page', 'scoped-css' ),
            'blog_page'    => __( 'Blog Page', 'scoped-css' ),
            '404'          => __( '404 Page', 'scoped-css' ),
            'page'         => __( 'Specific Page', 'scoped-css' ),
            'post'         => __( 'Specific Post', 'scoped-css' ),
            'post_type'    => __( 'Post Type', 'scoped-css' ),
            'url_exact'    => __( 'URL Exact Match', 'scoped-css' ),
            'url_contains' => __( 'URL Contains', 'scoped-css' ),
            'url_starts'   => __( 'URL Starts With', 'scoped-css' ),
        );

        // Determine which value fields to show
        $needs_value = in_array( $type, array( 'page', 'post', 'post_type', 'url_exact', 'url_contains', 'url_starts' ), true );
        $needs_search = in_array( $type, array( 'page', 'post' ), true );
        $needs_dropdown = $type === 'post_type';
        $needs_text = in_array( $type, array( 'url_exact', 'url_contains', 'url_starts' ), true );
        ?>
        <div class="scoped-css-rule" data-index="<?php echo esc_attr( $index ); ?>" style="background: #f9f9f9; padding: 12px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 3px;">
            <div style="display: flex; gap: 10px; align-items: flex-start; flex-wrap: wrap;">
                <div style="flex: 0 0 180px;">
                    <select 
                        name="scoped_css_rules[<?php echo esc_attr( $index ); ?>][type]" 
                        class="scoped-css-rule-type widefat"
                    >
                        <?php foreach ( $condition_types as $ctype => $label ) : ?>
                            <option value="<?php echo esc_attr( $ctype ); ?>" <?php selected( $type, $ctype ); ?>>
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="scoped-css-rule-value" style="flex: 1; min-width: 200px; <?php echo ! $needs_value ? 'display: none;' : ''; ?>">
                    <!-- Search input for pages/posts -->
                    <div class="scoped-css-search-wrap" style="<?php echo ! $needs_search ? 'display: none;' : ''; ?>">
                        <input 
                            type="text" 
                            class="scoped-css-search widefat" 
                            placeholder="<?php esc_attr_e( 'Search...', 'scoped-css' ); ?>"
                            autocomplete="off"
                            data-selected-id="<?php echo esc_attr( $value ); ?>"
                            data-selected-title="<?php 
                                if ( $needs_search && $value ) {
                                    echo esc_attr( get_the_title( $value ) );
                                }
                            ?>"
                            value="<?php 
                                if ( $needs_search && $value ) {
                                    echo esc_attr( get_the_title( $value ) );
                                }
                            ?>"
                            <?php echo ! $needs_search ? 'disabled' : ''; ?>
                        >
                        <input 
                            type="hidden" 
                            name="scoped_css_rules[<?php echo esc_attr( $index ); ?>][value]" 
                            class="scoped-css-search-value"
                            value="<?php echo esc_attr( $needs_search ? $value : '' ); ?>"
                        >
                        <div class="scoped-css-search-results" style="display: none; position: absolute; background: #fff; border: 1px solid #ddd; max-height: 200px; overflow-y: auto; z-index: 100; width: 100%;"></div>
                    </div>

                    <!-- Dropdown for post types -->
                    <select 
                        name="scoped_css_rules[<?php echo esc_attr( $index ); ?>][value]" 
                        class="scoped-css-post-type-select widefat"
                        style="<?php echo ! $needs_dropdown ? 'display: none;' : ''; ?>"
                        <?php echo ! $needs_dropdown ? 'disabled' : ''; ?>
                    >
                        <option value=""><?php esc_html_e( 'Select post type...', 'scoped-css' ); ?></option>
                        <?php foreach ( $post_types as $pt ) : ?>
                            <option value="<?php echo esc_attr( $pt->name ); ?>" <?php selected( $value, $pt->name ); ?>>
                                <?php echo esc_html( $pt->labels->singular_name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Text input for URLs -->
                    <input 
                        type="text" 
                        name="scoped_css_rules[<?php echo esc_attr( $index ); ?>][value]" 
                        class="scoped-css-url-input widefat"
                        placeholder="<?php esc_attr_e( '/path or full URL', 'scoped-css' ); ?>"
                        value="<?php echo esc_attr( $needs_text ? $value : '' ); ?>"
                        style="<?php echo ! $needs_text ? 'display: none;' : ''; ?>"
                        <?php echo ! $needs_text ? 'disabled' : ''; ?>
                    >
                </div>

                <div style="flex: 0 0 auto;">
                    <button type="button" class="button scoped-css-remove-rule" title="<?php esc_attr_e( 'Remove', 'scoped-css' ); ?>">
                        <span class="dashicons dashicons-trash" style="vertical-align: middle;"></span>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Save post meta
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     */
    public function save_post( int $post_id, WP_Post $post ): void {
        // Verify nonce
        if ( ! isset( $_POST['scoped_css_nonce'] ) || ! wp_verify_nonce( $_POST['scoped_css_nonce'], 'scoped_css_save' ) ) {
            return;
        }

        // Check capabilities
        if ( ! current_user_can( 'edit_scoped_css', $post_id ) ) {
            return;
        }

        // Check autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Log the change
        $user_id = get_current_user_id();

        // Validate CSS
        if ( isset( $_POST['scoped_css_content'] ) ) {
            $css_input = wp_unslash( $_POST['scoped_css_content'] );
            $validation = Scoped_CSS_Validator::validate_css( $css_input );

            if ( ! $validation['valid'] ) {
                // Log validation error
                error_log( sprintf(
                    '[Scoped CSS] Validation failed for rule %d user %d: %s',
                    $post_id,
                    $user_id,
                    $validation['message']
                ) );
                return;
            }

            $css_content = $validation['css'];
            
            // Update post content directly
            remove_action( 'save_post_scoped_css', array( $this, 'save_post' ), 10 );
            wp_update_post( array(
                'ID'           => $post_id,
                'post_content' => $css_content,
            ) );
            add_action( 'save_post_scoped_css', array( $this, 'save_post' ), 10, 2 );

            // Log CSS save
            error_log( sprintf(
                '[Scoped CSS] CSS updated for rule %d (ID %d) by user %d - %d bytes',
                $post_id,
                $post_id,
                $user_id,
                strlen( $css_content )
            ) );
        }

        // Save location
        if ( isset( $_POST['scoped_css_location'] ) ) {
            $location = sanitize_text_field( wp_unslash( $_POST['scoped_css_location'] ) );
            if ( in_array( $location, array( 'head', 'footer', 'body' ), true ) ) {
                update_post_meta( $post_id, '_scoped_css_location', $location );
            }
        }

        // Save conditions
        if ( isset( $_POST['scoped_css_match_type'] ) ) {
            $match_type = sanitize_text_field( wp_unslash( $_POST['scoped_css_match_type'] ) );
            if ( ! in_array( $match_type, array( 'any', 'all' ), true ) ) {
                $match_type = 'any';
            }

            $rules = array();
            if ( isset( $_POST['scoped_css_rules'] ) && is_array( $_POST['scoped_css_rules'] ) ) {
                foreach ( $_POST['scoped_css_rules'] as $rule ) {
                    if ( ! isset( $rule['type'] ) ) {
                        continue;
                    }

                    $clean_rule = array(
                        'type'  => sanitize_text_field( $rule['type'] ),
                        'value' => isset( $rule['value'] ) ? sanitize_text_field( $rule['value'] ) : '',
                    );

                    // Validate rule
                    if ( ! Scoped_CSS_Validator::validate_condition( $clean_rule ) ) {
                        error_log( sprintf(
                            '[Scoped CSS] Invalid condition for rule %d: %s',
                            $post_id,
                            wp_json_encode( $clean_rule )
                        ) );
                        continue;
                    }

                    $rules[] = $clean_rule;
                }
            }

            // Default to entire site if no rules
            if ( empty( $rules ) ) {
                $rules[] = array( 'type' => 'entire_site', 'value' => '' );
            }

            $conditions = array(
                'match_type' => $match_type,
                'rules'      => $rules,
            );

            update_post_meta( $post_id, '_scoped_css_conditions', $conditions );

            // Log conditions update
            error_log( sprintf(
                '[Scoped CSS] Conditions updated for rule rule %d (%d conditions, match type: %s)',
                $post_id,
                count( $rules ),
                $match_type
            ) );
        }

        // Clear cache
        Scoped_CSS_Loader::clear_cache();
    }

    /**
     * Custom admin columns
     *
     * @param array $columns Existing columns.
     * @return array
     */
    public function custom_columns( array $columns ): array {
        $new_columns = array();
        
        foreach ( $columns as $key => $value ) {
            if ( $key === 'title' ) {
                $new_columns[ $key ] = $value;
                $new_columns['scoped_css_status'] = __( 'Status', 'scoped-css' );
                $new_columns['scoped_css_location'] = __( 'Location', 'scoped-css' );
                $new_columns['scoped_css_conditions'] = __( 'Conditions', 'scoped-css' );
            } elseif ( $key !== 'date' ) {
                $new_columns[ $key ] = $value;
            }
        }
        
        $new_columns['date'] = __( 'Date', 'scoped-css' );
        
        return $new_columns;
    }

    /**
     * Column content
     *
     * @param string $column  Column name.
     * @param int    $post_id Post ID.
     */
    public function column_content( $column, $post_id ) {
        switch ( $column ) {
            case 'scoped_css_status':
                $status = get_post_status( $post_id );
                $is_active = $status === 'publish';
                $toggle_url = wp_nonce_url(
                    admin_url( 'admin-ajax.php?action=scoped_css_toggle_status&post_id=' . $post_id ),
                    'scoped_css_toggle_' . $post_id
                );
                ?>
                <a href="<?php echo esc_url( $toggle_url ); ?>" class="scoped-css-status-toggle" data-post-id="<?php echo esc_attr( $post_id ); ?>">
                    <span class="dashicons <?php echo $is_active ? 'dashicons-yes-alt' : 'dashicons-marker'; ?>" 
                          style="color: <?php echo $is_active ? '#46b450' : '#999'; ?>"></span>
                    <?php echo $is_active ? esc_html__( 'Active', 'scoped-css' ) : esc_html__( 'Inactive', 'scoped-css' ); ?>
                </a>
                <?php
                break;

            case 'scoped_css_location':
                $location = get_post_meta( $post_id, '_scoped_css_location', true );
                $locations = array(
                    'head'   => __( 'Head', 'scoped-css' ),
                    'footer' => __( 'Footer', 'scoped-css' ),
                    'body'   => __( 'Body', 'scoped-css' ),
                );
                echo esc_html( isset( $locations[ $location ] ) ? $locations[ $location ] : $locations['head'] );
                break;

            case 'scoped_css_conditions':
                $conditions = get_post_meta( $post_id, '_scoped_css_conditions', true );
                if ( empty( $conditions ) || empty( $conditions['rules'] ) ) {
                    echo '<em>' . esc_html__( 'Entire Site', 'scoped-css' ) . '</em>';
                } else {
                    $summaries = array();
                    foreach ( $conditions['rules'] as $rule ) {
                        $summaries[] = $this->get_condition_summary( $rule );
                    }
                    $connector = $conditions['match_type'] === 'all' ? ' + ' : ' / ';
                    echo esc_html( implode( $connector, array_slice( $summaries, 0, 3 ) ) );
                    if ( count( $summaries ) > 3 ) {
                        echo ' <em>+' . ( count( $summaries ) - 3 ) . '</em>';
                    }
                }
                break;
        }
    }

    /**
     * Get human-readable condition summary
     *
     * @param array $rule Condition rule.
     * @return string
     */
    private function get_condition_summary( $rule ) {
        $type = isset( $rule['type'] ) ? $rule['type'] : '';
        $value = isset( $rule['value'] ) ? $rule['value'] : '';

        switch ( $type ) {
            case 'entire_site':
                return __( 'Entire Site', 'scoped-css' );
            case 'front_page':
                return __( 'Front Page', 'scoped-css' );
            case 'blog_page':
                return __( 'Blog Page', 'scoped-css' );
            case '404':
                return __( '404', 'scoped-css' );
            case 'page':
            case 'post':
                $title = $value ? get_the_title( $value ) : '';
                return $title ? $title : ucfirst( $type ) . ' #' . $value;
            case 'post_type':
                $pt = get_post_type_object( $value );
                return $pt ? $pt->labels->singular_name : $value;
            case 'url_exact':
                return 'URL = ' . $value;
            case 'url_contains':
                return 'URL ~ ' . $value;
            case 'url_starts':
                return 'URL ^ ' . $value;
            default:
                return $type;
        }
    }

    /**
     * Modify row actions
     *
     * @param array   $actions Existing actions.
     * @param WP_Post $post    Post object.
     * @return array
     */
    public function row_actions( $actions, $post ) {
        if ( $post->post_type === 'scoped_css' ) {
            // Remove view action (not public)
            unset( $actions['view'] );
        }
        return $actions;
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page.
     */
    public function enqueue_assets( $hook ) {
        global $post_type;

        if ( $post_type !== 'scoped_css' ) {
            return;
        }

        // Admin CSS
        wp_enqueue_style(
            'scoped-css-admin',
            SCOPED_CSS_URL . 'assets/css/admin.css',
            array(),
            SCOPED_CSS_VERSION
        );

        // Admin JS
        wp_enqueue_script(
            'scoped-css-admin',
            SCOPED_CSS_URL . 'assets/js/admin.js',
            array( 'jquery', 'wp-util' ),
            SCOPED_CSS_VERSION,
            true
        );

        wp_localize_script( 'scoped-css-admin', 'scopedCssAdmin', array(
            'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
            'searchNonce' => wp_create_nonce( 'scoped_css_search' ),
            'minifyNonce' => wp_create_nonce( 'scoped_css_minify' ),
            'i18n'        => array(
                'noResults'  => __( 'No results found', 'scoped-css' ),
                'searching'  => __( 'Searching...', 'scoped-css' ),
                'error'      => __( 'An error occurred', 'scoped-css' ),
            ),
        ) );
    }

    /**
     * Custom title placeholder
     *
     * @param string  $placeholder Default placeholder.
     * @param WP_Post $post        Post object.
     * @return string
     */
    public function title_placeholder( $placeholder, $post ) {
        if ( $post->post_type === 'scoped_css' ) {
            return __( 'CSS Rule Name (for admin reference)', 'scoped-css' );
        }
        return $placeholder;
    }

    /**
     * AJAX handler for status toggle
     */
    public function ajax_toggle_status() {
        $post_id = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : 0;

        if ( ! $post_id || ! check_admin_referer( 'scoped_css_toggle_' . $post_id ) ) {
            wp_die( __( 'Invalid request', 'scoped-css' ) );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Permission denied', 'scoped-css' ) );
        }

        $post = get_post( $post_id );
        if ( ! $post || $post->post_type !== 'scoped_css' ) {
            wp_die( __( 'Invalid post', 'scoped-css' ) );
        }

        $new_status = $post->post_status === 'publish' ? 'draft' : 'publish';
        wp_update_post( array(
            'ID'          => $post_id,
            'post_status' => $new_status,
        ) );

        // Clear cache
        Scoped_CSS_Loader::clear_cache();

        // Redirect back to list
        wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=scoped_css' ) );
        exit;
    }
}
