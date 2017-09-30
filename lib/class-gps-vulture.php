<?php
/**
 * This is GPS Vulture for Gravity Forms.
 *
 * @package gps-vulture
 */

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * This class provides a field for creating GPS traces in GF.
 */
class GPS_Vulture extends GF_Field {

	/**
	 * What kind of field is this?
	 *
	 * @var $type
	 */
	public $type = 'gps_vulture';

	/**
	 * Have our actions been run yet?
	 */
	public static $actions_run = false;

	/**
	 * Default field settings.
	 */
	public static $vulture_defaults = array(
		'gps_vulture_gpsline' => 'checked',
		'gps_vulture_minDistance' => 5,
		'gps_vulture_minAccuracy' => 25,
		'gps_vulture_polyline' => 'checked',
		'gps_vulture_rectangle' => 'checked',
		'gps_vulture_polygon' => 'checked',
		'gps_vulture_circle' => 'checked',
		'gps_vulture_marker' => 'checked',
		'gps_vulture_circlemarker' => 'checked',
	);

	/**
	 * Register this field type.
	 */
	public static function register() {
		GF_Fields::register( new self());
	}

	/**
	 * Start this up!
	 *
	 * @param array $data Not sure, we just pass it up to the parent field.
	 */
	public function __construct( $data = array() ) {
		parent::__construct( $data );

		if ( !self::$actions_run ) {
			add_action( 'gform_field_standard_settings', array( $this, 'gform_field_standard_settings' ), 10, 2 );

			add_filter( 'gform_noconflict_scripts', array( $this, 'gform_noconflict_scripts' ) );
			add_filter( 'gform_noconflict_styles', array( $this, 'gform_noconflict_styles' ) );

			self::$actions_run = true;
		}
	}

	/**
	 * Get the title for the field type.
	 */
	public function get_form_editor_field_title() {
		return esc_attr__( 'GPS Vulture', 'gps-vulture' );
	}

