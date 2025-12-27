<?php

use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;

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
	 */
	public static function onSpecialListusersQueryInfo( $usersPager, &$query ) {
		global $wgFilterListUsersMinimumEdits, $wgFilterListUsersExemptGroups;

		// phpcs:ignore Generic.Files.LineLength.TooLong
		$requestedGroup = isset( $usersPager->getDefaultQuery()['group'] ) ? $usersPager->getDefaultQuery()['group'] : false;
		// Members of these groups will always be shown if the user selects this
		// group from the dropdown menu, no matter if they haven't edited the wiki
		// at all
		$isNotExempt = !in_array( $requestedGroup, $wgFilterListUsersExemptGroups );
		if (
			( !$usersPager->getRequest()->getVal( 'showall' ) && $isNotExempt ) ||
			( !$usersPager->getUser()->isAllowed( 'viewallusers' ) && $isNotExempt )
		) {
			$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
			// ORDER IS SUPER IMPORTANT HERE! Get these in the wrong order and the query breaks
			$query['tables'][] = 'actor';
			$query['tables'][] = 'revision';
			$query['fields'] = array_merge( $query['fields'], [ 'rev_actor', 'COUNT(*) AS cnt' ] );
			// user_name in GROUP BY is needed for ONLY_FULL_GROUP_BY compliance; WMF CI at least
			// seems to use ONLY_FULL_GROUP_BY
			$query['options']['GROUP BY'] = 'rev_actor, user_name';
			$query['options']['HAVING'] = 'cnt > ' . $wgFilterListUsersMinimumEdits;
			$query['join_conds']['actor'] = [ 'LEFT JOIN', 'actor_user = user_id' ];
			$query['join_conds']['revision'] = [ 'JOIN', 'rev_actor = actor_id' ];
		}
	}

	/**
	 * Adds the "Show all users" checkbox for privileged users.
	 *
	 * @param UsersPager $usersPager
	 * @param string &$out HTML output
	 */
	public static function onSpecialListusersHeaderForm( $usersPager, &$out ) {
		// Show this checkbox only to privileged users
		if ( $usersPager->getUser()->isAllowed( 'viewallusers' ) ) {
			$out .= Html::check(
				'showall', $usersPager->getRequest()->getVal( 'showall' ),
				[ 'id' => 'showall' ]
			) . "\u{00A0}" . Html::label(
				$usersPager->msg( 'listusers-showall' )->plain(),
				'showall'
			);
			$out .= '&nbsp;';
		}
	}
}
