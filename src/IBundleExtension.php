<?php

namespace Thunbolt\Bundles;

interface IBundleExtension {

	/**
	 * @return void
	 */
	public function startup();

	/**
	 * @return string
	 */
	public function getBaseFolder();

}
