<?php

namespace Drupal\Tests\change_requests\Unit;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\change_requests\DiffService;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests diff service with yetanotherape/diff_match_patch.
 *
 * @coversDefaultClass \Drupal\change_requests\DiffService
 * @group change_requests
 */
class DiffServiceTest extends UnitTestCase {

  /**
   * The class under test.
   *
   * @var \Drupal\change_requests\DiffService
   */
  protected $diffService;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->diffService = new DiffService();
    $this->diffService->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * @covers ::getTextDiff
   */
  public function testGetTextDiff() {
    $diff = $this->diffService->getTextDiff('a', 'ab');
    $exp = "@@ -1 +1,2 @@\n a\n+b\n";
    $this->assertSame($exp, $diff);
  }

  /**
   * @covers ::applyPatchText
   */
  public function testApplyPatchText() {
    $patched = $this->diffService->applyPatchText('a', "@@ -1 +1,2 @@\n a\n+b\n", 'test_prop');
    $this->assertSame('ab', $patched["result"]);
    $this->assertTrue($patched["feedback"]["applied"]);
    $this->assertTrue($patched["feedback"]["code"] == 100);
  }

  /**
   * @covers ::applyPatchText
   */
  public function testApplyPatchTextNegotiate() {
    $patched = $this->diffService->applyPatchText('uiop', "@@ -1,15 +1,23 @@\n asdf gh\n+ qwertz\n jk yxcvb\n", 'test_prop');
    $this->assertSame('uiop', $patched["result"]);
    $this->assertNotTrue($patched["feedback"]["applied"]);
    $this->assertTrue($patched["feedback"]["code"] == 0);
    $this->assertTrue($patched["feedback"]["message"] instanceof TranslatableMarkup);
  }

  /**
   * @covers ::patchView
   */
  public function testPatchView() {
    $patched = $this->diffService->patchView("@@ -1 +1,2 @@\n a\n+b\n", "a");
    $crawler = new Crawler($patched['#markup']);
    $this->assertEquals(1, $crawler->filter('span')->count());
    $this->assertEquals(1, $crawler->filter('ins')->count());
  }

}
