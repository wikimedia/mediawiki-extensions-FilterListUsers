<?php
/**
 * FilterListUsers -- filters out users that haven't edited from Special:ListUsers
 *
 * @file
 * @ingroup Extensions
 * @author Jack Phoenix
 * @license GPL-2.0-or-later
 */
class FilterListUsers {
	/**
	 * Alters the SQL query so that when there is no "showall" parameter in the
	 * URL or when the user isn't privileged, only users with 5 (or more) edits
	 * will be shown.
	 *
	 * @param UsersPager $usersPager
	 * @param array &$query SQL query parameters
	 * @return bool
	 */
	public static function onSpecialListusersQueryInfo( $usersPager, &$query ) {
		global $wgRequest, $wgFilterListUsersMinimumEdits, $wgFilterListUsersExemptGroups;

		// Members of these groups will always be shown if the user selects this
		// group from the dropdown menu, no matter if they haven't edited the wiki
		// at all

		if (
			!$wgRequest->getVal( 'showall' ) &&
			!in_array( $usersPager->requestedGroup, $wgFilterListUsersExemptGroups ) ||
			!$usersPager->getUser()->isAllowed( 'viewallusers' ) &&
			!in_array( $usersPager->requestedGroup, $wgFilterListUsersExemptGroups )
		) {
			$dbr = wfGetDB( DB_REPLICA );
			$query['tables'][] = 'revision';
			$query['fields'] = array_merge( $query['fields'], [ 'rev_user', 'COUNT(*) AS cnt' ] );
			$query['options']['GROUP BY'] = 'rev_user';
			$query['options']['HAVING'] = 'cnt > ' . $wgFilterListUsersMinimumEdits;
			$query['join_conds']['revision'] = [ 'JOIN', 'user_id = rev_user' ];
		}

		return true;
	}

	/**
	 * Adds the "Show all users" checkbox for privileged users.
	 *
	 * @param UsersPager $usersPager
	 * @param string &$out HTML output
	 * @return bool
	 */
	public static function onSpecialListusersHeaderForm( $usersPager, &$out ) {
		global $wgRequest;

		// Show this checkbox only to privileged users
		if ( $usersPager->getUser()->isAllowed( 'viewallusers' ) ) {
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
}
