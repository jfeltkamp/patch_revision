<?php

namespace Drupal\patch_revision;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Class DiffService.
 */
class DiffService {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a new DiffService object.
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Get a git-diff between two strings.
   *
   * @param $str_src string
   *   The source string.
   * @param $str_target string
   *   The overridden string.
   *
   * @return string
   *   The git diff.
   */
  public function getDiff($str_src, $str_target) {

    $process_str = "git diff $(echo \"{$str_src}\" | git hash-object -w --stdin) $(echo \"{$str_target}\" | git hash-object -w --stdin)  --word-diff";

    $process = new Process($process_str);
    $process->run();

    // executes after the command finishes
    if (!$process->isSuccessful()) {
      throw new ProcessFailedException($process);
    }

    return $process->getOutput();
  }

}
