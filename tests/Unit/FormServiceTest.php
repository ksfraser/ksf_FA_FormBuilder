<?php
declare(strict_types=1);

namespace Tests\Unit;

use ksfraser\FrontAccounting\FormBuilder\Service\FormService;
use PHPUnit\Framework\TestCase;

/**
 * @BABOK Related: UT-FB-001-006-001
 * @since 1.0.0
 */
class FormServiceTest extends TestCase
{
    public function testFormServiceInstantiable(): void
    {
        $service = new FormService();
        $this->assertInstanceOf(FormService::class, $service);
    }

    public function testFindFormBySlugReturnsNullWhenNoDb(): void
    {
        $service = new FormService();
        $result = $service->findFormBySlug('nonexistent');
        $this->assertNull($result);
    }

    public function testFindFormByIdReturnsNullWhenNoDb(): void
    {
        $service = new FormService();
        $result = $service->findFormById(999);
        $this->assertNull($result);
    }

    public function testGetActiveFormsReturnsEmptyWhenNoDb(): void
    {
        $service = new FormService();
        $result = $service->getActiveForms();
        $this->assertSame([], $result);
    }

    public function testSearchFormsReturnsEmptyWhenNoDb(): void
    {
        $service = new FormService();
        $result = $service->searchForms([]);
        $this->assertSame([], $result);
    }
}
