<?php

namespace Drupal\config_split_ignore\Plugin\ConfigFilter;

use Drupal\config_ignore\Plugin\ConfigFilter\IgnoreFilter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a ignore filter that allows to delete the configuration entities.
 *
 * @ConfigFilter(
 *   id = "config_split_ignore",
 *   label = "Configuration Split Ignore",
 *   weight = 20
 * )
 */
class ConfigSplitIgnoreFilter extends IgnoreFilter {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $entity_storage */
    $entity_storage = $container->get('entity_type.manager')->getStorage('config_split');
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $container->get('config.factory');

    // Load the list of ignored entities from enabled splits.
    $ignored = [];

    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $config_split */
    foreach ($entity_storage->loadMultiple() as $config_split) {
      $config_name = $config_split->getConfigDependencyName();
      $config = $config_factory->get($config_name);
      if (!empty($config->get('status'))) {
        $ignored = array_merge($ignored, $config_split->getThirdPartySetting('config_split_ignore', 'entities', []));
      }
    }

    $ignored = array_unique($ignored);

    // Allow modules to alter the list of ignored entities.
    $container->get('module_handler')->invokeAll('config_split_ignore_settings_alter', [&$ignored]);

    // Set the list in the plugin configuration.
    $configuration['ignored'] = array_unique($ignored);

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function filterExists($name, $exists) {
    // The ignored configuration entity must exist in a file in config folder
    // in order to be deleted properly.
    return $exists;
  }

  /**
   * {@inheritdoc}
   */
  public function filterListAll($prefix, array $data) {
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function filterDelete($name, $delete) {
    // Allow to delete the configuration is the split becomes inactive.
    return $delete;
  }

}
