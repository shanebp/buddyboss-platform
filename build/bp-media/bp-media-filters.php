<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'bp_media_album_after_save',                        'bp_media_update_media_privacy'                     );

// Activity
add_action( 'bp_after_activity_loop',                           'bp_media_add_theatre_template'                     );
add_action( 'bp_activity_entry_content',                        'bp_media_activity_entry'                           );
add_action( 'bp_activity_after_comment_content',                'bp_media_activity_comment_entry'                   );
add_action( 'bp_activity_posted_update',                        'bp_media_update_media_meta',               10, 3   );
add_action( 'bp_groups_posted_update',                          'bp_media_groups_update_media_meta',        10, 4   );
add_action( 'bp_activity_comment_posted',                       'bp_media_comments_update_media_meta',      10, 3   );
add_action( 'bp_activity_comment_posted_notification_skipped',  'bp_media_comments_update_media_meta',      10, 3   );
add_action( 'bp_activity_after_delete',                         'bp_media_delete_activity_media'                    );
add_filter( 'bp_get_activity_content_body',                     'bp_media_activity_embed_gif',              20, 2   );
add_action( 'bp_activity_after_comment_content',                'bp_media_comment_embed_gif',               20, 1   );
add_action( 'bp_activity_after_save',                           'bp_media_activity_save_gif_data',           2, 1   );

// Forums
add_action( 'bbp_template_after_single_topic',                  'bp_media_add_theatre_template'                     );
add_action( 'bbp_new_reply',                                    'bp_media_forums_new_post_media_save',     999      );
add_action( 'bbp_new_topic',                                    'bp_media_forums_new_post_media_save',     999      );
add_action( 'edit_post',                                        'bp_media_forums_new_post_media_save',     999      );
add_action( 'bbp_new_reply',                                    'bp_media_forums_save_gif_data',     999      );
add_action( 'bbp_new_topic',                                    'bp_media_forums_save_gif_data',     999      );
add_action( 'edit_post',                                        'bp_media_forums_save_gif_data',     999      );

add_filter( 'bbp_get_reply_content',                            'bp_media_forums_embed_attachments',       999, 2   );
add_filter( 'bbp_get_topic_content',                            'bp_media_forums_embed_attachments',       999, 2   );
add_filter( 'bbp_get_reply_content',                            'bp_media_forums_embed_gif',       999, 2   );
add_filter( 'bbp_get_topic_content',                            'bp_media_forums_embed_gif',       999, 2   );

// Messages
add_action( 'messages_message_sent',                            'bp_media_attach_media_to_message'                  );
add_action( 'messages_message_sent',                            'bp_media_messages_save_gif_data'                   );
add_action( 'bp_messages_thread_after_delete',                  'bp_media_messages_delete_attached_media',  10,  2  );

// Core tools
add_filter( 'bp_core_get_tools_settings_admin_tabs', 'bp_media_get_tools_media_settings_admin_tabs', 20, 1 );
add_action( 'bp_core_activation_notice', 'bp_media_activation_notice' );
add_action( 'wp_ajax_bp_media_import_status_request', 'bp_media_import_status_request' );

/**
 * Add media theatre template for activity pages
 */
function bp_media_add_theatre_template() {
	bp_get_template_part( 'media/theatre' );
}

/**
 * Get activity entry media to render on front end
 */
function bp_media_activity_entry() {
	global $media_template;
	$media_ids = bp_activity_get_meta( bp_get_activity_id(), 'bp_media_ids', true );

	if ( ! empty( $media_ids ) && bp_has_media( array( 'include' => $media_ids, 'order_by' => 'menu_order', 'sort' => 'ASC' ) ) ) { ?>
		<div class="bb-activity-media-wrap <?php echo 'bb-media-length-' . $media_template->media_count; echo $media_template->media_count > 5 ? ' bb-media-length-more' : ''; ?>"><?php
		while ( bp_media() ) {
			bp_the_media();
			bp_get_template_part( 'media/activity-entry' );
		} ?>
		</div><?php
	}
}

/**
 * Get activity comment entry media to render on front end
 */
