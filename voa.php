<?php
/*
------------------------------------------------------------------------------------------------------
Author: Edin Beslagic (ebeslagic@voanews.com)
Created: 2010-07-08
Modified: 2011-09-28
------------------------------------------------------------------------------------------------------
SQL helper function, riding on top of Smarty. Takes SQL input as sprintf() parameters, and sanitizes
any parameters. For example:

$result = $VOA->query(
	"select * from `table` where `something`='%s'",			// this parameter gets sanitized automatically
	$_GET['id'],											// with mysql_real_escape_string()
	array(
		"index" => "something"								// query options, see below
	)														// array can be placed in any order
);														

------------------------------------------------------------------------------------------------------
override smarty version					define( "VOA_SMARTY_VERSION",	'Smarty-3.1.1' );
standard mysql credentials:				define( "VOA_DATABASE__USER", 	"username" );
	"		"		"					define( "VOA_DATABASE__PASS", 	"password" );
to select database after connect:		define( "VOA_SELECT_DATABASE", 	"example_db" );
to prevent auto-declaration of $VOA:	define( "VOA_NO_AUTODECLARE", 	false );
to prevent mysql auto-connect:			define( "VOA_NO_AUTOCONNECT", 	false );
to prevent GPC check/stripslashes:		define( "VOA_NO_MAGICQUOTES", 	false );
die if can't connect to database:		define( "VOA_CONNECT_CHECK",	true );
------------------------------------------------------------------------------------------------------
$VOA->sticky_options	- array to prepopulate each query with - be careful with noescape

options array:
	"nofetch" 			- returns mysql_query() instead of array
	"noempty"			- if no sql results, returns empty array() instead of false
	"object" 			- returns as object, or array of objects instead of array / array of arrays
	"flat" 				- returns a single sql row, as either object or array
	"index" => "key" 	- indexes by a sql key
	"deep" 				- indexes by key, then places contents in unindexed [] array
	"index2" => "key2" 	- indexes deep structure by a sql key #2
	"noescape" 			- bypasses sanitization (mysql_real_escape_string) in parsing '%s' items
						- if using this option, make sure to always sanitize any user input
	"debug"				- keeps track of all queries in $queries, with timing before and after:
						- array( "sql", "start" => microtime(), "stop" => microtime() )
------------------------------------------------------------------------------------------------------
*/

if( !defined( 'VOA_SMARTY_VERSION' ) ) {
	define( 'VOA_SMARTY_VERSION', 'Smarty-3.1.1' );
}
require_once( VOA_SMARTY_VERSION . '/libs/Smarty.class.php' );

function pre($a, $die = true, $var_name = null) {
	echo "<PRE>";
	print_r( $a );
	echo "</PRE>";
	if( $die === true ) {
		die;
	}
}

function smarty_modifier_ago($tm, $cur_tm = null) {
	if( is_null($cur_tm) ) $cur_tm = time();
	$dif = $cur_tm-strtotime($tm);
	
	$pds = array('second','minute','hour','day','week','month','year','decade');
	$lngh = array(1,60,3600,86400,604800,2630880,31570560,315705600);
	for($v = sizeof($lngh)-1; ($v >= 0)&&(($no = $dif/$lngh[$v])<=1); $v--);
	if($v < 0) $v = 0;
	$_tm = $cur_tm-($dif%$lngh[$v]);
	$no = floor($no); if($no <> 1) $pds[$v] .='s'; $x=sprintf("%d %s ",$no,$pds[$v]);
	
	return $x;
}

class VOA extends Smarty {

	var $sql = '';
	var $sql_history = array();
	var $error = '';
	var $link = null;
	var $sql_username = VOA_DATABASE__USER;
	var $sql_password = VOA_DATABASE__PASS;
	var $sql_server = VOA_DATABASE__HOST;
	var $sql_database = null;
	var $t = null;
	var $options = array();
	var $sticky_options = array();
	var $queries = array();

	function recursive_stripslashes($a) {
		$b = array();
		foreach( $a as $k => $v ) {
			$k = stripslashes($k);
			if( is_array($v) ) {
				$b[$k] = $this->recursive_stripslashes($v);
			} else {
				$b[$k] = stripslashes($v);
			}
		}
		return($b);
	}
	
	function check_magic_quotes() {
		if( get_magic_quotes_gpc() ) {
			$_POST = $this->recursive_stripslashes( $_POST );
			$_GET = $this->recursive_stripslashes( $_GET );
		}
	}
	
	function connect() {
		$this->link = @mysql_connect( $this->sql_server, $this->sql_username, $this->sql_password, true );
		if( !$this->link ) {
			$this->error = mysql_error();
			return( false );
		}
		if( defined('VOA_SELECT_DATABASE') ) {
			if( !mysql_select_db( VOA_SELECT_DATABASE, $this->link ) ) {
				if( defined( 'VOA_CONNECT_CHECK' ) ) {
					die( mysql_error() );
				}
			}
		}
		return( true );
	}
	
	function query_fetch_reduce($a) {
		if( isset($this->options["index"]) && isset($this->options["collapse"]) ) {
			$a = $a[$this->options["collapse"]];
		}
		return( $a );
	}
	
	function query_fetch() {
		if( in_array("object", $this->options ) ) {
			return( mysql_fetch_object( $this->t ) );
		} else {
			return( mysql_fetch_assoc( $this->t ) );
		}
	}
	
