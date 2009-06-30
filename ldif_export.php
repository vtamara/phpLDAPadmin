<?php 

/*
 * ldif_export.php
 * Dumps the LDIF file for a given DN
 *
 * Variables that come in as GET vars:
 *  - dn (rawurlencoded)
 *  - server_id
 *  - format (one of 'win', 'unix', 'mac'
 *  - scope (one of 'sub', 'base', or 'one')
 */

require 'config.php';
require_once 'functions.php';

$dn = stripslashes( rawurldecode( $_GET['dn'] ) );
$server_id = $_GET['server_id'];
$format = $_GET['format'];
$scope = $_GET['scope'] ? $_GET['scope'] : 'base';

check_server_id( $server_id ) or pla_error( "Bad server_id: " . htmlspecialchars( $server_id ) );
have_auth_info( $server_id ) or pla_error( "Not enough information to login to server. Please check your configuration." );

$objects = pla_ldap_search( $server_id, 'objectClass=*', $dn, array(), $scope, false );

//echo "<pre>";
//print_r( $objects );
//exit;

$rdn = get_rdn( $dn );

switch( $format ) {
	case 'win': 	$br = "\r\n"; break;
	case 'mac': 	$br = "\r"; break;
	case 'unix': 
	default:	$br = "\n"; break;
}
		
if( ! $objects )
	pla_error( "Search on dn (" . htmlspecialchars($dn) . ") came back empty" );

header( "Content-type: text/plain" );
header( "Content-disposition: attachment; filename=\"$rdn.ldif\"" );
header( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" ); 
header( "Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT" ); 
header( "Cache-Control: post-check=0, pre-check=0", false );
header( "Pragma: no-cache" );

echo "version: 1$br$br";
echo "# LDIF Export for: $rdn$br";
echo "# Scope: $scope, " . count( $objects ) . " entries$br";
echo "# Generated by phpLDAPAdmin on " . date("F j, Y g:i a") . "$br";
echo $br;

foreach( $objects as $dn => $attrs )
{
	unset( $attrs['dn'] );
	unset( $attrs['count'] );

	if( is_safe_ascii( $dn ) )
		echo "dn: $dn$br";
	else
		echo "dn:: " . base64_encode( $dn ) . $br;

	foreach( $attrs as $attr => $val ) {
		if( is_array( $val ) ) {
			foreach( $val as $v ) {
				if( is_safe_ascii( $v ) ) {
					echo "$attr: $v$br";
				} else {
					echo "$attr:: " . base64_encode( $v ) . $br;
				}
			}
		} else {
			$v = $val;
			if( is_safe_ascii( $v ) ) {
				echo "$attr: $v$br";
			} else {
				echo "$attr:: " . base64_encode( $v ) . $br;
			}
		}
	}
	echo $br;
}

function is_safe_ascii( $str )
{
	for( $i=0; $i<strlen($str); $i++ )
		if( ord( $str{$i} ) < 32 || ord( $str{$i} ) > 127 )
			return false;
	return true;
}
