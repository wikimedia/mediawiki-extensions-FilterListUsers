<?php
/**
 * FilterListUsers -- filters out users that haven't edited from Special:ListUsers
 *
 * @file
 * @ingroup Extensions
 * @date 23 June 2015
 * @author Jack Phoenix <jack@countervandalism.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

// Extension credits that will show up on Special:Version
$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'FilterListUsers',
	'version' => '1.4',
	'author' => 'Jack Phoenix',
	'descriptionmsg' => 'filterlistusers-desc',
	'url' => 'https://www.mediawiki.org/wiki/Extension:FilterListUsers',
);

// New user right, required to view all users in Special:ListUsers
$wgAvailableRights[] = 'viewallusers';
$wgGroupPermissions['sysop']['viewallusers'] = true;

// i18n files
$wgMessagesDirs['FilterListUsers'] = __DIR__ . '/i18n';

$wgAutoloadClasses['FilterListUsers'] = __DIR__ . '/FilterListUsers.class.php';

$wgHooks['SpecialListusersQueryInfo'][] = 'FilterListUsers::onSpecialListusersQueryInfo';
$wgHooks['SpecialListusersHeaderForm'][] = 'FilterListUsers::onSpecialListusersHeaderForm';