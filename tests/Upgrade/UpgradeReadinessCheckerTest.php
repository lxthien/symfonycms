<?php

namespace Tests\Upgrade;

use App\Upgrade\UpgradeReadinessChecker;
use PHPUnit\Framework\TestCase;

class UpgradeReadinessCheckerTest extends TestCase
{
    private $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/upgrade_readiness_' . uniqid();
        mkdir($this->tmpDir);
        mkdir($this->tmpDir . '/app/config', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tmpDir);
    }

    public function testDetectsLegacySymfonyUpgradeBlockers()
    {
        file_put_contents($this->tmpDir . '/composer.json', json_encode(array(
            'require' => array(
                'symfony/symfony' => '^3.3',
                'friendsofsymfony/user-bundle' => '^2.1',
                'symfony/swiftmailer-bundle' => '^2.3',
            ),
            'config' => array(
                'platform' => array('php' => '7.0'),
            ),
        )));
        file_put_contents($this->tmpDir . '/app/config/security.yml', "security:\n    encoders: []\n    providers:\n        fos_userbundle: ~\n    firewalls:\n        main:\n            anonymous: true\n");
        file_put_contents($this->tmpDir . '/app/AppKernel.php', '<?php');
        mkdir($this->tmpDir . '/src/AppBundle', 0777, true);
        mkdir($this->tmpDir . '/web', 0777, true);

        $checker = new UpgradeReadinessChecker($this->tmpDir);
        $findings = $checker->check();
        $summary = $checker->summarize($findings);

        $this->assertGreaterThan(0, $summary['error']);
        $this->assertGreaterThan(0, $summary['warning']);
        $this->assertContainsArea($findings, 'Composer platform PHP');
        $this->assertContainsArea($findings, 'friendsofsymfony/user-bundle');
        $this->assertContainsArea($findings, 'security.encoders');
        $this->assertContainsArea($findings, 'app/AppKernel.php');
    }

    public function testRecognizesSymfonyFourKernelBridge()
    {
        file_put_contents($this->tmpDir . '/composer.json', json_encode(array(
            'require' => array(
                'symfony/symfony' => '^4.4',
            ),
            'config' => array(
                'platform' => array('php' => '7.2.5'),
            ),
        )));
        file_put_contents($this->tmpDir . '/app/config/security.yml', "security:\n");
        file_put_contents($this->tmpDir . '/app/AppKernel.php', '<?php');
        mkdir($this->tmpDir . '/src', 0777, true);
        mkdir($this->tmpDir . '/config', 0777, true);
        file_put_contents($this->tmpDir . '/src/Kernel.php', '<?php');
        file_put_contents($this->tmpDir . '/config/bundles.php', '<?php return array();');

        $checker = new UpgradeReadinessChecker($this->tmpDir);
        $findings = $checker->check();

        $this->assertContainsArea($findings, 'src/Kernel.php');
    }

    private function assertContainsArea(array $findings, $area)
    {
        foreach ($findings as $finding) {
            if ($finding['area'] === $area) {
                $this->assertTrue(true);

                return;
            }
        }

        $this->fail('Expected finding area was not found: ' . $area);
    }

    private function removeDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($dir);
    }
}