function bp_media_activity_comment_entry( $comment_id ) {
	global $media_template;
	$media_ids = bp_activity_get_meta( $comment_id, 'bp_media_ids', true );

	if ( ! empty( $media_ids ) && bp_has_media( array( 'include' => $media_ids, 'order_by' => 'menu_order', 'sort' => 'ASC' ) ) ) { ?>
		<div class="bb-activity-media-wrap <?php echo 'bb-media-length-' . $media_template->media_count; echo $media_template->media_count > 5 ? ' bb-media-length-more' : ''; ?>"><?php
		while ( bp_media() ) {
			bp_the_media();
			bp_get_template_part( 'media/activity-entry' );
		} ?>
		</div><?php
	}
}

/**
 * Update media for activity
 *
 * @param $content
 * @param $user_id
 * @param $activity_id
 *
 * @since BuddyBoss 1.0.0
 *
 * @return bool
 */
function bp_media_update_media_meta( $content, $user_id, $activity_id ) {

	if ( ! isset( $_POST['media'] ) || empty( $_POST['media'] ) ) {
		return false;
	}

	$media_list = $_POST['media'];

	if ( ! empty( $media_list ) ) {
		$media_ids = array();
		foreach ( $media_list as $media_index => $media ) {

			// remove actions to avoid infinity loop
			remove_action( 'bp_activity_posted_update', 'bp_media_update_media_meta', 10, 3 );
			remove_action( 'bp_groups_posted_update', 'bp_media_groups_update_media_meta', 10, 4 );

			// make an activity for the media
			$a_id = bp_activity_post_update( array( 'hide_sitewide' => true, 'privacy' => 'media' ) );

			if ( $a_id ) {
				// update activity meta
				bp_activity_update_meta( $a_id, 'bp_media_activity', '1' );
			}

			add_action( 'bp_activity_posted_update', 'bp_media_update_media_meta', 10, 3 );
			add_action( 'bp_groups_posted_update', 'bp_media_groups_update_media_meta', 10, 4 );

			$title         = ! empty( $media['name'] ) ? $media['name'] : '&nbsp;';
			$album_id      = ! empty( $media['album_id'] ) ? $media['album_id'] : 0;
			$privacy       = ! empty( $media['privacy'] ) ? $media['privacy'] : 'public';
			$attachment_id = ! empty( $media['id'] ) ? $media['id'] : 0;
			$menu_order    = ! empty( $media['menu_order'] ) ? $media['menu_order'] : $media_index;

			$media_id = bp_media_add(
				array(
					'title'         => $title,
					'album_id'      => $album_id,
					'activity_id'   => $a_id,
					'privacy'       => $privacy,
					'attachment_id' => $attachment_id,
					'menu_order'    => $menu_order,
				)
			);

			if ( $media_id ) {
				$media_ids[] = $media_id;

				//save media is saved in attahchment
				update_post_meta( $attachment_id, 'bp_media_saved', true );

				//save media meta for activity
				if ( ! empty( $activity_id ) && ! empty( $attachment_id ) ) {
					update_post_meta( $attachment_id, 'bp_media_parent_activity_id', $activity_id );
					update_post_meta( $attachment_id, 'bp_media_activity_id', $a_id );
				}
			}
		}

		$media_ids = implode( ',', $media_ids );

		//save media meta for activity
		if ( ! empty( $activity_id ) ) {
			bp_activity_update_meta( $activity_id, 'bp_media_ids', $media_ids );
		}
	}
}

/**
 * Update media for group activity
 *
 * @param $content
 * @param $user_id
 * @param $group_id
 * @param $activity_id
 *
 * @since BuddyBoss 1.0.0
 *
 * @return bool
 */
function bp_media_groups_update_media_meta( $content, $user_id, $group_id, $activity_id ) {
	bp_media_update_media_meta( $content, $user_id, $activity_id );
}

/**
 * Update media for activity comment
 *
 * @param $comment_id
 * @param $r
 * @param $activity
 *
 * @since BuddyBoss 1.0.0
 *
 * @return bool
 */
function bp_media_comments_update_media_meta( $comment_id, $r, $activity ) {
	bp_media_update_media_meta( false, false, $comment_id );
}

/**
 * Delete media when related activity is deleted.
 *
 * @since BuddyBoss 1.0.0
 * @param $activities
 */
