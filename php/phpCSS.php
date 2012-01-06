<?php
/*

Copyright (c) 2012 Bilawal Hameed

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

/*

	phpCSS {
		@version					1.0.0-alpha
		@license					MIT License
		@url							http://github.com/bilawal360/phpcss
		@author					Bilawal Hameed [http://www.bilawal.co.uk/]
		@last_updated			4 January 2012
	}

*/

class phpCSS
{

	/*
		@note: We need several variables that are available throughout the class
			[$css] enables us to store any raw CSS that's universally accessible in a semantic manner.
			[$result] is the array state after the CSS is successfully decoded.
			[$array_final] stores the final, organised and sorted CSS data state. If sorted is disable, this will not be used.
			[$tmp] is used for temporary storage. Minimizes the use of lots of useless variables.
			[$arraytmp] is a temporary storage that is elastic through array. $tmp is emptied/unset immediately after usage, $arraytmp is not.
	*/
	var $css = NULL;
	var $result = NULL;
	var $array_final = NULL;
	var $tmp = array();
	var $arraytmp = array();
	
	/* @note: __construct is run immediately after the CSSParser is initiated.
		@example: $phpcss = new CSSParser('http://www.website.com/style.css'); */
	public function __construct( $input = FALSE )
	{
		
		/* @note: If we don't have an input, then throw an engine error. */
		if( $input == FALSE )
		{
			return $this->engine_error( 'You need to input a URL or raw CSS data before we begin.', '$e = new CSSParser("http://www.domain.com/style.css");' );
		}
		
		/* @note: If the input is parse-able into a URL, we pull up the data. */
		if( count( parse_url($input) ) > 0 )
		{
			$this->css = @file_get_contents( $input );
		} else {
			$this->css = $input;
		}
		
		/* @note: If the $this->css is empty, then it can only be that the URL is not valid or is down. */
		if( empty($this->css) )
		{
			return $this->engine_error( 'We could not connect to the website address <b>' . $input . '</b>' );
		}
		
	}
	
	/* @note: The set_rule function enables other developers to implement custom rules throughout the decoder to allow more customisation in the future versions */
	public function set_rule($declaration, $value = 1)
	{
		$declaration = strstr('phpcss_', strtolower($declaration)) ? str_replace('phpcss_', '', strtolower($declaration)) : $declaration;
		define('phpcss_' . $declaration, $value);
		return;
	}
	
	/* @note: This enables configured rule set above ^^ to be pulled up in a semantic and orderly fashion.
		@note: It is set private because readability of this should only be in the code, and nowhere else. May change in the future. */
	private function rule($declaration)
	{
		return constant('phpcss_' . strtolower($declaration));
	}
	
