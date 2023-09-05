<?php
namespace Drupal\news_section\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
/**
 * Provides a 'NewsSection' Block.
 *
 * @Block(
 *   id = "news_section_block",
 *   admin_label = @Translation("News section block"),
 * )
 */
class NewsSectionBlock extends BlockBase {
  /**
   * This is entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * This constructor will be used for initializing the objects.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   This will be used to fetch the nodes.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $user = \Drupal::routeMatch()->getParameter('user');
    if ($user instanceof User) {
      $uid = $user->id();
      $user = User::load($uid);
      $genre = $user->get('field_genre')->getValue()[0]['target_id'];
      $users = $this->entityTypeManager->getStorage('user')->getQuery()
        ->condition('field_genre', $genre)
        ->accessCheck(FALSE)
        ->execute();
      unset($users[$uid]);
      $query = $this->entityTypeManager->getStorage('node')->getQuery()
        ->condition('type', 'news')
        ->condition('uid', $users, 'IN')
        ->range(0, 5)
        ->accessCheck(FALSE)
        ->sort('created', 'DESC');
      $nids = $query->execute();
      if (!$nids) {
        return ;
      }
      $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
      foreach ($nodes as $node) {
        $node_titles[] =  $node->label();
      }
    }
    foreach ($node_titles as $node_title) {
      $build[] = [
        '#markup' => "<p>$node_title</p>",
      ];
    }
    return $build;
  }
}