function bp_media_delete_activity_media( $activities ) {
	if ( ! empty( $activities ) ) {
		remove_action( 'bp_activity_after_delete', 'bp_media_delete_activity_media' );
		foreach ( $activities as $activity ) {
			$activity_id = $activity->id;
			$media_activity = bp_activity_get_meta( $activity_id, 'bp_media_activity', true );
			if ( ! empty( $media_activity ) && '1' == $media_activity ) {
				$result = bp_media_get( array( 'activity_id' => $activity_id, 'fields' => 'ids' ) );
				if ( ! empty( $result['medias'] ) ) {
					foreach( $result['medias'] as $media_id ) {
						bp_media_delete( $media_id ); // delete media
					}
				}
			}
		}
		add_action( 'bp_activity_after_delete', 'bp_media_delete_activity_media' );
	}
}

/**
 * Update media privacy according to album's privacy
 *
 * @since BuddyBoss 1.0.0
 * @param $album
 */
function bp_media_update_media_privacy( &$album ) {

	if ( ! empty( $album->id ) ) {

		$privacy      = $album->privacy;
		$media_ids    = BP_Media::get_album_media_ids( $album->id );
		$activity_ids = array();

		if ( ! empty( $media_ids ) ) {
			foreach( $media_ids as $media ) {
				$media_obj          = new BP_Media( $media );
				$media_obj->privacy = $privacy;
				$media_obj->save();

				$attachment_id = $media_obj->attachment_id;
				$main_activity_id = get_post_meta( $attachment_id, 'bp_media_parent_activity_id', true );

				if ( ! empty( $main_activity_id ) ) {
					$activity_ids[] = $main_activity_id;
				}
			}
		}

		if ( ! empty( $activity_ids ) ) {
		    foreach ( $activity_ids as $activity_id ) {
		        $activity = new BP_Activity_Activity( $activity_id );

		        if ( ! empty( $activity ) ) {
			        $activity->privacy = $privacy;
			        $activity->save();
		        }
            }
        }
	}
}

/**
 * Save media when new topic or reply is saved
 *
 * @since BuddyBoss 1.0.0
 * @param $post_id
 */
function bp_media_forums_new_post_media_save( $post_id ) {

	if ( ! empty( $_POST['bbp_media'] ) ) {

		// save activity id if it is saved in forums and enabled in platform settings
		$main_activity_id = get_post_meta( $post_id, '_bbp_activity_id', true );

		// save media
		$medias = json_decode( stripslashes( $_POST['bbp_media'] ), true );

		//fetch currently uploaded media ids
		$existing_media                = [];
		$existing_media_ids            = get_post_meta( $post_id, 'bp_media_ids', true );
		$existing_media_attachment_ids = array();
		if ( ! empty( $existing_media_ids ) ) {
			$existing_media_ids = explode( ',', $existing_media_ids );

			foreach ( $existing_media_ids as $existing_media_id ) {
				$existing_media[ $existing_media_id ] = new BP_Media( $existing_media_id );

				if ( ! empty( $existing_media[ $existing_media_id ]->attachment_id ) ) {
					$existing_media_attachment_ids[] = $existing_media[ $existing_media_id ]->attachment_id;
				}
			}
		}

		$media_ids = array();
		foreach ( $medias as $media ) {

			$title             = ! empty( $media['name'] ) ? $media['name'] : '';
			$attachment_id     = ! empty( $media['id'] ) ? $media['id'] : 0;
			$attached_media_id = ! empty( $media['media_id'] ) ? $media['media_id'] : 0;
			$album_id          = ! empty( $media['album_id'] ) ? $media['album_id'] : 0;
			$group_id          = ! empty( $media['group_id'] ) ? $media['group_id'] : 0;
			$menu_order        = ! empty( $media['menu_order'] ) ? $media['menu_order'] : 0;

			if ( ! empty( $existing_media_attachment_ids ) ) {
				$index = array_search( $attachment_id, $existing_media_attachment_ids );
				if ( ! empty( $attachment_id ) && $index !== false && ! empty( $existing_media[ $attached_media_id ] ) ) {

					$existing_media[ $attached_media_id ]->menu_order = $menu_order;
					$existing_media[ $attached_media_id ]->save();

					unset( $existing_media_ids[ $index ] );
					$media_ids[] = $attached_media_id;
					continue;
				}
			}

			$media_id = bp_media_add( array(
				'attachment_id' => $attachment_id,
				'title'         => $title,
				'album_id'      => $album_id,
				'group_id'      => $group_id,
				'error_type'    => 'wp_error'
			) );

			if ( ! is_wp_error( $media_id ) ) {
				$media_ids[] = $media_id;

				//save media is saved in attachment
				update_post_meta( $attachment_id, 'bp_media_saved', true );
			}
		}

		$media_ids = implode( ',', $media_ids );

		//Save all attachment ids in forums post meta
		update_post_meta( $post_id, 'bp_media_ids', $media_ids );

		//save media meta for activity
		if ( ! empty( $main_activity_id ) && bp_is_active( 'activity' ) ) {
			bp_activity_update_meta( $main_activity_id, 'bp_media_ids', $media_ids );
		}

		// delete medias which were not saved or removed from form
		if ( ! empty( $existing_media_ids ) ) {
            foreach ( $existing_media_ids as $media_id ) {
                bp_media_delete( $media_id );
            }
		}
	}
}

