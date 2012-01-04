<?php

/*
		[[[[[[[[[[		  [[[[[[[[[[[		  [[[[[[[[[[[				Parser Engine
	  [[[					 [[[					 [[[
	[[[					[[[					[[[							{
   [[[						[[[[[[[[[[[			 [[[[[[[[[[[					@version: 1.0.0 alpha
    [[[								[[[					[[[					@license: GPL
     [[[								 [[[					 [[[					@supports: up to CSS3
      [[[								 [[[					  [[[					@founded: Bilawal Hameed, Nov 11
  	     [[[[[[[[[[		[[[[[[[[[[[			 [[[[[[[[[[[				}
*/

class CSSParser {

	var $css = NULL;
	var $result = NULL;
	var $tmp = array();
	var $arraytmp = array();
	
	function __construct( $input = FALSE ) {
		
		if( $input == FALSE ) {
			
			return $this->engine_error(
				'You need to input a URL or raw CSS data before we begin.',
				'$e = new CSSParser("http://www.google.com/style.css");'
			);	
		
		}
		
		if( count( parse_url($input) ) > 0 ) {
			$this->css = @file_get_contents( $input );
		} else {
			$this->css = $input;
		}
		
	}
	
	function decode() {
		
		$regex = array(
			"`^([\t\s]+)`ism"=>'',
			"`^\/\*(.+?)\*\/`ism"=>"",
			"`([\n\A;]+)\/\*(.+?)\*\/`ism"=>"$1",
			"`([\n\A;\s]+)//(.+?)[\n\r]`ism"=>"$1\n",
			"`(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+`ism"=>"\n",
			"#/\*[^(\*/)]*\*/#" => "\n"
		);
		
		$this->css = preg_replace( array_keys($regex), $regex, $this->css);
		$this->css = str_replace( PHP_EOL, '', $this->css);
		
		preg_match_all( '/(?ims)([a-z0-9\s\.\:#_\-@]+)\{([^\}]*)\}/', $this->css, $arr);
		
		foreach( $arr[0] as $i => $x )
		{
			$selector = trim( $arr[1][$i] );
			$rules = explode( ';', trim( $arr[2][$i] ) );
			$this->result[$selector] = array();

			foreach( $rules as $strRule )
			{
				if( empty( $strRule ) ) { continue; }
				
				/*
					@note: Background URL bug fix
				*/
				
					if(strstr($strRule, 'background:url'))
					{
						$this->tmp = $strRule;
						$this->tmp .= ';';
						$this->arraytmp['next_bg'] = TRUE;
						continue;
					}

					if( $this->arraytmp['next_bg'] == TRUE )
					{
						$this->tmp .= $strRule;
						$strRule = trim($this->tmp);
						unset($this->arraytmp['next_bg']);
						unset($this->tmp);
					}
           
           
				$rule = explode( ":", $strRule, 2 );
				$css_name = strtolower(trim($rule[0]));
				$css_value = strtolower(trim($rule[1]));
				
				/*
					@note: This formats the font-family
				*/
					if($css_name == 'font-family') {
						$css_value = str_replace( array( '"', '\'' ), '', $css_value );

						foreach( explode(',', $css_value) as $strips ) {
							$this->tmp[] = ucwords(trim($strips));
						}
						
						$css_value = $this->tmp;
						unset($this->tmp);
					}
					
				/*
					@note: This formats the margin into the respective formats
				*/
					if($this->is_value($css_name, 'margin|margin-left|margin-right|margin-top|margin-bottom')) {
						
						if($css_name == 'margin') {
							$itms = explode(' ', $css_value);
							
							if(count($itms) == 1) {
								$css_value = array();
								$css_value['top'] = trim($itms[0]);
								$css_value['right'] = trim($itms[0]);
								$css_value['bottom'] = trim($itms[0]);
								$css_value['left'] = trim($itms[0]);
							} else if(count($itms) == 2) {
								$css_value = array();
								$css_value['top'] = trim($itms[0]);
								$css_value['right'] = trim($itms[1]);
								$css_value['bottom'] = trim($itms[0]);
								$css_value['left'] = trim($itms[1]);
							} else if(count($itms) == 3) {
								$css_value = array();
								$css_value['top'] = trim($itms[0]);
								$css_value['right'] = trim($itms[1]);
								$css_value['bottom'] = trim($itms[2]);
								$css_value['left'] = trim($itms[1]);
							} else {
								$css_value = array();
								$css_value['top'] = trim($itms[0]);
								$css_value['right'] = trim($itms[1]);
								$css_value['bottom'] = trim($itms[2]);
								$css_value['left'] = trim($itms[3]);
							}
							
							unset($itms);
						} else {
							$this->result[ $selector ][]['margin'][ str_replace('margin-', '', $css_name) ] = $css_value;
							continue;
						}
						
					}
					
					/*
						@note: Ignore some moz and webkit specific CSS commands. They replicate standard CSS elements.
					*/
					$array = array(
						'-moz-border-radius',
						'-webkit-border-radius',
						'-moz-border-shadow',
						'-webkit-border-shadow',
						'-moz-box-shadow',
						'-webkit-box-shadow',
						'-moz-text-shadow',
						'-webkit-text-shadow'
					);
					if($this->is_value($css_name, $array)) {
						continue;
					}
					
					/*
						@note: This formats the dynamic layout of a background CSS attribute into the respective format
					*/
					if($this->is_value($css_name, 'background')) {
						
						if($css_name == 'background') {
							$itms = explode(' ', $css_value);
							
							if(count($itms) == 0) {
								$this->result[ $selector ][]['background' ] = $css_value;
								break;
							}
							
							$original_css = $css_value;
							$css_value = array();
													
							foreach($itms as $itm) {
								$itm = trim($itm);
								
								if($this->is_value($css_name, '')) {
									
								}
								
								else if(substr($itm, 0, 3) == 'url') {
									$this->tmp = array();
									$this->tmp = preg_replace('/url\((.+)\)/', '$1', trim($itm));
									
									if( substr($this->tmp, 0, 5) != "data:" ) {
										$css_value['full_url'] = $this->tmp;
									} else {
									
										$css_value['url_raw'] = $this->tmp;
										preg_match('/data\:(.+)\;(.+),(.+)/', $this->tmp, $this->tmp);
									
										$css_value['url_data']['mime_type'] = $this->tmp[1];
										$css_value['url_data']['encode_type'] = strtoupper($this->tmp[2]);
										$css_value['url_data']['encoded'] = FALSE;
									
										switch(strtolower($this->tmp[2])) {
											/*
												@note: This detects whether the type is base64, as most PHP servers have this built-in
											*/
											case "base64":
										
												/*
													@note: In the unlikely case that BASE64 isn't available, let's skip this process.
												*/
												if( ! function_exists("base64_decode") ) { break; }
											
												/*
													@note: Alright, to get here, base64 is available, let's decode and let the script know we decoded!
												*/
												$css_value['url_data']['encoded'] = TRUE;
												$css_value['url_data']['raw'] = $this->tmp[3];
												$css_value['url_data']['output'] = base64_decode( $this->tmp[3] );
												break;
										}
									
									}
									
									/*
										@note: We don't need the $this->tmp memory anymore! 
									*/
									
									unset($this->tmp);
									
								}
								
							}
							
							if(count($css_value) == 0 ) {
								$css_value = $original_css;
							}
							
							unset($itms);
						}
						
					}
					
					/*
						@note: This formats the padding into the respective formats
					*/
					if($this->is_value($css_name, 'padding|padding-left|padding-right|padding-top|padding-bottom')) {
						
						if($css_name == 'padding') {
							$itms = explode(' ', $css_value);
							
							if(count($itms) == 1) {
								$css_value = array();
								$css_value['top'] = trim($itms[0]);
								$css_value['right'] = trim($itms[0]);
								$css_value['bottom'] = trim($itms[0]);
								$css_value['left'] = trim($itms[0]);
							} else if(count($itms) == 2) {
								$css_value = array();
								$css_value['top'] = trim($itms[0]);
								$css_value['right'] = trim($itms[1]);
								$css_value['bottom'] = trim($itms[0]);
								$css_value['left'] = trim($itms[1]);
							} else if(count($itms) == 3) {
								$css_value = array();
								$css_value['top'] = trim($itms[0]);
								$css_value['right'] = trim($itms[1]);
								$css_value['bottom'] = trim($itms[2]);
								$css_value['left'] = trim($itms[1]);
							} else {
								$css_value = array();
								$css_value['top'] = trim($itms[0]);
								$css_value['right'] = trim($itms[1]);
								$css_value['bottom'] = trim($itms[2]);
								$css_value['left'] = trim($itms[3]);
							}
							
							unset($itms);
						} else {
							$this->result[ $selector ][]['padding'][ str_replace('padding-', '', $css_name) ] = $css_value;
							continue;
						}
						
					}
					
					/*
						@note: This formats the border-color into the respective format
					*/
					if($this->is_value($css_name, 'border-color|border-color-top|border-color-right|border-color-bottom|border-color-left')) {
						
						if($css_name == 'border-color') {
							$itms = explode(' ', $css_value);
							
							if(count($itms) == 1) {
								$css_value = array();
								$css_value['top'] = trim($itms[0]);
								$css_value['right'] = trim($itms[0]);
								$css_value['bottom'] = trim($itms[0]);
								$css_value['left'] = trim($itms[0]);
							} else if(count($itms) == 2) {
								$css_value = array();
								$css_value['top'] = trim($itms[0]);
								$css_value['right'] = trim($itms[1]);
								$css_value['bottom'] = trim($itms[0]);
								$css_value['left'] = trim($itms[1]);
							} else if(count($itms) == 3) {
								$css_value = array();
								$css_value['top'] = trim($itms[0]);
								$css_value['right'] = trim($itms[1]);
								$css_value['bottom'] = trim($itms[2]);
								$css_value['left'] = trim($itms[1]);
							} else {
								$css_value = array();
								$css_value['top'] = trim($itms[0]);
								$css_value['right'] = trim($itms[1]);
								$css_value['bottom'] = trim($itms[2]);
								$css_value['left'] = trim($itms[3]);
							}
							
							unset($itms);
						} else {
							$this->result[ $selector ][]['border-color'][ str_replace('border-color-', '', $css_name) ] = $css_value;
							continue;
						}
						
					}
					
					/*
						@note: This formats the border into the respective formats
					*/
					if($this->is_value($css_name, 'border|border-top|border-bottom|border-right|border-left')) {
						
						$itms = explode(' ', $css_value);
							
						if(count($itms) == 1) {
							$css_value = array();
							$css_value['size'] = trim($itms[0]);
							$css_value['type'] = 'solid';
							$css_value['color'] = 'inherit';
						} else if(count($itms) == 2) {
							$css_value = array();
							$css_value['size'] = trim($itms[0]);
							$css_value['type'] = trim($itms[1]);
							$css_value['color'] = 'inherit';
						} else {
							$css_value = array();
							$css_value['size'] = trim($itms[0]);
							$css_value['type'] = trim($itms[2]);
							$css_value['color'] = trim($itms[1]);
						}
							
						unset($itms);
						
						if($css_name == 'border') {
							$this->result[ $selector ][]['border'][ 'top' ] = $css_value;
							$this->result[ $selector ][]['border'][ 'right' ] = $css_value;
							$this->result[ $selector ][]['border'][ 'bottom' ] = $css_value;
							$this->result[ $selector ][]['border'][ 'left' ] = $css_value;
						} else {
							$this->result[ $selector ][]['border'][ str_replace('border-', '', $css_name) ] = $css_value;
						}
						
						
						continue;
						
					}
					
					/*
						@note: Changes background-color/background-image to background (to save space)
					*/
					if($this->is_value($css_name, 'background-color|background-image')) {
						$css_name = 'background';
					}
				
				$this->result[ $selector ][][ $css_name ] = $css_value;
			
			}
		}
		
		return $this->result;
	}
	
	
	/*
		@name Miscellaneous
		@description This area involves the error reporting system that is fully customisable in a central manner, and will contain any code that does not directly relate to the purpose of the CSSParser.
		@author Bilawal Hameed [http://www.bilawal.co.uk/]
		@created 2nd January 2012
		@updated 2nd January 2012
	*/
	
	private function is_value($value, $options)
	{
		$options = (is_array($options) ? $options : explode('|', $options));
		foreach($options as $each)
		{
			if($value == $each)
			{
				return TRUE;
			}
		}
		
		return FALSE;
	}
	
	private function engine_error( $err, $suggested_code = FALSE ) {
		/* Display the code error */
		echo "<div style='font-family:arial, verdana; font-size:14px; background:#eee; padding:15px; line-height:24px;'>
					<p style='margin:0px; font-weight:bold; font-size:18px;'>CSSParser Error</p>
					<p style='margin:0px;'>" . $err . "</p>";
				
				if( $suggested_code) {
					echo "<br /><span style='display:block; background:#ddd; padding:6px;'><p style='margin:0px;'><b>The code needs to be as displayed below:</b></p><code>" . $suggested_code . "</code></span>";
				}	
		
		echo "</div>";
		return;
	}

}
