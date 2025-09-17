<?php

if ( ! defined( 'ABSPath' ) ) {
    exit; // Exit if accessed directly.
}

class AIOPMS_CPT_REST {

    private $cpt_manager;

    public function __construct(AIOPMS_CPT_Manager $cpt_manager) {
        $this->cpt_manager = $cpt_manager;
    }

    public function register_rest_endpoints() {
        register_rest_route('aiopms/v1', '/cpts', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_cpts_rest_data'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ));
    }

    public function get_cpts_rest_data($request) {
        $dynamic_cpts = $this->cpt_manager->get_dynamic_cpts();
        $cpt_data = array();
        
        foreach ($dynamic_cpts as $post_type => $cpt_info) {
            $posts = get_posts(array(
                'post_type' => $post_type,
                'numberposts' => -1,
                'post_status' => 'any'
            ));
            
            $cpt_data[] = array(
                'post_type' => $post_type,
                'label' => $cpt_info['label'],
                'description' => $cpt_info['description'],
                'posts_count' => count($posts),
                'custom_fields' => $cpt_info['custom_fields'] ?? array(),
                'posts' => array_map(function($post) {
                    return array(
                        'id' => $post->ID,
                        'title' => $post->post_title,
                        'status' => $post->post_status,
                        'url' => get_permalink($post->ID)
                    );
                }, $posts)
            );
        }
        
        return rest_ensure_response($cpt_data);
    }
}
