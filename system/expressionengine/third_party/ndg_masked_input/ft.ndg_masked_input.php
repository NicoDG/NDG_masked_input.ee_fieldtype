<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * This file must be in your /system/third_party/ndg_masked_input directory of your ExpressionEngine installation
 *
 * @package             NDG Masked Input Fiedltype for EE2
 * @author              Nico De Gols (nicodegols@me.com)
 * @copyright			Copyright (c) 2010 Nico De Gols
 * @version             Release: 1.0
 * @link                http://pixelclub.be
 */
 
class Ndg_masked_input_ft extends EE_Fieldtype {

	var $info = array(
		'name'		=> 'Masked Text Input',
		'version'	=> '1.0'
	);

	// Parser Flag (preparse pairs?)
	var $has_array_data = FALSE;

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function Ndg_masked_input_ft()
	{
		parent::EE_Fieldtype();
	}
	
	
	// --------------------------------------------------------------------
	
	function display_field($data)
	{
		$firstmask = $this->settings["mask"][key($this->settings["mask"])];

		$theme_folder_url = $this->EE->config->item('theme_folder_url');

		$this->EE->cp->add_to_head('<script type="text/javascript" src="'.$theme_folder_url.'third_party/ndg_masked_input/jquery.inputmask.js"></script>');
		
		$this->EE->javascript->output('$("#'.$this->field_name.'").inputmask({"mask" : "'.$firstmask.'", "autounmask" : false, "greedy" : false});');
		
		if (REQ == 'CP')
		{
			$this->EE->javascript->output('
				if($("#hold_field_'.$this->field_id.' > .instruction_text").length > 0){
					$("#hold_field_'.$this->field_id.' .instruction_text > p").append(" Input format: '.$firstmask.'");
				}else{
					$("#sub_hold_field_'.$this->field_id.'").prepend(\'<div class="instruction_text"><p><strong>Instructions</strong> Input format: '.$firstmask.'</p></div>\');
				}
			');
		}

		$dropdown = "";
		if(count($this->settings["mask"]) > 1){
			$options = array();
			foreach($this->settings["mask"] as $label => $value){
				$options[$value] = $label;
			}
			$dropdown .= form_dropdown('maskoptions', $options, '');			
		}
		return $dropdown.form_input(array(
			'name'		=> $this->field_name,
			'id'		=> $this->field_name,
			'value'		=> $data,
			'dir'		=> $this->settings['field_text_direction']
		));
	}
	
	// --------------------------------------------------------------------
		
	function display_cell( $data )
	{
	
		$firstmask = $this->settings["mask"][key($this->settings["mask"])];

		$theme_folder_url = $this->EE->config->item('theme_folder_url');

		$this->EE->cp->add_to_head('<script type="text/javascript" src="'.$theme_folder_url.'third_party/ndg_masked_input/jquery.inputmask.js"></script>');
		
	  	return form_input(array(
			'name'		=> $this->cell_name,
			'id'		=> $this->cell_name,
			'class'		=> $firstmask,
			'value'		=> $data,
			'dir'		=> $this->settings['field_text_direction'],
			'onFocus'	=> '$(this).inputmask({\'mask\' : \''.$firstmask.'\', \'autounmask\' : false, \'greedy\' : false});'
		));
	}

	
	// --------------------------------------------------------------------
	
	function validate($data)
	{
	
		if(strlen(str_replace("_","",$data)) != strlen($this->settings["mask"][key($this->settings["mask"])]) && $this->settings["field_required"] == "y"){
			return $this->EE->lang->line("Please fill in a valid value");
		}
		
		return TRUE;
	}
	
	// --------------------------------------------------------------------
		
	function save_cell( $data )
	{

	  if ($data == '&nbsp;') $data = '';
	
	  return $data;
	}

	
	// --------------------------------------------------------------------
	
	function replace_tag($data, $params = '', $tagdata = '')
	{
		return $this->EE->typography->parse_type(
			$this->EE->functions->encode_ee_tags($data),
			array(
				'text_format'	=> $this->row['field_ft_'.$this->field_id],
				'html_format'	=> $this->row['channel_html_formatting'],
				'auto_links'	=> $this->row['channel_auto_link_urls'],
				'allow_img_url' => $this->row['channel_allow_img_urls']
			)
		);
	}
	

	// --------------------------------------------------------------------

	function display_settings($data)
	{
		$prefix = 'text';
		$field_content_text	= ($data['field_content_text'] == '') ? 'any' : $data['field_content_text'];
		$mask				= isset($data['mask']) ? $this->options_setting($data['mask']) : '';
	
		
		$this->EE->table->add_row(
			lang('instructions', 'instructions'),
			nl2br('a     - Represents an alpha character (A-Z,a-z)
				   9     - Represents a numeric character (0-9)
				   *     - Represents an alphanumeric character (A-Z,a-z,0-9)
				   d/m/y - day/month/year ex 06/12/2010
				   (999) 999-9999 - phone example
					')
		);
		$this->EE->table->add_row(
			lang('mask', 'mask'),
			form_input('mask', $mask)
		);
		
		
		$this->field_formatting_row($data, $prefix);
		$this->text_direction_row($data, $prefix);
	}

	// --------------------------------------------------------------------
	
	function display_cell_settings( $data )
	{
	  if (! isset($data['mask'])){ 
	  	$data['mask'] = '';
	  }else{
	  	$data['mask'] = $this->options_setting($data['mask']);
	  }
	  return array(
	    array(lang('mask'), form_input('mask', $data['mask'], 'class="matrix-textarea"'))
	  );
	}

	// --------------------------------------------------------------------
	
	function options_setting($options=array(), $indent = '')
	{
	
		$r = '';

		foreach($options as $name => $label)
		{
			if ($r !== '') $r .= "\n";

			// is this just a blank option?
			if (! $name && ! $label) $name = $label = ' ';

			$r .= $name;

			// is this an optgroup?
			if (is_array($label)) $r .= "\n".$this->options_setting($label, $indent.'    ');
			else if ($name != $label) $r .= ' : '.$label;
		}

		return $r;
	}
	

	// --------------------------------------------------------------------

	function save_settings($data)
	{		
		
		return array(
			'field_content_text'	=> $this->EE->input->post('field_content_text'),
			'mask'					=> $this->save_options_setting($this->EE->input->post('mask'))
			
		);
	}
	
	// --------------------------------------------------------------------
		
	function save_cell_settings( $data )
	{
	
	  if (! isset($data['mask'])){ 
	  	$data['mask'] = '';
	  }else{
	  	$data['mask'] = $this->save_options_setting($data['mask']);
	  }
	
	  return $data;
	}

	// --------------------------------------------------------------------

	function save_options_setting($options = '', $total_levels = 1)
	{
		// prepare options
		$options = preg_split('/[\r\n]+/', $options);
		foreach($options as &$option)
		{
			$option_parts = preg_split('/\s:\s/', $option, 2);
			$option = array();
			$option['indent'] = preg_match('/^\s+/', $option_parts[0], $matches) ? strlen(str_replace("\t", '    ', $matches[0])) : 0;
			$option['name']   = trim($option_parts[0]);
			$option['value']  = isset($option_parts[1]) ? trim($option_parts[1]) : $option['name'];
		}

		return $this->_structure_options($options, $total_levels);
	}

	// --------------------------------------------------------------------
	
	private function _structure_options(&$options, $total_levels, $level = 1, $indent = -1)
	{
		$r = array();

		while ($options)
		{
			if ($indent == -1 || $options[0]['indent'] > $indent)
			{
				$option = array_shift($options);
				$children = (! $total_levels OR $level < $total_levels)
				              ?  $this->_structure_options($options, $total_levels, $level+1, $option['indent']+1)
				              :  FALSE;
				$r[(string)$option['name']] = $children ? $children : (string)$option['value'];
			}
			else if ($options[0]['indent'] <= $indent)
			{
				break;
			}
		}

		return $r;
	}
	
	
}

// END Ndg_masked_input_Ft class

/* End of file ft.ndg_masked_input.php */
/* Location: ./system/expressionengine/third_party/ndg_masked_input/ft.ndg_masked_input.php */