/**
 * Embed topic or reply attachments in a post
 *
 * @since BuddyBoss 1.0.0
 * @param $content
 * @param $id
 *
 * @return string
 */
function bp_media_forums_embed_attachments( $content, $id ) {
	global $media_template;

	// Do not embed attachment in wp-admin area
	if ( is_admin() ) {
		return $content;
	}

	$media_ids = get_post_meta( $id, 'bp_media_ids', true );

	if ( ! empty( $media_ids ) && bp_has_media( array( 'include' => $media_ids, 'order_by' => 'menu_order', 'sort' => 'ASC' ) ) ) {
		ob_start();
		?>
        <div class="bb-activity-media-wrap forums-media-wrap <?php echo 'bb-media-length-' . $media_template->media_count; echo $media_template->media_count > 5 ? ' bb-media-length-more' : ''; ?>"><?php
		while ( bp_media() ) {
			bp_the_media();
			bp_get_template_part( 'media/activity-entry' );
		} ?>
        </div><?php
		$content .= ob_get_clean();
	}

	return $content;
}

/**
 * Embed topic or reply gif in a post
 *
 * @since BuddyBoss 1.0.0
 * @param $content
 * @param $id
 *
 * @return string
 */
function bp_media_forums_embed_gif( $content, $id ) {
	$gif_data = get_post_meta( $id, '_gif_data', true );

	if ( empty( $gif_data ) ) {
		return $content;
	}

	$preview_url = wp_get_attachment_url( $gif_data['still'] );
	$video_url = wp_get_attachment_url( $gif_data['mp4'] );

	ob_start();
	?>
    <div class="activity-attached-gif-container">
        <div class="gif-image-container">
            <div class="gif-player">
                <video preload="auto" playsinline poster="<?php echo $preview_url ?>" loop muted playsinline>
                    <source src="<?php echo $video_url ?>" type="video/mp4">
                </video>
                <a href="#" class="gif-play-button">
                    <span class="dashicons dashicons-video-alt3"></span>
                </a>
                <span class="gif-icon"></span>
            </div>
        </div>
    </div>
	<?php
	$content .= ob_get_clean();

	return $content;
}

/**
 * save gif data for forum, topic, reply
 *
 * @since BuddyBoss 1.0.0
 * @param $post_id
 */
function bp_media_forums_save_gif_data( $post_id ) {

	if ( ! bp_is_forums_gif_support_enabled() ) {
		return;
	}

	if ( ! empty( $_POST['bbp_media_gif'] ) ) {

		// save activity id if it is saved in forums and enabled in platform settings
		$main_activity_id = get_post_meta( $post_id, '_bbp_activity_id', true );

	    // save gif data
		$gif_data = json_decode( stripslashes( $_POST['bbp_media_gif'] ), true );

		if ( ! empty( $gif_data['saved'] ) && $gif_data['saved'] ) {
			return;
		}

		$still = bp_media_sideload_attachment( $gif_data['images']['480w_still']['url'] );
		$mp4   = bp_media_sideload_attachment( $gif_data['images']['original_mp4']['mp4'] );

		$gdata = array(
			'still' => $still,
			'mp4'   => $mp4,
        );

		update_post_meta( $post_id, '_gif_data', $gdata );

		$gif_data['saved'] = true;

		update_post_meta( $post_id, '_gif_raw_data', $gif_data );

		//save media meta for forum
		if ( ! empty( $main_activity_id ) && bp_is_active( 'activity' ) ) {
			bp_activity_update_meta( $main_activity_id, '_gif_data', $gdata );
			bp_activity_update_meta( $main_activity_id, '_gif_raw_data', $gif_data );
		}

	} else {
	    delete_post_meta( $post_id, '_gif_data' );
	    delete_post_meta( $post_id, '_gif_raw_data' );
	}
}

