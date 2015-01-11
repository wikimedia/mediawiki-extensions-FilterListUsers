<?php
/**
 * FilterListUsers -- filters out users that haven't edited from Special:ListUsers
 *
 * @file
 * @ingroup Extensions
 * @date 4 January 2015
 * @author Jack Phoenix <jack@countervandalism.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

// Extension credits that will show up on Special:Version
$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'FilterListUsers',
	'version' => '1.3',
	'author' => 'Jack Phoenix',
	'descriptionmsg' => 'filterlistusers-desc',
	'url' => 'https://www.mediawiki.org/wiki/Extension:FilterListUsers',
);

// New user right, required to view all users in Special:ListUsers
$wgAvailableRights[] = 'viewallusers';
$wgGroupPermissions['sysop']['viewallusers'] = true;

// i18n files
$wgMessagesDirs['FilterListUsers'] = __DIR__ . '/i18n';

$wgHooks['SpecialListusersQueryInfo'][] = 'efFilterListUsersAlterQuery';
/**
 * Alters the SQL query so that when there is no "showall" parameter in the URL
 * or when the user isn't privileged, only users with 5 (or more) edits will be
 * shown.
 *
 * @param UsersPager $usersPager
 * @param array $query SQL query parameters
 * @return bool
 */
function efFilterListUsersAlterQuery( $usersPager, &$query ) {
	global $wgRequest, $wgUser;

	// Members of these groups will always be shown if the user selects this
	// group from the dropdown menu, no matter if they haven't edited the wiki
	// at all
	$exemptGroups = array(
		'sysop', 'bureaucrat', 'steward', 'staff', 'globalbot'
	);

	if (
		!$wgRequest->getVal( 'showall' ) && !in_array( $usersPager->requestedGroup, $exemptGroups ) ||
		!$wgUser->isAllowed( 'viewallusers' ) && !in_array( $usersPager->requestedGroup, $exemptGroups )
	)
	{
		$dbr = wfGetDB( DB_SLAVE );
		$query['tables'][] = 'revision';
		$query['fields'] = ( array_merge( $query['fields'], array( 'rev_user', 'COUNT(*) AS cnt' ) ) );
		$query['options']['GROUP BY'] = 'rev_user';
		$query['options']['HAVING'] = 'cnt > 5';
		$query['join_conds']['revision'] = array( 'JOIN', 'user_id = rev_user' );
	}

	return true;
}

$wgHooks['SpecialListusersHeaderForm'][] = 'efFilterListUsersHeaderForm';
/**
 * Adds the "Show all users" checkbox for privileged users.
 *
 * @param UsersPager $usersPager
 * @param string $out HTML output
 * @return bool
 */
function efFilterListUsersHeaderForm( $usersPager, &$out ) {
	global $wgRequest, $wgUser;

	// Show this checkbox only to privileged users
	if ( $wgUser->isAllowed( 'viewallusers' ) ) {
		$out .= Xml::checkLabel(
			wfMessage( 'listusers-showall' )->plain(),
			'showall',
			'showall',
			$wgRequest->getVal( 'showall' )
		);
		$out .= '&nbsp;';
	}

	return true;
}