	/**
	 * Get the placement and label for the field button.
	 */
	public function get_form_editor_button() {
		return array(
			'group' => 'advanced_fields',
			'text' => esc_html__( 'GPS Vulture', 'gravityforms' ),
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
			'gps_vulture_settings',
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

			$leaflet->add_layer( 'L.geoJSON', array( $geojson ), 'editthis' );


			$settings = $this->get_settings( $form );

			$args = array(
				'draw' => array(
					'gpsline' => array(
						'minDistance' => $settings['gps_vulture_minDistance'],
						'minAccuracy' => $settings['gps_vulture_minAccuracy'],
						),
				),
				'edit' => array(
					'featureGroup' => '@@@editthis@@@',
				),
			);

			$shapes = array(
				'polyline' => $settings['gps_vulture_polyline'],
				'rectangle' => $settings['gps_vulture_rectangle'],
				'polygon' => $settings['gps_vulture_polygon'],
				'circle' => $settings['gps_vulture_circle'],
				'marker' => $settings['gps_vulture_marker'],
				'circlemarker' => $settings['gps_vulture_circlemarker'],
			);
			
			foreach( $shapes as $shape => $checked ) {
				if ( $checked !== 'checked' ) {
					$args['draw'][$shape] = false;
				}
			}

			$leaflet->add_control('L.Control.Draw',$args,'drawControl');

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


	/**
	 * Get the standard settings.
	 *
	 * @param int $position Where should it appear on the page.
	 * @param int $form_id Which form is it for.
	 */
	public function gform_field_standard_settings( $position, $form_id ) {

		if ( 50 === $position ) {

			print '<li class="gps_vulture_settings field_setting">';
			print '<p>Note: On mobile devices the <em>rectangle</em>, <em>circle</em> and <em>circlemarker</em> inputs will be hidden.</p>';
			print '<label class="section_label">Map Input Settings</label>';
			print '<label><input type="checkbox" name="gps_vulture_gpsline" value="checked" ' . self::$vulture_defaults['gps_vulture_gpsline'] . '> GPS Trace Tool</label>';
			print '<label style="margin-left: 25px;">Min. distance between GPS trace points <input type="text" name="gps_vulture_minDistance" value="' . esc_attr( self::$vulture_defaults['gps_vulture_minDistance'] ) . '"></label>';
			print '<label style="margin-left: 25px;">Min. accuracy (in meters. smaller = more accurate) <input type="text" name="gps_vulture_minAccuracy" value="' . esc_attr( self::$vulture_defaults['gps_vulture_minAccuracy'] ) . '"></label>';
			print '<label><input type="checkbox" name="gps_vulture_polyline" value="checked" ' . self::$vulture_defaults['gps_vulture_polyline'] . '> Polyline tool</label>';
			print '<label><input type="checkbox" name="gps_vulture_rectangle" value="checked" ' . self::$vulture_defaults['gps_vulture_rectangle'] . '> Rectangle tool</label>';
			print '<label><input type="checkbox" name="gps_vulture_polygon" value="checked" ' . self::$vulture_defaults['gps_vulture_polygon'] . '> Polygon tool</label>';
			print '<label><input type="checkbox" name="gps_vulture_circle" value="checked" ' . self::$vulture_defaults['gps_vulture_circle'] . '> Circle tool</label>';
			print '<label><input type="checkbox" name="gps_vulture_circlemarker" value="checked" ' . self::$vulture_defaults['gps_vulture_circlemarker'] . '> Circle Marker tool</label>';
			print "<script>
				jQuery(document).bind('gform_load_field_settings')
			</script>";
			print '</li>';
		}
	}

	/**
	 * Get a list of geocoders.
	 */
	public function get_form_editor_inline_script_on_page_render() {
		$some_js = parent::get_form_editor_inline_script_on_page_render();

		$some_js .= "
				jQuery('li.gps_vulture_settings input[type=checkbox]').on('change',function(e){ 
					SetFieldProperty(e.target.name,e.target.checked ? e.target.value : '' );
				});
				jQuery('li.gps_vulture_settings input[type=text]').on('change',function(e){ 
					SetFieldProperty(e.target.name,e.target.value);
				});";

		$some_js .= "\njQuery(document).bind('gform_load_field_settings', function(event,field,form){\n";

		$text_inputs = array('gps_vulture_minDistance','gps_vulture_minAccuracy');

		$checkbox_inputs = array(
			"gps_vulture_gpsline",
			"gps_vulture_polyline",
			"gps_vulture_rectangle",
			"gps_vulture_polygon",
			"gps_vulture_circle",
			"gps_vulture_circlemarker",
		);

		foreach( $text_inputs as $field_name ) {
			$some_js .= "jQuery('input[name=\"$field_name\"]').val((field.$field_name === undefined ? '" . self::$vulture_defaults[$field_name] . "' : field.$field_name ));\n";
		}

		foreach( $checkbox_inputs as $field_name ) {
			$some_js .= "jQuery('input[name=\"$field_name\"').prop('checked',(field.$field_name === undefined ? '" . self::$vulture_defaults[$field_name] . "' === 'checked' : field.$field_name === 'checked'));\n";
		}
		$some_js .= "});";
		return $some_js;
	}

	public function get_settings( $form = array() ) {
		return wp_parse_args( $form, self::$vulture_defaults );
	}

	function gform_noconflict_scripts( $required_scripts ) {
		$required_scripts[] = 'form_admin_geocode';
		$required_scripts[] = 'gfg_geocode';
		$required_scripts[] = 'leafletphp-leaflet-js';
		return $required_scripts;
	}

	function gform_noconflict_styles( $required_styles ) {
		$required_styles[] = 'form_admin_geocode';
		$required_styles[] = 'leafletphp-css';
		$required_styles[] = 'leafletphp-leaflet-css';
		return $required_styles;
	}

}
