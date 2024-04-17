<?php
 function render_block_core_image( $attributes, $content, $block ) { if ( false === stripos( $content, '<img' ) ) { return ''; } $p = new WP_HTML_Tag_Processor( $content ); if ( ! $p->next_tag( 'img' ) || null === $p->get_attribute( 'src' ) ) { return ''; } if ( isset( $attributes['data-id'] ) ) { $p->set_attribute( 'data-id', $attributes['data-id'] ); } $link_destination = isset( $attributes['linkDestination'] ) ? $attributes['linkDestination'] : 'none'; $lightbox_settings = block_core_image_get_lightbox_settings( $block->parsed_block ); if ( isset( $lightbox_settings ) && 'none' === $link_destination && isset( $lightbox_settings['enabled'] ) && true === $lightbox_settings['enabled'] ) { $suffix = wp_scripts_get_suffix(); if ( defined( 'IS_GUTENBERG_PLUGIN' ) && IS_GUTENBERG_PLUGIN ) { $module_url = gutenberg_url( '/build/interactivity/image.min.js' ); } wp_register_script_module( '@wordpress/block-library/image', isset( $module_url ) ? $module_url : includes_url( "blocks/image/view{$suffix}.js" ), array( '@wordpress/interactivity' ), defined( 'GUTENBERG_VERSION' ) ? GUTENBERG_VERSION : get_bloginfo( 'version' ) ); wp_enqueue_script_module( '@wordpress/block-library/image' ); add_filter( 'render_block_core/image', 'block_core_image_render_lightbox', 15, 2 ); } else { remove_filter( 'render_block_core/image', 'block_core_image_render_lightbox', 15 ); } return $p->get_updated_html(); } function block_core_image_get_lightbox_settings( $block ) { if ( isset( $block['attrs']['lightbox'] ) ) { $lightbox_settings = $block['attrs']['lightbox']; } if ( ! isset( $lightbox_settings ) ) { $lightbox_settings = wp_get_global_settings( array( 'lightbox' ), array( 'block_name' => 'core/image' ) ); if ( isset( $lightbox_settings['lightbox'] ) ) { $lightbox_settings = wp_get_global_settings( array( 'lightbox' ) ); } } return $lightbox_settings ?? null; } function block_core_image_render_lightbox( $block_content, $block ) { $p = new WP_HTML_Tag_Processor( $block_content ); if ( $p->next_tag( 'figure' ) ) { $p->set_bookmark( 'figure' ); } if ( ! $p->next_tag( 'img' ) ) { return $block_content; } $alt = $p->get_attribute( 'alt' ); $img_uploaded_src = $p->get_attribute( 'src' ); $img_class_names = $p->get_attribute( 'class' ); $img_styles = $p->get_attribute( 'style' ); $img_width = 'none'; $img_height = 'none'; $aria_label = __( 'Enlarge image' ); if ( $alt ) { $aria_label = sprintf( __( 'Enlarge image: %s' ), $alt ); } if ( isset( $block['attrs']['id'] ) ) { $img_uploaded_src = wp_get_attachment_url( $block['attrs']['id'] ); $img_metadata = wp_get_attachment_metadata( $block['attrs']['id'] ); $img_width = $img_metadata['width'] ?? 'none'; $img_height = $img_metadata['height'] ?? 'none'; } $p->seek( 'figure' ); $figure_class_names = $p->get_attribute( 'class' ); $figure_styles = $p->get_attribute( 'style' ); $p->add_class( 'wp-lightbox-container' ); $p->set_attribute( 'data-wp-interactive', 'core/image' ); $p->set_attribute( 'data-wp-context', wp_json_encode( array( 'uploadedSrc' => $img_uploaded_src, 'figureClassNames' => $figure_class_names, 'figureStyles' => $figure_styles, 'imgClassNames' => $img_class_names, 'imgStyles' => $img_styles, 'targetWidth' => $img_width, 'targetHeight' => $img_height, 'scaleAttr' => $block['attrs']['scale'] ?? false, 'ariaLabel' => $aria_label, 'alt' => $alt, ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ) ); $p->next_tag( 'img' ); $p->set_attribute( 'data-wp-init', 'callbacks.setButtonStyles' ); $p->set_attribute( 'data-wp-on--load', 'callbacks.setButtonStyles' ); $p->set_attribute( 'data-wp-on-window--resize', 'callbacks.setButtonStyles' ); $p->set_attribute( 'data-wp-on--click', 'actions.showLightbox' ); $body_content = $p->get_updated_html(); $img = null; preg_match( '/<img[^>]+>/', $body_content, $img ); $button = $img[0] . '<button
			class="lightbox-trigger"
			type="button"
			aria-haspopup="dialog"
			aria-label="' . esc_attr( $aria_label ) . '"
			data-wp-init="callbacks.initTriggerButton"
			data-wp-on--click="actions.showLightbox"
			data-wp-style--right="context.imageButtonRight"
			data-wp-style--top="context.imageButtonTop"
		>
			<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 12 12">
				<path fill="#fff" d="M2 0a2 2 0 0 0-2 2v2h1.5V2a.5.5 0 0 1 .5-.5h2V0H2Zm2 10.5H2a.5.5 0 0 1-.5-.5V8H0v2a2 2 0 0 0 2 2h2v-1.5ZM8 12v-1.5h2a.5.5 0 0 0 .5-.5V8H12v2a2 2 0 0 1-2 2H8Zm2-12a2 2 0 0 1 2 2v2h-1.5V2a.5.5 0 0 0-.5-.5H8V0h2Z" />
			</svg>
		</button>'; $body_content = preg_replace( '/<img[^>]+>/', $button, $body_content ); add_action( 'wp_footer', 'block_core_image_print_lightbox_overlay' ); return $body_content; } function block_core_image_print_lightbox_overlay() { $close_button_label = esc_attr__( 'Close' ); $background_color = '#fff'; $close_button_color = '#000'; if ( wp_theme_has_theme_json() ) { $global_styles_color = wp_get_global_styles( array( 'color' ) ); if ( ! empty( $global_styles_color['background'] ) ) { $background_color = esc_attr( $global_styles_color['background'] ); } if ( ! empty( $global_styles_color['text'] ) ) { $close_button_color = esc_attr( $global_styles_color['text'] ); } } echo <<<HTML
		<div 
			class="wp-lightbox-overlay zoom"
			data-wp-interactive="core/image"
			data-wp-context='{}'
			data-wp-bind--role="state.roleAttribute"
			data-wp-bind--aria-label="state.currentImage.ariaLabel"
			data-wp-bind--aria-modal="state.ariaModal"
			data-wp-class--active="state.overlayEnabled"
			data-wp-class--show-closing-animation="state.showClosingAnimation"
			data-wp-watch="callbacks.setOverlayFocus"
			data-wp-on--keydown="actions.handleKeydown"
			data-wp-on--touchstart="actions.handleTouchStart"
			data-wp-on--touchmove="actions.handleTouchMove"
			data-wp-on--touchend="actions.handleTouchEnd"
			data-wp-on--click="actions.hideLightbox"
			data-wp-on-window--resize="callbacks.setOverlayStyles"
			data-wp-on-window--scroll="actions.handleScroll"
			tabindex="-1"
			>
				<button type="button" aria-label="$close_button_label" style="fill: $close_button_color" class="close-button">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="M13 11.8l6.1-6.3-1-1-6.1 6.2-6.1-6.2-1 1 6.1 6.3-6.5 6.7 1 1 6.5-6.6 6.5 6.6 1-1z"></path></svg>
				</button>
				<div class="lightbox-image-container">
					<figure data-wp-bind--class="state.currentImage.figureClassNames" data-wp-bind--style="state.currentImage.figureStyles">
						<img data-wp-bind--alt="state.currentImage.alt" data-wp-bind--class="state.currentImage.imgClassNames" data-wp-bind--style="state.imgStyles" data-wp-bind--src="state.currentImage.currentSrc">
					</figure>
				</div>
				<div class="lightbox-image-container">
					<figure data-wp-bind--class="state.currentImage.figureClassNames" data-wp-bind--style="state.currentImage.figureStyles">
						<img data-wp-bind--alt="state.currentImage.alt" data-wp-bind--class="state.currentImage.imgClassNames" data-wp-bind--style="state.imgStyles" data-wp-bind--src="state.enlargedSrc">
					</figure>
				</div>
				<div class="scrim" style="background-color: $background_color" aria-hidden="true"></div>
				<style data-wp-text="state.overlayStyles"></style>
		</div>
HTML;
} function register_block_core_image() { register_block_type_from_metadata( __DIR__ . '/image', array( 'render_callback' => 'render_block_core_image', ) ); } add_action( 'init', 'register_block_core_image' ); 