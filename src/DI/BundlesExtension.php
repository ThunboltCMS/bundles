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
use Thunbolt\Bundles\BundlesInfo;
use Thunbolt\Bundles\Conflicts\IEntityProviderReplacement;
use Thunbolt\Bundles\Conflicts\ITranslationReplacement;
use Thunbolt\Bundles\IBundle;
use Thunbolt\Bundles\IBundlesInfoProvider;

if (!interface_exists(ITranslationProvider::class)) {
	class_alias(ITranslationReplacement::class, ITranslationProvider::class);
}
if (!interface_exists(IEntityProvider::class)) {
	class_alias(IEntityProviderReplacement::class, IEntityProvider::class);
}

/**
 * @internal
 */
final class BundlesExtension extends CompilerExtension implements ITranslationProvider, IEntityProvider, IBundlesInfoProvider {

	private const EXTENSION_NAME = 'bundles';
	private const ENTITY_PATH = 'Entities';

	/** @var array */
	private $transPaths = [];

	/** @var array */
	private $entityPaths = [];

	/** @var BundleHelper */
	private $helper;

	/** @var BundlesInfo */
	private $infoObject;

	/** @var bool */
	private $hasTranslator;

	/** @var bool */
	private $hasDoctrine;

	public function load(): void {
		$bundles = $this->getConfig();
		$namespaces = [];

		$this->hasTranslator = class_exists(TranslationExtension::class);
		$this->hasDoctrine = class_exists(OrmExtension::class);
		$this->helper = new BundleHelper($this->compiler, $bundles);

		foreach ($bundles as $name => $class) {
			$object = $this->createExtension((string) $name, $class);
			$object->startup();

			$path = realpath($object->getBaseFolder());
			$namespaces[$name] = $namespace = $this->extractNamespace($object, $class);

			// registrations
			$this->registerTranslatorPath($path);
			$this->registerDoctrinePath($path, $namespace);
		}

		$this->infoObject = new BundlesInfo($namespaces);
	}

	public function getBundlesInfo(): BundlesInfo {
		return $this->infoObject;
	}

	public function getEntityMappings(): array {
		return $this->entityPaths;
	}

	public function getTranslationResources(): array {
		return $this->transPaths;
	}

	private function registerTranslatorPath(string $path): void {
		if (!$this->hasTranslator) {
			return;
		}

		if (is_dir($modelPath = $path . '/translations')) {
			$this->transPaths[] = $modelPath;
		}
	}

	private function registerDoctrinePath(string $path, string $namespace): void {
		if (!$this->hasDoctrine) {
			return;
		}

		if (is_dir($doctrinePath = $path . '/' . self::ENTITY_PATH)) {
			$this->entityPaths[$namespace ? $namespace . '\\' . self::ENTITY_PATH : self::ENTITY_PATH] = $doctrinePath;
		}
	}

	private function extractNamespace(IBundle $object, string $class): string {
		if (method_exists($object, 'getNamespace')) {
			$namespace = $object->getNamespace();
		} else {
			$namespace = substr($class, 0, strrpos($class, '\\'));
		}

		return $namespace;
	}

	private function createExtension(string $name, &$class): IBundle {
		$classArguments = [$this->helper, $name];
		if ($class instanceof Statement) {
			$reflection = new \ReflectionClass($class->getEntity());
			array_unshift($class->arguments, ...$classArguments);
			$object = $reflection->newInstanceArgs($class->arguments);
			$class = $class->getEntity();

		} else if (!class_exists($class)) {
			throw new BundleException("Bundle '$class' not exists in bundle '$name'");

		} else {
			$object= new $class(...$classArguments);

		}

		if (!$object instanceof IBundle) {
			throw new BundleException("Bundle '$class' must implements " . IBundle::class);
		}

		return $object;
	}

	public static function register(Configurator $configurator, ?string $extensionName = self::EXTENSION_NAME): void {
		$configurator->onCompile[] = function (Configurator $configurator, Compiler $compiler) use ($extensionName) {
			$self = new self();
			$self->setCompiler($compiler, $extensionName);
			$self->setConfig($compiler->getConfig()[$extensionName] ?? []);
			$self->load();

			$compiler->addExtension($extensionName, $self);
		};
	}

}
