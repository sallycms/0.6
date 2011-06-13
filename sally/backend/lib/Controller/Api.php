<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_Api extends sly_Controller_Ajax {
	protected function index() {
		print 'Welcome to the API controller.';
	}

	protected function linklistbutton_search() {
		$query  = sly_get('q', 'string');
		$sql    = sly_DB_Persistence::getInstance();
		$prefix = sly_Core::config()->get('DATABASE/TABLE_PREFIX');
		$user   = sly_Util_User::getCurrentUser();

		$sql->query('SELECT id,clang FROM '.$prefix.'article WHERE name LIKE ? GROUP BY id', array("%$query%"));

		foreach ($sql as $row) {
			$article = sly_Util_Article::findById($row['id'], $row['clang']);

			if ($article && sly_Util_Category::hasPermissionOnCategory($user, $row['id'])) {
				$name = str_replace('|', '/', sly_html($article->getName()));
				$path = $article->getParentTree();

				foreach ($path as $idx => $cat) {
					$path[$idx] = str_replace('|', '/', sly_html($cat->getName()));
				}

				if (count($path) > 3) {
					$path = array_slice($path, -2);
					array_unshift($path, '&hellip;');
				}

				array_unshift($path, '(Homepage)');
				printf("%s|%d|%s|%d\n", $name, $row['id'], implode(' &gt; ', $path), $row['clang']);
			}
		}
	}

	protected function checkPermission() {
		$user = sly_Util_User::getCurrentUser();
		return !is_null($user);
	}
}
