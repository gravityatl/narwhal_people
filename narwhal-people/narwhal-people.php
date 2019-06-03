<?php
/**
 * @package Narwhal_People
 * @version 0.1
 */
/*
Plugin Name: Narwhal People
Description: 'People' custom post type with taxonomy and archive
Author: Michael Weil
Version: 0.1
Author URI: http://devdesignatl.site/
*/

/*
* function to create our custom post type People
*/

function create_post_type_people() {
	register_post_type( 'people',
		array(
			'labels'       => array(
				'name'       => __( 'People' ),
			),
			'public'       => true,
			'hierarchical' => true,
			'has_archive'  => true,
			'supports'     => array(
				'title',
				'editor',
				'custom-fields',
			)
		)
	);
}
add_action( 'init', 'create_post_type_people' );

// Create Taxonomy for Person Type
add_action( 'init', 'create_people_custom_taxonomy', 0 );
 
function create_people_custom_taxonomy() {
 
  $labels = array(
    'name' => _x( 'Person Types', 'taxonomy general name' ),
    'singular_name' => _x( 'Person Type', 'taxonomy singular name' ),
    'search_items' =>  __( 'Search Person Types' ),
    'all_items' => __( 'All Person Types' ),
    'parent_item' => __( 'Parent Person Type' ),
    'parent_item_colon' => __( 'Parent Person Type:' ),
    'edit_item' => __( 'Edit Person Type' ), 
    'update_item' => __( 'Update Person Type' ),
    'add_new_item' => __( 'Add New Person Type' ),
    'new_item_name' => __( 'New Person Type Name' ),
    'menu_name' => __( 'Person Type' ),
  ); 	
 
  register_taxonomy('person_type',array('people'), array(
    'hierarchical' => true,
    'labels' => $labels,
    'show_ui' => true,
    'show_admin_column' => true,
    'query_var' => true,
    'rewrite' => array( 'slug' => 'person-type' ),
  ));
}


// create shortcode with parameters so that the user can define what's queried - default is to list all blog posts
add_shortcode( 'list-people', 'people_listing_parameters_shortcode' );
function people_listing_parameters_shortcode( $atts ) {
    ob_start();
 
    // define attributes and their defaults
    extract( shortcode_atts( array (
        'type' => 'people',
        'order' => 'date',
        'orderby' => 'title',
        'posts' => 100,
        'person_type' => '',
    ), $atts ) );
 
    // define query parameters based on attributes
    $options = array(
        'post_type' => $type,
        'order' => $order,
        'orderby' => $orderby,
        'posts_per_page' => $posts,
        'person_type' => $person_type,
    );
    $query = new WP_Query( $options );
    // run the loop based on the query
    if ( $query->have_posts() ) { ?>
        <ul class="clothes-listing ">
            <?php while ( $query->have_posts() ) : $query->the_post(); ?>

            <li id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <?php $meta = get_post_meta( get_the_ID(),'people_fields', true ); ?>
                <img src="<?php echo $meta['image'] ?>" style="height: 15vh;">
                <?php the_title(); ?>&nbsp;
				<a href="<?php the_permalink(); ?>">full detail page</a>
            </li>
            <?php endwhile;
            wp_reset_postdata(); ?>
        </ul>
    <?php
        $myvariable = ob_get_clean();
        return $myvariable;
    }
}


/* Meta box for custom fields */
function add_people_fields_meta_box() {
	add_meta_box(
		'people_fields_meta_box', // $id
		'People', // $title
		'show_people_fields_meta_box', // $callback
		'people', // $screen
		'normal', // $context
		'high' // $priority
	);
}
add_action( 'add_meta_boxes', 'add_people_fields_meta_box' );

function show_people_fields_meta_box() {
    global $post;  
    $meta = get_post_meta( $post->ID, 'people_fields', true ); ?>

    <input type="hidden" name="people_meta_box_nonce" value="<?php echo wp_create_nonce( basename(__FILE__) ); ?>">

    <p>
        <label for="people_fields[text]">Job Title</label>
        <br>
        <input type="text" name="people_fields[text]" id="people_fields[text]" class="regular-text" value="<?php if (is_array($meta) && isset($meta['text'])) {	echo $meta['text']; } ?>">
    </p>

    <p>
        <label for="people_fields[image]">Headshot</label><br>
        <input type="text" name="people_fields[image]" id="people_fields[image]" class="meta-image regular-text" value="<?php echo $meta['image']; ?>">
        <input type="button" class="button image-upload" value="Browse">
    </p>

    <div class="image-preview"><img src="<?php echo $meta['image']; ?>" style="max-width: 250px;"></div>

    <script>
    jQuery(document).ready(function ($) {
        // Instantiates the variable that holds the media library frame.
        var meta_image_frame;
        // Runs when the image button is clicked.
        $('.image-upload').click(function (e) {
        // Get preview pane
        var meta_image_preview = $(this).parent().parent().children('.image-preview');
        // Prevents the default action from occuring.
        e.preventDefault();
        var meta_image = $(this).parent().children('.meta-image');
        // If the frame already exists, re-open it.
        if (meta_image_frame) {
            meta_image_frame.open();
            return;
        }
        // Sets up the media library frame
        meta_image_frame = wp.media.frames.meta_image_frame = wp.media({
            title: meta_image.title,
            button: {
            text: meta_image.button
            }
        });
        // Runs when an image is selected.
        meta_image_frame.on('select', function () {
            // Grabs the attachment selection and creates a JSON representation of the model.
            var media_attachment = meta_image_frame.state().get('selection').first().toJSON();
            // Sends the attachment URL to our custom image input field.
            meta_image.val(media_attachment.url);
            meta_image_preview.children('img').attr('src', media_attachment.url);
        });
        // Opens the media library frame.
        meta_image_frame.open();
        });
    });
    </script>

  <?php }

function save_people_fields_meta( $post_id ) {   
	// verify nonce
	if ( isset($_POST['people_meta_box_nonce']) 
			&& !wp_verify_nonce( $_POST['people_meta_box_nonce'], basename(__FILE__) ) ) {
			return $post_id; 
		}
	// check autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return $post_id;
	}
	// check permissions
	if (isset($_POST['post_type'])) { //Fix 2
        if ( 'page' === $_POST['post_type'] ) {
            if ( !current_user_can( 'edit_page', $post_id ) ) {
                return $post_id;
            } elseif ( !current_user_can( 'edit_post', $post_id ) ) {
                return $post_id;
            }  
        }
    }
	
	$old = get_post_meta( $post_id, 'people_fields', true );
		if (isset($_POST['people_fields'])) { //Fix 3
			$new = $_POST['people_fields'];
			if ( $new && $new !== $old ) {
				update_post_meta( $post_id, 'people_fields', $new );
			} elseif ( '' === $new && $old ) {
				delete_post_meta( $post_id, 'people_fields', $old );
			}
		}
}

add_action( 'save_post', 'save_people_fields_meta' );

/* use plugin templates  */
add_filter( 'single_template', 'wpsites_custom_post_type_template' );
function wpsites_custom_post_type_template($single_template) {
     global $post;

     if ($post->post_type == 'people' ) {
          $single_template = dirname( __FILE__ ) . '/templates/single-people.php';
     }
     return $single_template;
  
}

?>