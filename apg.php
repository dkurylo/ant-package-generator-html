<?php
	//error_reporting(E_ALL);
	//var_dump($_SERVER);
	$post_data = $_POST['data'];
	$post_action = $_POST['action'];
	$post_name = $_POST['name'];
	$post_password = $_POST['password'];

	$profilesdir = '';
	$profilesfilename = 'apg.json';
	$path = '.';
	$nocache = 0;

	if( $post_action == 'read_names' ) {
		$profilenames = array();
		$filename = $profilesdir.$profilesfilename;
		if( file_exists( $filename ) ) {
			$profileitem = json_decode( file_get_contents( $filename ), true );
			foreach( $profileitem AS $profileindex => $profilevalue ) {
				foreach( $profilevalue AS $key => $value ) {
					if( !is_array($value) && $key == "name" ) {
						array_push( $profilenames, $value );
						break;
					}
				}
			}
		}
		returnResult( json_encode( $profilenames ), false );

	} else if( $post_action == 'read_profile' ) {
		if( empty( $post_name ) ) {
			returnResult( 'Provide the profile name', true );
			return;
		}

		$profilereturn = new ProfileOut();
		$profileitemfound = false;
		$filename = $profilesdir.$profilesfilename;
		if( file_exists( $filename ) ) {
			$profileitem = json_decode( file_get_contents( $filename ), true );
			foreach( $profileitem AS $profileindex => $profilevalue ) {
				foreach( $profilevalue AS $key => $value ) {
					if( !is_array($value) && $key == "name" ) {
						if( $value == $post_name ) {
							$profileitemfound = true;
						}
						$profilereturn->name = $value;
					//} else if( !is_array($value) && $key == "password" ) {
					//	$profilereturn->password = "";//$value;
					} else if( is_array($value) && $key == "profiles" ) {
						$profilereturn->profiles = $value;
					}
				}
				if( $profileitemfound == true ) {
					break;
				} else {
					$profilereturn = new ProfileOut();
				}
			}
		}
		if( $profileitemfound == true ) {
			returnResult( json_encode( $profilereturn ), false );
		} else {
			returnResult( 'No profile with such name', true );
		}

	} else if( $post_action == 'write_profile' ) {
		if( empty( $post_name ) ) {
			returnResult( 'Provide the profile name', true );
			return;
		}
		if( empty( $post_data ) || $post_data == '' || $post_data == '[]' ) {
			returnResult( 'Nothing to save', true );
			return;
		}

		$filename = $profilesdir.$profilesfilename;
		$oldprofilewrappers = array();
		$newprofilewrappers = array();
		$profileitemfound = false;
		$readonly = false;
		$passwordbad = false;

		if( file_exists( $filename ) ) {
			$profileitem = json_decode( file_get_contents( $filename ), true );
			foreach( $profileitem AS $profileindex => $profilevalue ) {
				$profileitemwrapper = new ProfileIn();
				foreach( $profilevalue AS $key => $value ) {
					if( !is_array($value) && $key == "name" ) {
						$profileitemwrapper->name = $value;
					} else if( !is_array($value) && $key == "password" ) {
						$profileitemwrapper->password = $value;
					} else if( !is_array($value) && $key == "readonly" ) {
						$profileitemwrapper->readonly = $value;
					} else if( is_array($value) && $key == "profiles" ) {
						$profileitemwrapper->profiles = $value;
					}
				}
				array_push( $oldprofilewrappers, $profileitemwrapper );
			}
		}

		$newprofilewrapper = new ProfileIn();
		$newprofilewrapper->name = $post_name;
		$newprofilewrapper->password = $post_password;
		$newprofilewrapper->readonly = "false";
		$newprofilewrapper->profiles = json_decode( str_replace( '\\"', '"', $post_data ) );

		foreach( $oldprofilewrappers AS $profileitemwrapper ) {
			if( $profileitemwrapper->name == $newprofilewrapper->name ) {
				$profileitemfound = true;
				if( $profileitemwrapper->readonly == "true" ) {
					$readonly = true;
					break;
				}
				if( $profileitemwrapper->password != $newprofilewrapper->password ) {
					$passwordbad = true;
					break;
				}
				array_push( $newprofilewrappers, $newprofilewrapper );
			} else {
				array_push( $newprofilewrappers, $profileitemwrapper );
			}
		}

		if( $readonly == true ) {
			returnResult( 'This profile is read only', true );
			return;
		}
		if( $passwordbad == true ) {
			returnResult( 'Wrong password provided', true );
			return;
		}

		if( $profileitemfound == false ) {
			array_push( $newprofilewrappers, $newprofilewrapper );
		}
		
		$handle = fopen( $filename, "w" );
		fwrite( $handle, pretty_json( json_encode( $newprofilewrappers ) ) );
		fclose( $handle );
		returnResult( 'OK', false );

	} else if( $post_action == 'remove_profile' ) {
		if( empty( $post_name ) ) {
			returnResult( 'Provide the profile name', true );
			return;
		}

		$filename = $profilesdir.$profilesfilename;
		$oldprofilewrappers = array();
		$newprofilewrappers = array();
		$profileitemfound = false;
		$readonly = false;
		$passwordbad = false;

		if( file_exists( $filename ) ) {
			$profileitem = json_decode( file_get_contents( $filename ), true );
			foreach( $profileitem AS $profileindex => $profilevalue ) {
				$profileitemwrapper = new ProfileIn();
				foreach( $profilevalue AS $key => $value ) {
					if( !is_array($value) && $key == "name" ) {
						if( $value == $post_name ) {
							$profileitemfound = true;
						}
						$profileitemwrapper->name = $value;
					} else if( !is_array($value) && $key == "password" ) {
						$profileitemwrapper->password = $value;
					} else if( !is_array($value) && $key == "readonly" ) {
						$profileitemwrapper->readonly = $value;
					} else if( is_array($value) && $key == "profiles" ) {
						$profileitemwrapper->profiles = $value;
					}
				}
				array_push( $oldprofilewrappers, $profileitemwrapper );
			}
		}

		if( $profileitemfound == false ) {
			returnResult( 'No profile with such name', true );
			return;
		}

		$delprofilewrapper = new ProfileIn();
		$delprofilewrapper->name = $post_name;
		$delprofilewrapper->password = $post_password;

		foreach( $oldprofilewrappers AS $profileitemwrapper ) {
			if( $profileitemwrapper->name == $delprofilewrapper->name ) {
				if( $profileitemwrapper->readonly == "true" ) {
					$readonly = true;
					break;
				}
				if( $profileitemwrapper->password != $delprofilewrapper->password ) {
					$passwordbad = true;
					break;
				}
			} else {
				array_push( $newprofilewrappers, $profileitemwrapper );
			}
		}

		if( $readonly == true ) {
			returnResult( 'This profile is read only', true );
			return;
		}
		if( $passwordbad == true ) {
			returnResult( 'Wrong password provided', true );
			return;
		}

		$handle = fopen( $filename, "w" );
		fwrite( $handle, pretty_json( json_encode( $newprofilewrappers ) ) );
		fclose( $handle );
		returnResult( 'OK', false );
	}

	class ProfileIn {
		public $name = "";
		public $password = "";
		public $readonly = "";
		public $profiles = array();
	}

	class ProfileOut {
		public $name = "";
		public $profiles = array();
	}

	class Response {
		public $message = "Message";
		public $result = "ERROR";
	}

	function returnResult( $message, $isError ) {
		$response = new Response();
		$response->message = $message;
		$response->result = $isError != true ? "SUCCESS" : "ERROR";
		echo( json_encode( $response ) );
	}

	function pretty_json( $json ) {
		$result = '';
		$pos = 0;
		$strLen = strlen($json);
		$indentStr = "\t";
		$newLine = "\n";
		$prevChar = '';
		$outOfQuotes = true;

		for( $i = 0; $i <= $strLen; $i++ ) {
			$char = substr( $json, $i, 1 );
			if( $char == '"' && $prevChar != '\\' ) {
				$outOfQuotes = !$outOfQuotes;
			} else if( ( $char == '}' || $char == ']' ) && $outOfQuotes ) {
				$result .= $newLine;
				$pos--;
				for( $j = 0; $j < $pos; $j++ ) {
					$result .= $indentStr;
				}
			}
			if( ( $char == ':' ) && $outOfQuotes ) {
				$result .= " ".$char." ";
			} else {
				$result .= $char;
			}
			if( ( $char == ',' || $char == '{' || $char == '[' ) && $outOfQuotes ) {
				$result .= $newLine;
				if( $char == '{' || $char == '[' ) {
					$pos++;
				}
				for( $j = 0; $j < $pos; $j++ ) {
					$result .= $indentStr;
				}
			}
			$prevChar = $char;
		}
		return $result;
	}
?>