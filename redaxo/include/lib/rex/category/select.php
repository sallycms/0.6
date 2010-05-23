<?php

class rex_category_select extends rex_select
{
	public $ignore_offlines;
	public $clang;
	public $check_perms;

	public function __construct($ignore_offlines = false, $clang = false, $check_perms = true, $add_homepage = true)
	{
		$this->ignore_offlines = (boolean) $ignore_offlines;
		$this->clang           = $clang;
		$this->check_perms     = (boolean) $check_perms;

		if ($add_homepage) {
			$this->addOption('Homepage', 0);
		}
		
		$cats = OOCategory::getRootCategories($ignore_offlines, $clang);

		if ($cats) {
			foreach ($cats as $cat) $this->addCatOption($cat);
		}

		parent::__construct();
	}

	public function addCatOption($cat)
	{
		global $REX;
		if (empty($cat)) return;

		if (!$this->check_perms || $this->check_perms && $REX['USER']->hasCategoryPerm($cat->getId())) {
			$this->addOption($cat->getName(), $cat->getId(), $cat->getId(), $cat->getParentId());
			$children = $cat->getChildren($this->ignore_offlines, $this->clang);
			
			if (is_array($children)) {
				foreach ($children as $child) $this->addCatOption($child);
			}
		}
	}
}
