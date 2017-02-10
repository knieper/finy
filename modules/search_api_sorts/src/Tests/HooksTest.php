<?php

namespace Drupal\search_api_sorts\Tests;

use Drupal\search_api\Entity\Index;
use Drupal\search_api\Tests\ExampleContentTrait;
use Drupal\search_api\Tests\WebTestBase;

/**
 * Tests the Search API sorts hooks.
 *
 * @group search_api_sorts
 */
class HooksTest extends WebTestBase {

  use ExampleContentTrait;

  /**
   * The ID of the search index used for this test.
   *
   * @var string
   */
  protected $indexId;

  /**
   * The ID of the search display used for this test.
   *
   * @var string
   */
  protected $displayId;

  /**
   * The escaped ID of the search display used for this test.
   *
   * @var string
   */
  protected $escapedDisplayId;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'block',
    'search_api',
    'search_api_test_backend',
    'search_api_test_db',
    'search_api_sorts',
    'search_api_sorts_test_views',
    'search_api_sorts_test_hook',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Add a test server and index.
    $this->getTestServer();
    $this->indexId = 'database_search_index';
    $this->escapedDisplayId = 'views_page---search_api_sorts_test_view__page_1';
    $this->displayId = 'views_page:search_api_sorts_test_view__page_1';

    // Set up example structure and content and populate the test index with
    // that content.
    $this->setUpExampleStructure();
    $this->insertExampleContent();

    \Drupal::getContainer()
      ->get('search_api.index_task_manager')
      ->addItemsAll(Index::load($this->indexId));
    $this->indexItems($this->indexId);
  }

  /**
   * Tests sorting.
   */
  public function testHookDefaultSortsAlter() {
    $this->drupalLogin($this->adminUser);

    // Add a sorting on the ID field.
    $this->drupalGet('admin/config/search/search-api/index/' . $this->indexId . '/fields');
    $sorts_config = 'admin/config/search/search-api/index/' . $this->indexId . '/sorts/' . $this->escapedDisplayId;
    $this->drupalGet($sorts_config);
    $edit = ['sorts[id][status]' => TRUE, 'default_sort' => 'id'];
    $this->drupalPostForm(NULL, $edit, $this->t('Save settings'));

    // Add and place the sorts block in the footer.
    $block_settings = ['region' => 'footer', 'id' => 'sorts-id'];
    $this->drupalPlaceBlock('search_api_sorts_block:' . $this->displayId, $block_settings);

    // Set the sorting to descending.
    \Drupal::state()->set('search_api_sorts_default_sort', 'desc');

    // Go to the search page and check that the drupal_set_message() has made
    // output to the search page. And make sure that the positions are actually
    // in descending order.
    $this->drupalGet('search-api-test');
    $this->assertResponse(200);
    $this->assertText('Hook hook_search_api_sorts_default_sort_alter');
    $this->assertLink('ID');
    $this->assertPositions([
      'default | bar baz',
      'default | foo baz',
      'default | foo test foobuz',
    ]);

    // Revert the sorting output.
    \Drupal::state()->set('search_api_sorts_default_sort', 'asc');

    // Go to the search page and check that the drupal_set_message() has made
    // output to the search page. And make sure that the positions are actually
    // in ascending order.
    $this->drupalGet('search-api-test');
    $this->assertResponse(200);
    $this->assertText('Hook hook_search_api_sorts_default_sort_alter');
    $this->assertLink('ID');
    $this->assertPositions([
      'default | foo test foobuz',
      'default | foo baz',
      'default | bar baz',
    ]);
  }

  /**
   * Asserts that x is before y in the result.
   *
   * @param array $params
   *   An array of strings to assert positions for.
   */
  protected function assertPositions($params) {
    foreach ($params as $k => $string) {
      $this->assertRaw($string);

      if ($k > 0) {
        $x_position = strpos($this->getRawContent(), $params[$k - 1]);
        $y_position = strpos($this->getRawContent(), $params[$k]);

        $this->assertTrue($x_position < $y_position, 'Position of ' . $params[$k - 1] . ' is before ' . $params[$k]);
      }
    }
  }

}