/**
 * Attach media to the message object
 *
 * @since BuddyBoss 1.0.0
 * @param $message
 */
function bp_media_attach_media_to_message( &$message ) {

	if ( bp_is_messages_media_support_enabled() && ! empty( $message->id ) && ! empty( $_POST['media'] ) ) {
		$media_list = $_POST['media'];
		$media_ids = array();

		foreach ( $media_list as $media_index => $media ) {
			$title         = ! empty( $media['name'] ) ? $media['name'] : '&nbsp;';
			$attachment_id = ! empty( $media['id'] ) ? $media['id'] : 0;

			$media_id = bp_media_add(
				array(
					'title'         => $title,
					'privacy'       => 'message',
					'attachment_id' => $attachment_id,
				)
			);

			if ( $media_id ) {
				$media_ids[] = $media_id;

				//save media is saved in attachment
				update_post_meta( $attachment_id, 'bp_media_saved', true );
			}
		}

		$media_ids = implode( ',', $media_ids );

		//save media meta for message
		bp_messages_update_meta( $message->id, 'bp_media_ids', $media_ids );
	}
}

/**
 * Delete media attached to messages
 *
 * @since BuddyBoss 1.0.0
 * @param $thread_id
 * @param $message_ids
 */
function bp_media_messages_delete_attached_media( $thread_id, $message_ids ) {

    if ( ! empty( $message_ids ) ) {
        foreach( $message_ids as $message_id ) {

            // get media ids attached to message
	        $media_ids = bp_messages_get_meta( $message_id, 'bp_media_ids', true );

	        if ( ! empty( $media_ids ) ) {
		        $media_ids = explode( ',', $media_ids );
                foreach( $media_ids as $media_id ) {
                    bp_media_delete( $media_id );
                }
            }
        }
    }
}

/**
 * Save gif data into messages meta key "_gif_data"
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $message
 */
function bp_media_messages_save_gif_data( &$message ) {

	if ( ! bp_is_messages_gif_support_enabled() || empty( $_POST['gif_data'] ) ) {
		return;
	}

	$gif_data =  $_POST['gif_data'];

	$still = bp_media_sideload_attachment( $gif_data['images']['480w_still']['url'] );
	$mp4 = bp_media_sideload_attachment( $gif_data['images']['original_mp4']['mp4'] );

	bp_messages_update_meta( $message->id, '_gif_data', [
		'still' => $still,
		'mp4'   => $mp4,
	] );

	bp_messages_update_meta( $message->id, '_gif_raw_data', $gif_data );
}

/**
 * Return activity gif embed HTML
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $activity_id
 *
 * @return false|string|void
 */
function bp_media_activity_embed_gif_content( $activity_id ) {

	$gif_data = bp_activity_get_meta( $activity_id, '_gif_data', true );

	if ( empty( $gif_data ) ) {
		return;
	}

	$preview_url = wp_get_attachment_url( $gif_data['still'] );
	$video_url = wp_get_attachment_url( $gif_data['mp4'] );

	ob_start();
	?>
    <div class="activity-attached-gif-container">
        <div class="gif-image-container">
            <div class="gif-player">
                <video preload="auto" playsinline poster="<?php echo $preview_url ?>" loop muted playsinline>
                    <source src="<?php echo $video_url ?>" type="video/mp4">
                </video>
                <a href="#" class="gif-play-button">
                    <span class="dashicons dashicons-video-alt3"></span>
                </a>
                <span class="gif-icon"></span>
            </div>
        </div>
    </div>
	<?php
	$content = ob_get_clean();

	return $content;
}

/**
 * Embed gif in activity content
 *
 * @param $content
 * @param $activity
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string
 */
function bp_media_activity_embed_gif( $content, $activity ) {

	$gif_content = bp_media_activity_embed_gif_content(  $activity->id );

	if ( ! empty( $gif_content ) ) {
		$content .= $gif_content;
	}

	return $content;
}

/**
 * Embed gif in activity comment content
 *
 * @param $content
 * @param $activity
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string
 */
function bp_media_comment_embed_gif( $activity_id ) {

	$gif_content = bp_media_activity_embed_gif_content(  $activity_id );

	if ( ! empty( $gif_content ) ) {
		echo $gif_content;
	}
}

/**
 * Save gif data into activity meta key "_gif_attachment_id"
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $activity
 */
