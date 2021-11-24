<?php
require_once 'src/getter.php';

use PHPUnit\Framework\TestCase;

final class GetterTest extends TestCase
{
    use Getter;

    private $_propA = 'propA';

    private function propB()
    {
        return 'propB';
    }

    public function testUnderscorePrivate()
    {
        $this->assertSame('propA', $this->propA);
        $this->assertSame('propA', $this->propA());
    }

    public function testPrivateFun()
    {
        $this->assertSame('propB', $this->propB);
    }

    public function testNonExists()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Try to call undefined method "GetterTest::propC".');
        $this->assertSame('propC', $this->propC);
    }
}
