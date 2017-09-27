<?php
/**
 * This is the Partial Station for Gravity Forms.
 *
 * It's not as good as a Total Station, but it should be good enough for lots of use cases.
 *
 * @package partial-station
 */

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * This class provides a field for creating GPS traces in GF.
 */
class Partial_Station_GPS_Trace extends GF_Field {

	/**
	 * What kind of field is this?
	 *
	 * @var $type
	 */
	public $type = 'partial_station_gps_trace';

	public static function register() {
		GF_Fields::register( new self());
	}

	public function __construct( $data = array() ) {
		parent::__construct( $data );
	}

	/**
	 * Get the title for the field type.
	 */
	public function get_form_editor_field_title() {
		return esc_attr__( 'GPS Trace', 'partial_station' );
	}

	/**
	 * Get the placement and label for the field button.
	 */
	public function get_form_editor_button() {
		return array(
			'group' => 'advanced_fields',
			'text' => esc_html__( 'GPS Trace', 'gravityforms' ),
		);
	}

	/**
	 * Get the list of supported settings for this field type.
	 */
	public function get_form_editor_field_settings() {
		return array(
			'conditional_logic_field_setting',
			'prepopulate_field_setting',
			'error_message_setting',
			'label_setting',
			'label_placement_setting',
			'admin_label_setting',
			'size_setting',
			'rules_setting',
			'default_value_setting',
			'css_class_setting',
			// 'geocoding_setting',
			'visibility_setting',
			'description_setting',
		);
	}

	/**
	 * Does what it says on the label.
	 */
	public function is_conditional_logic_supported() {
		return true;
	}

