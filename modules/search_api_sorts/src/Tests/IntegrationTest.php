<?php

namespace Drupal\search_api_sorts\Tests;

use Drupal\Core\Url;
use Drupal\search_api\Tests\ExampleContentTrait;
use Drupal\search_api\Tests\WebTestBase;

/**
 * Tests the default functionality of Search API sorts.
 *
 * @group search_api_sorts
 */
class IntegrationTest extends WebTestBase {

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
  public static $modules = array(
    'node',
    'block',
    'search_api',
    'search_api_test_backend',
    'search_api_sorts_test_views',
    'search_api_sorts',
  );

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create some nodes.
    $this->drupalCreateNode(array('type' => 'page', 'title' => 'node - 1'));
    $this->drupalCreateNode(array('type' => 'page', 'title' => 'node - 2'));
    $this->drupalCreateNode(array('type' => 'page', 'title' => 'node - 3'));
    $this->drupalCreateNode(array('type' => 'page', 'title' => 'node - 4'));

    // Do not use a batch for tracking the initial items after creating an
    // index when running the tests via the GUI. Otherwise, it seems Drupal's
    // Batch API gets confused and the test fails.
    \Drupal::state()->set('search_api_use_tracking_batch', FALSE);

    // Create an index and server to work with.
    $this->getTestServer();
    $this->getTestIndex();

    $this->indexId = 'database_search_index';
    $this->escapedDisplayId = 'views_page---search_api_sorts_test_view__page_1';
    $this->displayId = 'views_page:search_api_sorts_test_view__page_1';

    // Log in, so we can test all the things.
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests sorting.
   */
  public function testFramework() {
    $this->drupalLogin($this->adminUser);

    // Add sorting on ID.
    $this->drupalGet('admin/config/search/search-api/index/' . $this->indexId . '/sorts');
    $this->drupalGet('admin/config/search/search-api/index/' . $this->indexId . '/sorts/' . $this->escapedDisplayId);
    $edit = [
      'sorts[id][status]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, $this->t('Save settings'));

    $block_settings = [
      'region' => 'footer',
      'id' => 'sorts_id',
    ];
    $this->drupalGet('admin/structure/block');
    $this->drupalPlaceBlock('search_api_sorts_block:' . $this->displayId, $block_settings);

    // Make sure the block is available and the ID link is shown.
    $this->drupalGet('search-api-test');
    $this->assertResponse(200);
    $this->assertLink('ID');

    // Click on the link and assert that the url now has changed.
    $this->clickLinkPartialName('ID');
    $this->assertResponse(200);
    $url = Url::fromUserInput('/search-api-test', ['query' => ['sort' => 'id', 'order' => 'asc']]);
    $this->assertUrl($url);

    // Click on the link again and assert that the url is now changed again.
    $this->clickLinkPartialName('ID');
    $this->assertResponse(200);
    $url = Url::fromUserInput('/search-api-test', ['query' => ['sort' => 'id', 'order' => 'desc']]);
    $this->assertUrl($url);
  }

}