function bp_media_activity_save_gif_data( $activity ) {

	if ( empty( $_POST['gif_data'] ) ) {
		return;
	}

	$gif_data =  $_POST['gif_data'];

	$still = bp_media_sideload_attachment( $gif_data['images']['480w_still']['url'] );
	$mp4 = bp_media_sideload_attachment( $gif_data['images']['original_mp4']['mp4'] );

	bp_activity_update_meta( $activity->id, '_gif_data', [
		'still' => $still,
		'mp4'   => $mp4,
	] );

	bp_activity_update_meta( $activity->id, '_gif_raw_data', $gif_data );
}

function bp_media_get_tools_media_settings_admin_tabs( $tabs ) {

	$tabs[] = array(
		'href' => get_admin_url( '', add_query_arg( array( 'page' => 'bp-media-import', 'tab' => 'bp-media-import' ), 'admin.php' ) ),
		'name' => __( 'Import Media', 'buddyboss' ),
		'slug' => 'bp-media-import'
	);

	return $tabs;
}

/**
 * Add Import Media admin menu in tools
 *
 * @since BuddyPress 3.0.0
 */
function bp_media_import_admin_menu() {

	add_submenu_page(
		'buddyboss-platform',
		__( 'Import Media', 'buddyboss' ),
		__( 'Import Media', 'buddyboss' ),
		'manage_options',
		'bp-media-import',
		'bp_media_import_submenu_page'
	);

}
add_action( bp_core_admin_hook(), 'bp_media_import_admin_menu' );

/**
 * Import Media menu page
 *
 * @since BuddyBoss 1.0.0
 *
 */
function bp_media_import_submenu_page() {
	global $wpdb, $background_updater;

	$bp_media_import_status = get_option( 'bp_media_import_status' );

	if ( isset( $_POST['bp-media-import-submit'] ) && ! empty( $background_updater ) ) {
		$update_queued          = false;

		if ( 'done' != $bp_media_import_status ) {
			foreach ( bp_media_get_import_callbacks() as $update_callback ) {
				error_log( sprintf( 'Queuing %s', $update_callback ) );
				$background_updater->push_to_queue( $update_callback );
				$update_queued = true;
			}
		}

		if ( $update_queued ) {
			$background_updater->save()->dispatch();
		}
	}

	$check                        = false;
	$buddyboss_media_table        = $wpdb->prefix . 'buddyboss_media';
	$buddyboss_media_albums_table = $wpdb->prefix . 'buddyboss_media_albums';
	if ( empty( $wpdb->get_results( "SHOW TABLES LIKE '{$buddyboss_media_table}' ;" ) ) || empty( $wpdb->get_results( "SHOW TABLES LIKE '{$buddyboss_media_albums_table}' ;" ) ) ) {
		$check = true;
	}

	?>
    <div class="wrap">
        <h2 class="nav-tab-wrapper"><?php bp_core_admin_tabs( __( 'Tools', 'buddyboss' ) ); ?></h2>
        <div class="nav-settings-subsubsub">
            <ul class="subsubsub">
				<?php bp_core_tools_settings_admin_tabs(); ?>
            </ul>
        </div>
    </div>
    <div class="wrap">
        <div class="bp-admin-card section-bp-member-type-import">
            <div class="boss-import-area">
                <form id="bp-member-type-import-form" method="post" action="">
                    <div class="import-panel-content">
                        <h2><?php _e( 'Import Media', 'buddyboss' ); ?></h2>

						<?php if ( $check ) {
							?>
                            <p><?php _e( 'BuddyBoss Media plugin database tables do not exist, meaning you have nothing to import.', 'buddyboss' ); ?></p>
							<?php
						} else if ( ! empty( $background_updater ) && $background_updater->is_updating() ) {
							$total_media   = get_option( 'bp_media_import_total_media', 0 );
							$total_albums  = get_option( 'bp_media_import_total_albums', 0 );
							$albums_done   = get_option( 'bp_media_import_albums_done', 0 );
							$media_done    = get_option( 'bp_media_import_media_done', 0 );
							?>
                            <p>
								<?php esc_html_e( 'Your database is being updated in the background.', 'buddyboss' ); ?>
                            </p>
                            <table>
                                <tr>
                                    <td><h4><?php _e( 'Albums', 'buddyboss' ); ?></h4></td>
                                    <td><span id="bp-media-import-albums-done"><?php echo $albums_done; ?></span> <?php _e( 'out of', 'buddyboss' ); ?> <span id="bp-media-import-albums-total"><?php echo $total_albums; ?></span></td>
                                </tr>
                                <tr>
                                    <td><h4><?php _e( 'Media', 'buddyboss' ); ?></h4></td>
                                    <td><span id="bp-media-import-media-done"><?php echo $media_done; ?></span> <?php _e( 'out of', 'buddyboss' ); ?> <span id="bp-media-import-media-total"><?php echo $total_media; ?></span></td>
                                </tr>
                            </table>
                            <p>
								<label id="bp-media-import-msg"></label>
                            </p>
                            <input type="hidden" value="bp-media-import-updating" id="bp-media-import-updating" />
							<?php
						} else if ( 'done' == $bp_media_import_status ) {
							?>
                            <p><?php _e( 'BuddyBoss Media data update is complete! Any previously uploaded member photos should display in their profiles now.', 'buddyboss' ); ?></p>
							<?php
						} else { ?>
                            <p><?php _e( 'Import your existing members photo uploads, if you were previously using <a href="https://www.buddyboss.com/product/buddyboss-media/">BuddyBoss Media</a> with BuddyPress. Click "Run Migration" below to migrate your old photos into the new Media component.', 'buddyboss' ); ?></p>
                            <input type="submit" value="<?php _e('Run Migration', 'buddyboss'); ?>" id="bp-media-import-submit" name="bp-media-import-submit" class="button-primary"/>
						<?php } ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <br />

	<?php
}