	/* @note: We begin the decode process. The CSS is stored in $this->css so we don't need it as a parameter. */
	public function decode()
	{
		/* @note: This removes CSS comments, which will only clog up the parser. */
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
		
		/* @note: Let's begin breaking the CSS down into nested arrays, starting off with each declaration i.e. h1 { background:white; } */
		preg_match_all( '/(?ims)([a-z0-9\s\.\:#_\-@]+)\{([^\}]*)\}/', $this->css, $arr);
		foreach( $arr[0] as $i => $x )
		{
			$selector = trim( $arr[1][$i] );
			$rules = explode( ';', trim( $arr[2][$i] ) );
			$this->result[$selector] = array();

			/* @note: Each CSS is now split into child settings of each declaration */
			foreach( $rules as $strRule )
			{
				/* @note: If the element is empty, we don't need it. Carry on with the next one. */
				if( empty( $strRule ) ) { continue; }
				
				/*	@note: Background URL bug fix with malfunctional formatting */
				if(strstr($strRule, 'background:url'))
				{
					$this->tmp = $strRule . ';';
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
           
           		
           		/* @note: We are now breaking each element name with it's value into array values. */
				$rule = explode( ":", $strRule, 2 );
				$css_name = strtolower(trim($rule[0]));
				$css_value = strtolower(trim($rule[1]));
				
				/* @note: This formats the font-family into a child array format for easier processing */
				if($css_name == 'font-family')
				{
					$css_value = str_replace( array( '"', '\'' ), '', $css_value );

					foreach( explode(',', $css_value) as $strips )
					{
						$this->tmp[] = ucwords(trim($strips));
					}
						
					$css_value = $this->tmp;
					unset($this->tmp);
				}
					
					
				/* @note: This converts the colour into both HEX and RGB formats. */
				$i = FALSE;
					
				if($i = $this->format_rgb($css_value))
				{
					$css_value = array(
						'hex' => $this->rgb2hex($i['red'], $i['green'], $i['blue'], $i['opacity']),
						'rgb' => $this->format_rgb($css_value)
					);
				}
					
				if($i = $this->format_hex($css_value))
				{
					$css_value = array(
						'hex' => $this->format_hex($css_value),
						'rgb' => $this->hex2rgb($css_value)
					);
				}
					
				unset($i);
					
				/* @note: This formats the margin into the respective formats */
				if($this->is_value($css_name, 'margin|margin-left|margin-right|margin-top|margin-bottom'))
				{
						
						if($css_name == 'margin')
						{
							$itms = explode(' ', $css_value);
							
							if(count($itms) == 1)
							{
								$css_value = array();
								$css_value['top'] = trim($itms[0]);
								$css_value['right'] = trim($itms[0]);
								$css_value['bottom'] = trim($itms[0]);
								$css_value['left'] = trim($itms[0]);
							}
							
							else if(count($itms) == 2)
							{
								$css_value = array();
								$css_value['top'] = trim($itms[0]);
								$css_value['right'] = trim($itms[1]);
								$css_value['bottom'] = trim($itms[0]);
								$css_value['left'] = trim($itms[1]);
							}
							
							else if(count($itms) == 3)
							{
								$css_value = array();
								$css_value['top'] = trim($itms[0]);
								$css_value['right'] = trim($itms[1]);
								$css_value['bottom'] = trim($itms[2]);
								$css_value['left'] = trim($itms[1]);
							}
							
							else {
								$css_value = array();
								$css_value['top'] = trim($itms[0]);
								$css_value['right'] = trim($itms[1]);
								$css_value['bottom'] = trim($itms[2]);
								$css_value['left'] = trim($itms[3]);
							}
							
							unset($itms);
						}
						
						else {
							$this->result[ $selector ][]['margin'][ str_replace('margin-', '', $css_name) ] = $css_value;
							continue;
						}
						
					}
					
					/* @note: Ignore some moz and webkit specific CSS commands. They merely replicate standard CSS elements. */
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
					
					if($this->is_value($css_name, $array))
					{
						continue;
					}
					
					/* @note: This formats the dynamic layout of a background CSS attribute into the respective format */
					if($this->is_value($css_name, 'background'))
					{
						
						if($css_name == 'background')
						{
							$itms = explode(' ', $css_value);
							if(count($itms) == 0)
							{
								$this->result[ $selector ][]['background' ] = $css_value;
								break;
							}
							
							$original_css = $css_value;
							$css_value = array();
													
							foreach($itms as $itm)
							{
								$itm = trim($itm);
								
								if($this->is_value($itm, 'top|bottom|left|right|inherit') || substr_count($itm, 'px') > 0)
								{
									$css_value['position'][] = $itm;
								}
								
								if($this->is_value($itm, 'fixed|no-repeat|repeat'))
								{
									$css_value['repeat'] = $itm;
								}

								if(substr($itm, 0, 3) == 'url')
								{
									$this->tmp = array();
									$this->tmp = preg_replace('/url\((.+)\)/', '$1', trim($itm));
									$this->tmp = str_replace( array( '\'', '"' ), '', $this->tmp);
																		
									if( substr($this->tmp, 0, 5) != "data:" )
									{
										$css_value['url_raw'] = $this->tmp;
									}
									
									else {
									
										$css_value['url_raw'] = $this->tmp;
										preg_match('/data\:(.+)\;(.+),(.+)/', $this->tmp, $this->tmp);
									
										$css_value['url_data']['mime_type'] = $this->tmp[1];
										$css_value['url_data']['encode_type'] = strtoupper($this->tmp[2]);
										$css_value['url_data']['raw'] = $this->tmp[3];
										$css_value['url_data']['encoded'] = 0;
									
										switch( strtolower( $this->tmp[2] ) )
										{
											/* @note: This detects whether the type is base64, as most PHP servers have this built-in */
											case "base64" && ! $this->rule('disable_advanced_decoding'):
										
												/* @note: In the unlikely case that BASE64 isn't available, let's skip this process. */
												if( ! function_exists("base64_decode") ) { break; }
											
												/* @note: Alright, to get here, base64 is available, let's decode and let the script know we decoded! */
												$css_value['url_data']['encoded'] = 1;
												$css_value['url_data']['output'] = base64_decode( $this->tmp[3] );
												break;
										}
									
									}
									
								/* @note: We don't need the $this->tmp memory anymore! */
								unset($this->tmp);
								}
							}
							
							/* @note: If nothing's been achieved from this part of the decoder, let's revert the changes to avoid any future errors. */
							if(count($css_value) == 0 )
							{
								$css_value = $original_css;
							}							
							
							/* @note: We don't need the $itms variable anymore. Let's save some memory! */
							unset($itms);
						}
						
					}
					
					/* @note: This formats the padding into the respective formats */
					if($this->is_value($css_name, 'padding|padding-left|padding-right|padding-top|padding-bottom'))
					{
						
						if($css_name == 'padding')
						{
							$itms = explode(' ', $css_value);
							
							if(count($itms) == 1)
							{
								$css_value = array();
								$css_value['top'] = trim($itms[0]);
								$css_value['right'] = trim($itms[0]);
								$css_value['bottom'] = trim($itms[0]);
								$css_value['left'] = trim($itms[0]);
							}
							
							else if(count($itms) == 2)
							{
								$css_value = array();
								$css_value['top'] = trim($itms[0]);
								$css_value['right'] = trim($itms[1]);
								$css_value['bottom'] = trim($itms[0]);
								$css_value['left'] = trim($itms[1]);
							}
							
							else if(count($itms) == 3)
							{
								$css_value = array();
								$css_value['top'] = trim($itms[0]);
								$css_value['right'] = trim($itms[1]);
								$css_value['bottom'] = trim($itms[2]);
								$css_value['left'] = trim($itms[1]);
							}
							
							else {
								$css_value = array();
								$css_value['top'] = trim($itms[0]);
								$css_value['right'] = trim($itms[1]);
								$css_value['bottom'] = trim($itms[2]);
								$css_value['left'] = trim($itms[3]);
							}
							
							unset($itms);
						}
						
						else {
							$this->result[ $selector ][]['padding'][ str_replace('padding-', '', $css_name) ] = $css_value;
							continue;
						}
						
					}
					
					/* @note: This formats the border-color into the respective format */
					if($this->is_value($css_name, 'border-color|border-color-top|border-color-right|border-color-bottom|border-color-left'))
					{
						
						if($css_name == 'border-color')
						{
							$itms = explode(' ', $css_value);
							
							if(count($itms) == 1)
							{
								$css_value = array();
								$css_value['top'] = trim($itms[0]);
								$css_value['right'] = trim($itms[0]);
								$css_value['bottom'] = trim($itms[0]);
								$css_value['left'] = trim($itms[0]);
							}
							
							else if(count($itms) == 2)
							{
								$css_value = array();
								$css_value['top'] = trim($itms[0]);
								$css_value['right'] = trim($itms[1]);
								$css_value['bottom'] = trim($itms[0]);
								$css_value['left'] = trim($itms[1]);
							}
							
							else if(count($itms) == 3)
							{
								$css_value = array();
								$css_value['top'] = trim($itms[0]);
								$css_value['right'] = trim($itms[1]);
								$css_value['bottom'] = trim($itms[2]);
								$css_value['left'] = trim($itms[1]);
							}
							
							else {
								$css_value = array();
								$css_value['top'] = trim($itms[0]);
								$css_value['right'] = trim($itms[1]);
								$css_value['bottom'] = trim($itms[2]);
								$css_value['left'] = trim($itms[3]);
							}
							
							unset($itms);
						}
						
						else {
							$this->result[ $selector ][]['border-color'][ str_replace('border-color-', '', $css_name) ] = $css_value;
							continue;
						}
						
					}
					
					/* @note: This formats the border into the respective formats */
					if($this->is_value($css_name, 'border|border-top|border-bottom|border-right|border-left'))
					{
						
						$itms = explode(' ', $css_value);
							
						if(count($itms) == 1)
						{
							$css_value = array();
							$css_value['size'] = trim($itms[0]);
							$css_value['type'] = 'solid';
							$css_value['color'] = 'inherit';
						}
						
						else if(count($itms) == 2)
						{
							$css_value = array();
							$css_value['size'] = trim($itms[0]);
							$css_value['type'] = trim($itms[1]);
							$css_value['color'] = 'inherit';
						}
						
						else {
							$css_value = array();
							$css_value['size'] = trim($itms[0]);
							$css_value['type'] = trim($itms[1]);
							$css_value['color'] = trim($itms[2]);
						}
							
						unset($itms);
						
						if($css_name == 'border')
						{
							$this->result[ $selector ][]['border'][ 'top' ] = $css_value;
							$this->result[ $selector ][]['border'][ 'right' ] = $css_value;
							$this->result[ $selector ][]['border'][ 'bottom' ] = $css_value;
							$this->result[ $selector ][]['border'][ 'left' ] = $css_value;
						}
						
						else {
							$this->result[ $selector ][]['border'][ str_replace('border-', '', $css_name) ] = $css_value;
						}
						
						
						continue;
						
					}
					
					/* @note: Changes background-color/background-image to background (to save space) */
					if($this->is_value($css_name, 'background-color|background-image'))
					{
						$css_name = 'background';
					}
				
				$this->result[ $selector ][][ $css_name ] = $css_value;
			
			}
		}
		
		/* @note: Let's empty the $this->tmp variable */
		unset($this->tmp);
		
		/* @note: This supports the 'disable_organise' rule that stops it sorting into deeply nested arrays. */
		if($this->rule('disable_organise'))
		{
			return $this->result;
		}
		
		/* @note: This will only be executed if any above return statements are fired. */
		return $this->organize($this->result);
	}
	
	
	/*
		@name Miscellaneous
		@description This area involves the error reporting system that is fully customisable in a central manner, and will contain any code that does not directly relate to the purpose of the CSSParser.
		@author Bilawal Hameed [http://www.bilawal.co.uk/]
		@created 2nd January 2012
		@updated 2nd January 2012
	*/
	
	/* @note: This is our built-in array organiser that allows deeply nested arrays for more readability, at the cost of CPU. */
	private function organize($array)
	{
		foreach($array as $k=>$v)
		{
			$ke = explode(" ", $k);
			if(count($ke) == 0)
			{
				continue;
			}
			
			$php = "";
			foreach($ke as $elem)
			{
				if($php != "" && ! is_numeric($elem))
				{
					$php .= "['childs']";
				}
				$php .= "['".$elem."']";
			}
			
			/* @note: This generates PHP that creates nested arrays. I couldn't think of any better ones, so if you can, please contribute on github. */
			eval ("\$this->array_final".$php." = \$v;");
		}
		
		return $this->array_final;
	}
	
	/* @note: This function is dedicated to formatting hexadecimal values and identifying if they're in a valid format. */
	private function format_hex($value)
	{
		
		if(substr($value, 0, 1) == "#" && $this->is_value(strlen($value), '3|4|7'))
		{
			$value = substr($value, 1, 7);
		} else {
			return FALSE;
		}
		
		if(strlen($value) == 3)
		{
			$value .= $value;
		}
		
		if(strlen($value) == 2)
		{
			$value .= $value . $value;
		}
		
		foreach(str_split($value, 2) as $elem)
		{
				$val[] = strtoupper($elem);
		}
		
		return array(
			'raw' => '#' . strtoupper($value),
			'segments' => $val
		);
	}
	
	/* @note: This function is dedicated to doing as format_hex does above, just in the RGB (Red, Green, Blue) format. */
	private function format_rgb($value)
	{
		if(substr($value, 0, 4) == 'rgb(' || substr($value, 0, 5) == 'rgba(')
		{
			preg_match('/(|.+)rgb(a)\((.+)\)(|.+)/', $value, $regex);
			
			if(substr_count($regex[3], ',') > 0)
			{
				$v = array();
				foreach(explode(',', trim($regex[3])) as $element)
				{
					$v[] = trim($element);
				}
				
				return array(
					'raw' => trim($regex[3]),
					'red' => $v[0],
					'green' => $v[1],
					'blue' => $v[2],
					'opacity' => ($v[3]) ? $v[3] : 1
				);
			}
			
		}
		
		return FALSE;
	}
	
	/* @note: This converts hexadecimal color codes into their RGB counterpart. */
	private function hex2rgb($value)
	{
		
		$v = $this->format_hex($value);
		
		if($v)
		{
			$red = hexdec($v['segments'][0]);
			$green = hexdec($v['segments'][1]);
			$blue = hexdec($v['segments'][2]);
				
			return array(
				'raw' => implode(', ', array( $red, $green, $blue, '1.0' )),
				'red' => $red,
				'green' => $green,
				'blue' => $blue,
				'opacity' => 1
			);
		}
		
		return FALSE;
	}
	
	/* @note: This is the reverse as hex2rgb() however it requires three parameters = r, g, b
		@note: [$opacity] is useless at the moment as HEX does not support this. I added it to support future CSS releases, or if anyone has any workarounds. */
	private function rgb2hex($num1, $num2, $num3, $opacity = 1)
	{
		
		if(abs($num1) <= 255 && abs($num2) <= 255 && abs($num3) <= 255)
		{
			$return = dechex($num1);
			$return .= dechex($num2);
			$return .= dechex($num3);
			
			if(strlen($return) != 6)
			{
				return FALSE;
			}
			
			return '#' . strtoupper($return);
		}
		
				
		return FALSE;
	}
	
	/* @note: This detects whether a value matches many of the values in [$options] - it supports arrays and | dividers. Designed to make the code look more semantic. */
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
	
	/* @note: This throws a custom error that allows developers to customise, or link in with their current frameworks. The styling is used to help the error be identified as this engine. Most likely to be removed in future versions, to support relative errors in PHP using trigger_error() */ 
	private function engine_error( $err, $suggested_code = FALSE )
	{
		/* @note: Display the code error */
		echo "<div style='font-family:arial, verdana; font-size:14px; background:#eee; padding:15px; line-height:24px;'>
					<p style='margin:0px; font-weight:bold; font-size:18px;'>CSSParser Error</p>
					<p style='margin:0px;'>" . $err . "</p>";
				
				if( $suggested_code)
				{
					echo "<br /><span style='display:block; background:#ddd; padding:6px;'><p style='margin:0px;'><b>The code needs to be as displayed below:</b></p><code>" . $suggested_code . "</code></span>";
				}	
		
		echo "</div>";
		return;
	}

}


$e = new phpCSS();
/* --  End of file: phpCSS.php -- */