	function stop_debug() {
		if( in_array( "debug", $this->options ) ) {
			$de = count($this->queries) - 1;
			$this->queries[$de]['stop'] = microtime();
			#$diff = $this->queries[$de]['stop'] - $this->queries[$de]['start'];
			
			$this->queries[$de]['diff'] = 
				(substr($this->queries[$de]['stop'],11)-substr($this->queries[$de]['start'],11))
				+(substr($this->queries[$de]['stop'],0,9)-substr($this->queries[$de]['start'],0,9));
		}
	}
	
	function query() {
		// be careful with global options
		$this->options = $this->sticky_options;
		
		$c = func_num_args();
		if( $c === 0 ) return( false );
		
		/* generate SQL line, escape if needed, apply any given options */
		if( $c === 1 ) {
			$this->sql = func_get_arg(0);
		} else {
			$a = array();
			for( $i = 0; $i < $c; $i++ ) {
				if( is_array( func_get_arg($i) ) ) {
					$this->options = array_merge( $this->options, func_get_arg($i) );
				} else {
					$a[] = func_get_arg($i);
				}
			}
			$b = array_shift($a);
			if( !in_array( "noescape", $this->options ) ) {
				foreach( $a as $k => $v ) {
					$a[$k] = mysql_real_escape_string( $v );
				}
			}
			$this->sql = @vsprintf( $b, $a );
		}

		/* one start, many stops */
		if( in_array( "debug", $this->options ) ) {
			$this->queries[] = array("sql" => $this->sql, "start" => microtime());
		}
		
		/* return directly instead of returning contents as array */
		$this->t = @mysql_query( $this->sql );
		if( in_array( "nofetch", $this->options ) ) {
			$this->stop_debug();
			
			if( !$this->t ) {
				$this->error = mysql_error();
				return( false );
			} else {
				return( $this->t );
			}
		}
		
		/* empty? */
		if( @mysql_num_rows( $this->t ) == 0 ) {
			$this->stop_debug();
			if( in_array( "noempty", $this->options ) ) {
				return( array() );
			} else {
				return( false );
			}
		}
		
		/* single line */
		if( in_array( "flat", $this->options ) ) {
			$retval = $this->query_fetch();
			$this->stop_debug();
			return( $retval );
		}
		
		/* else, return array - possibly indexed */
		$retarr = array();
		while( $r = $this->query_fetch() ) {
			if( !isset( $this->options['index'] ) ) {
				$retarr[] = $r;
			} else {
				if( in_array( "deep", $this->options ) ) {
					if( is_object($r) ) {
						if( isset( $this->options["index2"] ) ) {
						
							// fixme: never did the object part
						
						} else {
							$key = $r->{$this->options['index']};
							$retarr[$key][] = $this->query_fetch_reduce($r);
						}
					} else {
						if( isset( $this->options["index2"] ) ) {
							$key = $r[$this->options['index']];
							$key2 = $r[$this->options['index2']];
							
							$retarr[$key][$key2] = $this->query_fetch_reduce($r);
						} else {
							$key = $r[$this->options['index']];
							$retarr[$key][] = $this->query_fetch_reduce($r);
						}
					}
				} else {
					if( is_object($r) ) {
						$key = $r->{$this->options['index']};
						$retarr[$key] = $this->query_fetch_reduce($r);
					} else {
						$key = $r[$this->options['index']];
						$retarr[$key] = $this->query_fetch_reduce($r);
					}
				}
			}
		}
		$this->stop_debug();
		return( $retarr );
	}
}

if( !function_exists( 'smarty_block_rewrite' ) ) {

function smarty_block_rewrite($parms, $content, &$smarty, &$repeat ) {

	if( $repeat ) {
	
		$get = $_GET;
		
		$delim = ",";
		if( isset( $parms['delimiter'] ) ) {
			$delim = $parms['delimiter'];
			unset( $parms['delimiter'] );
		}
		
		foreach( $parms as $k => $v ) {
			if( $k == 'erase' ) {
				$erase = explode($delim, $v);
				foreach( $erase as $to_erase ) {
					if( isset( $get[$to_erase] ) ) unset( $get[$to_erase] );
				}
			} elseif( $k == 'toggle' ) {
				$toggle = explode($delim, $v);
				foreach( $toggle as $to_toggle ) {

					// only toggle if sort = country ?
					if( $get['sort'] == $parms['sort'] ) {
						if( isset( $get[$to_toggle] ) ) {
							unset( $get[$to_toggle] );
						} else {
							$get[$to_toggle] = '';
						}
					}
				}
			} elseif( $k == 'inject' ) {
				// for variable keys
				$inject = $v;
			} else {
				// ignore key as worthless
				if( isset( $inject ) ) {
					$get[$inject] = $v;
				} else {
					$get[$k] = $v;
				}
			}
		}

		$ret = http_build_query( $get );
		$ret = str_replace( "=&", "&", $ret );
		if( substr($ret,-1) == "=" ) $ret = substr($ret,0,-1);
		
		return( $ret );
	
	}
}

} # end if function smarty_block_rewrite exists

if( !defined( "VOA_NO_AUTODECLARE" ) ) {
	$VOA = new VOA();
	$VOA->compile_id = $_SERVER['HTTP_HOST'];
	if( !defined( "VOA_NO_AUTOCONNECT" ) ) {
		if( !$VOA->connect() ) {
			if( defined( "VOA_CONNECT_CHECK" ) ) {
				die( mysql_error() );
			}
		}
	}
	if( !defined( 'VOA_NO_MAGICQUOTES' ) ) {
		$VOA->check_magic_quotes();
	}
}
