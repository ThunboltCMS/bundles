<?php

declare(strict_types=1);

namespace Thunbolt\Bundles\DI;

use Kdyby\Doctrine\DI\IEntityProvider;
use Kdyby\Doctrine\DI\OrmExtension;
use Kdyby\Translation\DI\ITranslationProvider;
use Kdyby\Translation\DI\TranslationExtension;
use Nette\Configurator;
use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;
use Nette\DI\Statement;
use Thunbolt\Bundles\BundleException;
use Thunbolt\Bundles\BundleHelper;
use Thunbolt\Bundles\Conflicts\IEntityProviderReplacement;
use Thunbolt\Bundles\Conflicts\ITranslationReplacement;
use Thunbolt\Bundles\IBundleExtension;

if (!interface_exists(ITranslationProvider::class)) {
	class_alias(ITranslationReplacement::class, ITranslationProvider::class);
}
if (!interface_exists(IEntityProvider::class)) {
	class_alias(IEntityProviderReplacement::class, IEntityProvider::class);
}

/**
 * @internal
 */
final class BundlesExtension extends CompilerExtension implements ITranslationProvider, IEntityProvider {

	const EXTENSION_NAME = 'bundles';

	const BUNDLE_PREFIX = 'bundle.';

	/** @var array */
	private $transPaths = [];

	/** @var array */
	private $entityPaths = [];

	public function load(): void {
		$config = $this->getConfig();
		$hasTranslator = class_exists(TranslationExtension::class);
		$hasEntityProvider = class_exists(OrmExtension::class);

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
			if ($hasEntityProvider && is_dir($path . '/Model')) {
				$this->entityPaths[$namespace . '\\Model'] = $path . '/Model';
			}
		}
	}

	public function getEntityMappings(): array {
		return $this->entityPaths;
	}

	public function getTranslationResources(): array {
		return $this->transPaths;
	}

	public static function register(Configurator $configurator, ?string $extensionName = NULL): void {
		$extensionName = $extensionName ?: self::EXTENSION_NAME;
		$configurator->defaultExtensions[$extensionName] = self::class;
		$configurator->onCompile[] = function (Configurator $configurator, Compiler $compiler) use ($extensionName) {
			$self = new self();
			$self->setCompiler($compiler, $extensionName);
			$self->setConfig($compiler->getConfig()[$extensionName] ?? []);
			$self->load();
		};
	}

}
