<?php
if ( !class_exists( 'SettingsCtl' ) ) :

class SettingsCtl {
	static $version = '1.2';

	static function select_page( $args ) {
		$key = $args['label_for'];
		wp_dropdown_pages( array(
			'name' => $key,
			'show_option_none' => ' - None -',
			'selected' => get_option( $key )
		) );
	}

	static function input_text( $args ) {
		$args['type'] = 'text';
		echo self::input( $args );
	}

	static function input_password( $args ) {
		$args['type'] = 'password';
		echo self::input( $args );
	}

	static function input_hidden( $args ) {
		$args['type'] = 'hidden';
		echo self::input( $args );
	}

	static function input( $args ) {
		$defaults = array(
			'type'      => 'text',
			'attr'      => array(),
			'label_for' => ''
		);

		$args = wp_parse_args( $args, $defaults );
		$attr = (array)$args['attr'];

		$name  = !empty( $args['name'] ) ? $args['name'] : $args['label_for'];
		$value = !empty( $args['value'] ) ? $args['value'] : get_option( $name );

		$attr['id'] = $args['label_for'];
		if ( isset( $args['class'] ) ) {
			$attr['class'] = $args['class'];
		}

		$attr = self::attr_string( $attr );

		$out = sprintf( '<input type="%s" name="%s" value="%s" %s />', $args['type'], $name, $value, $attr );
		$out .= self::add_description( $args );

		return $out;
	}

	static function add_description( $args ) {
		if ( !empty( $args['description'] ) )
			return '<span class="description">' . $args['description'] . '</span>';
	}

	static function select( $args ) {
		//name, options, args

		if ( !empty( $args['label_for'] ) ) {
			$id = $args['label_for'];
			$attr[] = 'id="' . esc_attr( $id ) . '"';
		}

		$name  = !empty( $args['name'] ) ? $args['name'] : $id;

		if ( empty( $args['selected'] ) ) {
			$args['selected'] = get_option( $name );
		}

		if ( ! $args['selected'] && isset( $args['default'] ) ) {
			$args['selected'] = $args['default'];
		}

		//option_callback
		$options = array();
		if ( isset( $args['options'] ) ) {
			$options = is_callable( $args['options'] ) ? call_user_func( $args['options'] ) : $args['options'];
		}

		echo self::dropdown( $name, $options, $args );
		echo self::add_description( $args );
	}

	static function dropdown( $name, $options, $args = array() ) {
		$defaults = array(
			'echo' => false,
			'selected' => null,
			'id' => $name,
			'show_option_none' => false,
			'option_none_text' => '',
			'attr' => array()
		);

		$args = wp_parse_args( $args, $defaults );
		if ( !empty( $args['id'] ) )
			$attr[] = 'id="' . esc_attr( $args['id'] ) . '"';

		$options_html = $args['show_option_none'] ? '<option value="">' . $args['option_none_text'] . '</option>' . "\n" : '';
		foreach( $options as $val => $label ) {
			//Optgroup
			if ( is_array( $label ) ) {
				//$val becomes group label in this scenario
				$options_html .= sprintf( '<optgroup label="%s">', esc_attr( $val ) );
				foreach( $label as $inner_val => $inner_label ) {
					$sel = selected( $inner_val, $args['selected'], false );
					$options_html .= sprintf( '<option value="%s" %s>%s</option>', esc_attr( $inner_val ), $sel, esc_html( $inner_label ) ) . "\n";
				}
				$options_html .= '</optgroup>';
			} else {
				$sel = selected( $val, $args['selected'], false );
				$options_html .= sprintf( '<option value="%s" %s>%s</option>', esc_attr( $val ), $sel, esc_html( $label ) ) . "\n";
			}
		}

//		$attr = implode( ' ', $attr );
		$attr = self::attr_string( $attr );
		$out = sprintf( '<select name="%s" %s>%s</select>', esc_attr( $name ), $attr, $options_html );

		if ( $args['echo'] )
			echo $out;

		return $out;
	}

	static function checkboxes( $args ) {

		if ( !empty( $args['label_for'] ) ) {
			$id = $args['label_for'];
			$attr[] = 'id="' . esc_attr( $id ) . '"';
		}

		$name  = !empty( $args['name'] ) ? $args['name'] : $id;

		if ( empty( $args['selected'] ) )
			$args['selected'] = get_option( $name );

		//option_callback
		$options = isset( $args['options'] ) ? call_user_func( $args['options'] ) : array();

		echo self::checkbox_list( $name, $options, $args );
		echo self::add_description( $args );
	}

	static function checkbox_list( $name, $options, $args = array() ) {

		$defaults = array(
			'selected' => array(),
			'id_base' => $name . '_',
			'attr' => array()
		);

		$args = wp_parse_args( $args, $defaults );

		$options_html = '';
		foreach( $options as $key => $opt ) {

			//Convert value => label format used by dropdowns to this more complex format.
			if ( !is_array( $opt ) )
				$opt = array( 'value' => $key, 'label' => $opt );

			$id = sanitize_title( $args['id_base'] . $opt['value'] );
			$label_attr = array( 'label_for' => $id );

			$option_attr = array(
				'type' => 'checkbox',
				'id' => $id,
				'name' => !empty( $opt['name'] ) ? $opt['name'] : $name . '[]',
				'value' => $opt['value']
			);

			$option_attr[] = checked( in_array( $opt['value'], (array) $args['selected'] ), true, false );

			if ( !empty( $opt['attr'] ) && is_array( $opt['attr'] ) )
				$option_attr += $opt['attr'];

			$label_attr = self::attr_string( $label_attr );
			$option_attr = self::attr_string( $option_attr );
			$text = $opt['label'];

			$options_html .= sprintf( '<label %s><input %s> %s</label><br />', $label_attr, $option_attr, $text ) . "\n";
		}

		return $options_html;

	}

	static function attr_string( $attr ) {
		if ( ! is_array( $attr ) )
			return strval( $attr );

		$items = array();
		foreach( $attr as $key => $val ) {
			if ( is_numeric( $key ) ) {
				$items[] = $val;
			} else {
				$items[] = sprintf( '%s="%s"', $key, esc_attr( $val ) );
			}
		}

		return implode( ' ', $items );
	}

}

endif;
