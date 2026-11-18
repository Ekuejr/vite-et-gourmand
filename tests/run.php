<?php
/**
 * Lanceur de tests de secours, utilisable SANS Composer :   php tests/run.php
 * Avec Composer installé, préférer :                        vendor/bin/phpunit --testdox tests
 * (les classes de test sont écrites au format PHPUnit et fonctionnent avec les deux.)
 */
declare(strict_types=1);

namespace PHPUnit\Framework;

require dirname(__DIR__) . '/src/autoload.php';

if (!class_exists(TestCase::class)) {
    /** Mini-implémentation des assertions utilisées dans nos tests. */
    abstract class TestCase
    {
        private ?string $expected = null;
        public int $assertions = 0;

        protected function assertSame(mixed $expected, mixed $actual, string $msg = ''): void
        {
            $this->assertions++;
            if ($expected !== $actual) {
                throw new \AssertionError(sprintf('%s attendu %s, obtenu %s', $msg ? "$msg :" : '', var_export($expected, true), var_export($actual, true)));
            }
        }

        protected function assertTrue(mixed $v, string $msg = ''): void
        {
            $this->assertSame(true, $v, $msg);
        }

        protected function assertFalse(mixed $v, string $msg = ''): void
        {
            $this->assertSame(false, $v, $msg);
        }

        protected function expectException(string $class): void
        {
            $this->expected = $class;
        }

        public function runTest(string $method): void
        {
            try {
                $this->$method();
            } catch (\Throwable $e) {
                if ($this->expected !== null && $e instanceof $this->expected) {
                    $this->assertions++;
                    return;
                }
                throw $e;
            }
            if ($this->expected !== null) {
                throw new \AssertionError("Exception {$this->expected} attendue");
            }
        }
    }
}

$passed = $failed = 0;
foreach (glob(__DIR__ . '/*Test.php') as $file) {
    require_once $file;
    $class = 'Tests\\' . basename($file, '.php');
    echo "\n" . $class . "\n";
    foreach (get_class_methods($class) as $method) {
        if (!str_starts_with($method, 'test')) {
            continue;
        }
        $test = new $class();
        try {
            $test->runTest($method);
            $passed++;
            echo "  [OK]    $method\n";
        } catch (\Throwable $e) {
            $failed++;
            echo "  [ECHEC] $method : " . $e->getMessage() . "\n";
        }
    }
}
echo sprintf("\n%d test(s) réussi(s), %d échec(s)\n", $passed, $failed);
exit($failed > 0 ? 1 : 0);
