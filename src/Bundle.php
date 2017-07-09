<?php

declare(strict_types=1);

namespace Thunbolt\Bundles;

use Nette\SmartObject;

abstract class Bundle implements IBundle {

	use SmartObject;

	/** @var BundleHelper */
	protected $helper;

	/** @var string */
	protected $name;

	public function __construct(BundleHelper $helper, string $name) {
		$this->helper = $helper;
		$this->name = $name;
	}

	public function startup(): void {}

}