/**
 *
 *
 * @since BuddyBoss 1.0.0
 * @return array
 */
function bp_media_get_import_callbacks() {
	return array(
		'bp_media_import_reset_options',
		//'bp_media_import_reset_media_albums',
		//'bp_media_import_reset_media',
		'bp_media_import_buddyboss_media_tables',
		'bp_media_import_buddyboss_forum_media',
		'bp_media_import_buddyboss_topic_media',
		'bp_media_import_buddyboss_reply_media',
		'bp_media_update_import_status',
	);
}

/**
 * Hook to display admin notices when media component is active
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_activation_notice() {
	global $wpdb;

	if ( ! empty( $_GET['page'] ) && 'bp-media-import' == $_GET['page'] ) {
		return;
	}

	$bp_media_import_status = get_option( 'bp_media_import_status' );

	if ( 'done' != $bp_media_import_status ) {

		$buddyboss_media_table        = $wpdb->prefix . 'buddyboss_media';
		$buddyboss_media_albums_table = $wpdb->prefix . 'buddyboss_media_albums';

		if ( ! empty( $wpdb->get_results( "SHOW TABLES LIKE '{$buddyboss_media_table}' ;" ) ) && ! empty( $wpdb->get_results( "SHOW TABLES LIKE '{$buddyboss_media_albums_table}' ;" ) ) ) {

			$admin_url = bp_get_admin_url( add_query_arg( array(
				'page' => 'bp-media-import',
				'tab'  => 'bp-media-import'
			), 'admin.php' ) );
			$notice    = sprintf( '%1$s <a href="%2$s">%3$s</a>',
				__( 'We have found some media uploaded from the <strong>BuddyBoss Media</strong></strong> plugin, which is not compatible with BuddyBoss Platform as it has its own media component. You should  import the media into BuddyBoss Platform, and then remove the BuddyBoss Media plugin if you are still using it.', 'buddyboss' ),
				esc_url( $admin_url ),
				__( 'Import Media', 'buddyboss' ) );

			bp_core_add_admin_notice( $notice );
		}
	}
}

/**
 * AJAX function for media import status
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_import_status_request() {
	$import_status = get_option( 'bp_media_import_status' );
	$total_media   = get_option( 'bp_media_import_total_media', 0 );
	$total_albums  = get_option( 'bp_media_import_total_albums', 0 );
	$albums_done   = get_option( 'bp_media_import_albums_done', 0 );
	$media_done    = get_option( 'bp_media_import_media_done', 0 );

	wp_send_json_success( array(
		'total_media'   => $total_media,
		'total_albums'  => $total_albums,
		'albums_done'   => $albums_done,
		'media_done'    => $media_done,
		'import_status' => $import_status,
		'success_msg'   => __( 'BuddyBoss Media data update is complete! Any previously uploaded member photos should display in their profiles now.', 'buddyboss' ),
		'error_msg'     => __( 'BuddyBoss Media data update is failing!', 'buddyboss' ),
	) );
}