	/**
	 * Get the field html.
	 *
	 * @param object $form The current form.
	 * @param string $value The current value of the field.
	 * @param array  $entry The current entry.
	 */
	public function get_field_input( $form, $value = '', $entry = null ) {
		$form_id         = absint( $form['id'] );
		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();

		$logic_event = ! $is_form_editor && ! $is_entry_detail ? $this->get_conditional_logic_event( 'keyup' ) : '';
		$id          = (int) $this->id;
		$field_id    = $is_entry_detail || $is_form_editor || 0 === $form_id ? "input_$id" : 'input_' . $form_id . "_$id";

		$value        = esc_attr( $value );
		$size         = $this->size;
		$class_suffix = $is_entry_detail ? '_admin' : '';
		$class        = $size . $class_suffix;

		$max_length = is_numeric( $this->maxLength ) ? "maxlength='{$this->maxLength}'" : ''; // @codingStandardsIgnoreLine

		$tabindex              = $this->get_tabindex();
		$disabled_text         = $is_form_editor ? 'disabled="disabled"' : '';
		$required_attribute    = $this->isRequired ? 'aria-required="true"' : ''; // @codingStandardsIgnoreLine
		$invalid_attribute     = $this->failed_validation ? 'aria-invalid="true"' : 'aria-invalid="false"';

		$show_map = ( ! isset( $this->geocoder_appearance_map ) ? true : $this->geocoder_appearance_map );
		$show_geojson = ( ! isset( $this->geocoder_appearance_geojson ) ? false : $this->geocoder_appearance_geojson );
		$show_latlng = ( ! isset( $this->geocoder_appearance_latlng ) ? false : $this->geocoder_appearance_latlng );

		$show_something = ($show_map || $show_geojson || $show_latlng );

		$input = '';

		$geojson = json_decode( html_entity_decode( $value ), true );

		if ( $show_something ) {
			$classes = array();
			if ( $show_map ) {
				$classes[] = 'has_map';
			}
			if ( $show_geojson ) {
				$classes[] = 'has_geojson';
			}
			if ( $show_latlng ) {
				$classes[] = 'has_latlng';
			}
			$input .= '<div class="ginput_complex ginput_container ' . implode( ' ', $classes ) . '">';
		}

		/**
		 * Display the map, with a Leaflet.draw editor
		 */
		if ( $show_map || $is_form_editor || $is_entry_detail ) {
			$leaflet = new LeafletPHP( array(), "geocode_map_$field_id" );

			if ( !$is_form_editor ) {
				$leaflet->add_layer( 'L.geoJSON', array( $geojson ), 'editthis' );
				$leaflet->add_control('L.Control.Draw',array(
					'draw' => array(
						'polyline' => false,
						'polygon' => false,
						'circle' => false,
						'rectangle' => false,
					),
					'edit' => array(
						'featureGroup' => '@@@editthis@@@',
					),
				),'drawControl');
			}

			if ( $is_entry_detail ) {
				$leaflet->add_script( $this->get_form_inline_script_on_page_render( $form, false ) );
			}

			if ( $is_form_editor ) {

				$fe_class = '';
				if ( ! $show_map ) {
					$fe_class = 'hidden';
				}

				$input .= '<div class="mapdisplay ' . $fe_class . '">';
			}

			$input .= '<p>' . $leaflet->get_html() . '</p>';

			if ( $is_form_editor ) {
				$input .= '</div>';
			}
		}

		/**
		 * Show the GeoJSON text input box
		 */
		if ( $show_geojson || $is_form_editor || $is_entry_detail ) {

			if ( $is_form_editor ) {

				$fe_class = '';
				if ( ! $show_geojson ) {
					$fe_class = 'hidden';
				}

				$input .= '<div class="geojsondisplay ' . $fe_class . '">';
			}

			$input .= "<span class='ginput_full'><textarea name='input_{$id}' id='{$field_id}' class='geocoderesults {$class}' {$tabindex} {$logic_event} {$required_attribute} {$invalid_attribute} {$disabled_text}>{$value}</textarea><label for='{$field_id}'>" . esc_html__( 'Location GeoJSON' ) . '</label></span>';

			if ( $is_form_editor ) {
				$input .= '</div>';
			}
		} else {
			$input .= "<input name='input_{$id}' id='{$field_id}' type='hidden' value='{$value}' class='{$class}' {$max_length} {$tabindex} {$logic_event} {$invalid_attribute} {$disabled_text}/>";
		}

		if ( $show_latlng || $is_form_editor || $is_entry_detail ) {

			if ( $is_form_editor ) {

				$fe_class = '';
				if ( ! $show_latlng ) {
					$fe_class = 'hidden';
				}

				$input .= '<div class="latlngdisplay ' . $fe_class . '">';
			}

			if ( is_array( $geojson['geometry'] ) && is_array( $geojson['geometry']['coordinates'] ) ) {
				$lat = $geojson['geometry']['coordinates'][1];
				$lng = $geojson['geometry']['coordinates'][0];
			} else {
				$lat = '';
				$lng = '';
			}
			$input .= '<span class="ginput_left">';
			$input .= "<input class='gf_left_half' id='{$field_id}_lat' type='text' value='{$lat}'><label for='{$field_id}_lat'>" . esc_html__( 'Latitude', 'cimburacom' ) . '</label>';
			$input .= '</span>';
			$input .= '<span class="ginput_right ginput_container">';
			$input .= "<input class='gf_right_half' id='{$field_id}_lng' type='text' value='{$lng}'><label for='{$field_id}_lng'>" . esc_html__( 'Longitude', 'cimburacom' ) . '</label>';
			$input .= '</span>';

			if ( $is_form_editor ) {
				$input .= '</div>';
			}
		}

		$input .= "\n" . '<script>jQuery(document).ready(function(){new gfg_sync_data("' . $field_id . '");});</script>';

		if ( $show_something ) {
			$input .= '</div>';
		}

		return sprintf( "<div class='ginput_container ginput_container_geocoder'>%s</div>", $input );
	}

	/**
	 * Is the submitted field valid?
	 *
	 * @param string $value The value.
	 * @param object $form The current form.
	 */
	public function validate( $value, $form ) {
		return WP_GeoUtil::is_geojson( $value );
	}
}
