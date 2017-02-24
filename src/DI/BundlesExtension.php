<?php

namespace Thunbolt\Bundles\DI;

use Kdyby\Doctrine\DI\IEntityProvider;
use Kdyby\Translation\DI\ITranslationProvider;
use Nette\Configurator;
use Nette\DI\CompilerExtension;
use Nette\DI\Extensions\ExtensionsExtension;
use Nette\DI\Statement;
use Thunbolt\Bundles\BundleException;
use Thunbolt\Bundles\BundleHelper;
use Thunbolt\Bundles\IBundleExtension;
use Thunbolt\Bundles\IVoidInterface;

if (!interface_exists(ITranslationProvider::class)) {
	class_alias(IVoidInterface::class, ITranslationProvider::class);
}

/**
 * @internal
 */
final class BundlesExtension extends ExtensionsExtension implements ITranslationProvider, IEntityProvider {

	const BUNDLE_PREFIX = 'bundle.';

	/** @var array */
	private $transPaths = [];

	/** @var array */
	private $entityPaths = [];

	public function loadConfiguration() {
		$config = $this->getConfig();
		$hasTranslator = interface_exists(ITranslationProvider::class);

		$helper = new BundleHelper($this->compiler->getExtensions(), $config);
		foreach ($config as $name => $class) {
			if ($class instanceof Statement) {
				$reflection = new \ReflectionClass($class->getEntity());
				array_unshift($class->arguments, $helper);
				$object = $reflection->newInstanceArgs($class->arguments);
				$class = $class->getEntity();
			} else if (!class_exists($class)) {
				throw new BundleException("Bundle '$class' not exists in bundle '$name'");
			} else {
				$object= new $class($helper);
			}
			if (!$object instanceof IBundleExtension) {
				throw new BundleException("Bundle '$class' must implements " . IBundleExtension::class);
			}
			if (!$object instanceof CompilerExtension) {
				throw new BundleException("Bundle '$class' must be instance of " . CompilerExtension::class);
			}
			$this->compiler->addExtension(self::BUNDLE_PREFIX . $name, $object);
			$object->startup();

			$path = realpath($object->getBaseFolder());
			$namespace = substr($class, 0, strpos($class, '\\'));
			if ($hasTranslator && is_dir($path . '/Resources/translations')) {
				$this->transPaths[] = $path . '/Resources/translations';
			}
			if (is_dir($path . '/Model')) {
				$this->entityPaths[$namespace . '\\Model'] = $path . '/Model';
			}
		}
	}

	/**
	 * @return array
	 */
	public function getEntityMappings() {
		return $this->entityPaths;
	}

	/**
	 * @return array
	 */
	public function getTranslationResources() {
		return $this->transPaths;
	}

	/**
	 * @param Configurator $configurator
	 */
	public static function register(Configurator $configurator) {
		$configurator->defaultExtensions['bundles'] = self::class;
	}

}
