<?php

namespace App\Upgrade;

class UpgradeReadinessChecker
{
    private $projectDir;

    public function __construct($projectDir)
    {
        $this->projectDir = rtrim($projectDir, '/\\');
    }

    public function check($target = '4.4')
    {
        $composer = $this->readComposer();
        $findings = array();

        $findings = array_merge($findings, $this->checkComposer($composer, $target));
        $findings = array_merge($findings, $this->checkLegacyStructure());
        $findings = array_merge($findings, $this->checkSecurityConfig());

        usort($findings, function ($a, $b) {
            $weight = array('error' => 0, 'warning' => 1, 'info' => 2, 'ok' => 3);

            return $weight[$a['level']] - $weight[$b['level']];
        });

        return $findings;
    }

    public function summarize(array $findings)
    {
        $summary = array('error' => 0, 'warning' => 0, 'info' => 0, 'ok' => 0);

        foreach ($findings as $finding) {
            if (isset($summary[$finding['level']])) {
                $summary[$finding['level']]++;
            }
        }

        return $summary;
    }

    private function readComposer()
    {
        $path = $this->projectDir . '/composer.json';

        if (!is_file($path)) {
            return array();
        }

        $data = json_decode(file_get_contents($path), true);

        return is_array($data) ? $data : array();
    }

    private function checkComposer(array $composer, $target)
    {
        $findings = array();
        $require = isset($composer['require']) && is_array($composer['require']) ? $composer['require'] : array();
        $requireDev = isset($composer['require-dev']) && is_array($composer['require-dev']) ? $composer['require-dev'] : array();
        $platformPhp = isset($composer['config']['platform']['php']) ? $composer['config']['platform']['php'] : null;

        $symfonyConstraint = isset($require['symfony/symfony']) ? $require['symfony/symfony'] : null;

        if ($symfonyConstraint) {
            $level = $this->constraintAtLeast($symfonyConstraint, $target) ? 'ok' : 'info';
            $message = $level === 'ok'
                ? 'Current constraint "' . $symfonyConstraint . '" matches the selected target.'
                : 'Current constraint is "' . $symfonyConstraint . '". First target should be 3.4.*, then 4.4.*.';
            $findings[] = $this->finding($level, 'Symfony constraint', $message);
        } else {
            $findings[] = $this->finding('warning', 'Symfony packages', 'symfony/symfony meta package is not found. Verify split Symfony packages before upgrading.');
        }

        if ($platformPhp && $this->targetAtLeast($target, '4.4') && version_compare($platformPhp, '7.1.3', '<')) {
            $findings[] = $this->finding('error', 'Composer platform PHP', 'Platform PHP is ' . $platformPhp . '. Symfony 4.4 requires at least PHP 7.1.3.');
        } elseif ($platformPhp) {
            $findings[] = $this->finding('ok', 'Composer platform PHP', 'Platform PHP is compatible with the selected target baseline.');
        }

        if ($platformPhp && version_compare($platformPhp, '8.1.0', '<')) {
            $level = $this->targetAtLeast($target, '6.4') ? 'error' : 'warning';
            $findings[] = $this->finding($level, 'Symfony 6.4 PHP target', 'Symfony 6.4 requires PHP 8.1+. Plan a runtime and Composer platform upgrade later.');
        }

        $packages = array_merge($require, $requireDev);
        $riskyPackages = array(
            'friendsofsymfony/user-bundle' => 'Replace with native Symfony Security before or during 5.4/6.4 migration.',
            'sensio/distribution-bundle' => 'Remove when moving to Symfony Flex structure.',
            'symfony/swiftmailer-bundle' => 'Replace with Symfony Mailer; Swiftmailer is legacy.',
            'twig/extensions' => 'Replace deprecated Twig extensions with twig/extra-bundle or native filters.',
            'incenteev/composer-parameter-handler' => 'Move parameters.yml values to env/config before Flex structure.',
            'sensio/framework-extra-bundle' => 'Remove after route/security annotations and ParamConverter-style entity injection have been replaced.',
            'whiteoctober/breadcrumbs-bundle' => 'Check maintenance status; consider replacing with local breadcrumb/schema service.',
        );

        foreach ($riskyPackages as $package => $message) {
            if (isset($packages[$package])) {
                $findings[] = $this->finding('warning', $package, $message . ' Current constraint: ' . $packages[$package]);
            }
        }

        if (isset($packages['doctrine/doctrine-bundle']) && !$this->constraintAtLeast($packages['doctrine/doctrine-bundle'], '2.0')) {
            $findings[] = $this->finding('warning', 'doctrine/doctrine-bundle', 'Current 1.x line must be upgraded for Symfony 5/6. Current constraint: ' . $packages['doctrine/doctrine-bundle']);
        }

        if (isset($packages['doctrine/doctrine-migrations-bundle']) && !$this->constraintAtLeast($packages['doctrine/doctrine-migrations-bundle'], '3.0')) {
            $findings[] = $this->finding('warning', 'doctrine/doctrine-migrations-bundle', 'Current line is legacy; upgrade migrations bundle before Symfony 5.4. Current constraint: ' . $packages['doctrine/doctrine-migrations-bundle']);
        }

        if (isset($packages['liip/imagine-bundle']) && !$this->constraintAtLeast($packages['liip/imagine-bundle'], '2.0')) {
            $findings[] = $this->finding('warning', 'liip/imagine-bundle', 'Current 1.x line needs compatibility check for Symfony 5/6. Current constraint: ' . $packages['liip/imagine-bundle']);
        }

        if (isset($packages['vich/uploader-bundle'])) {
            if ($this->targetAtLeast($target, '6.4') && !$this->constraintAtLeast($packages['vich/uploader-bundle'], '2.0')) {
                $findings[] = $this->finding('warning', 'vich/uploader-bundle', 'Upgrade to the 2.x line for Symfony 6.4/PHP 8.1. Current constraint: ' . $packages['vich/uploader-bundle']);
            } else {
                $findings[] = $this->finding('ok', 'vich/uploader-bundle', 'Current constraint is compatible with the selected target: ' . $packages['vich/uploader-bundle']);
            }
        }

        return $findings;
    }

    private function checkLegacyStructure()
    {
        $checks = array(
            'app/config' => 'Symfony Flex structure uses config/.',
            'src/AppBundle' => 'Symfony 4+ projects usually use App\\ namespace directly under src/.',
        );
        $findings = array();

        if (is_file($this->projectDir . '/src/Kernel.php') && is_file($this->projectDir . '/config/bundles.php')) {
            $findings[] = $this->finding('ok', 'src/Kernel.php', 'Symfony 4-style Kernel and config/bundles.php are present.');
        } elseif (is_file($this->projectDir . '/app/AppKernel.php')) {
            $findings[] = $this->finding('warning', 'app/AppKernel.php', 'Symfony 4+ uses src/Kernel.php and config/bundles.php.');
        }

        if (is_file($this->projectDir . '/app/AppKernel.php') && is_file($this->projectDir . '/src/Kernel.php')) {
            $findings[] = $this->finding('info', 'app/AppKernel.php', 'Legacy AppKernel compatibility shim can stay temporarily until all entrypoints use App\\Kernel.');
        }

        foreach ($checks as $path => $message) {
            if (file_exists($this->projectDir . '/' . $path)) {
                $findings[] = $this->finding('warning', $path, $message);
            }
        }

        if (is_dir($this->projectDir . '/templates')) {
            $findings[] = $this->finding('ok', 'templates', 'Symfony templates directory is present.');
            if (!is_dir($this->projectDir . '/app/Resources/views')) {
                $findings[] = $this->finding('ok', 'app/Resources/views', 'Legacy views directory is removed.');
            }
        } elseif (is_dir($this->projectDir . '/app/Resources/views')) {
            $findings[] = $this->finding('warning', 'app/Resources/views', 'Symfony Flex structure uses templates/.');
        }

        if (is_dir($this->projectDir . '/public')) {
            $findings[] = $this->finding('ok', 'public', 'Symfony public document root is present.');
            if (!is_dir($this->projectDir . '/web')) {
                $findings[] = $this->finding('ok', 'web', 'Legacy web/ directory is removed.');
            }
        } elseif (is_dir($this->projectDir . '/web')) {
            $findings[] = $this->finding('warning', 'web', 'Symfony Flex structure uses public/.');
        }

        return $findings;
    }

    private function checkSecurityConfig()
    {
        $path = is_file($this->projectDir . '/config/security.yml')
            ? $this->projectDir . '/config/security.yml'
            : $this->projectDir . '/app/config/security.yml';

        if (!is_file($path)) {
            return array();
        }

        $contents = file_get_contents($path);
        $findings = array();

        if (strpos($contents, 'encoders:') !== false) {
            $findings[] = $this->finding('warning', 'security.encoders', 'Symfony 5.3+/6 uses password_hashers. Keep this on the security migration checklist.');
        } elseif (strpos($contents, 'password_hashers:') !== false) {
            $findings[] = $this->finding('ok', 'security.password_hashers', 'Security password hashing uses the Symfony 5.3+ config key.');
        }

        if (strpos($contents, 'fos_user.user_provider') !== false || strpos($contents, 'fos_userbundle:') !== false) {
            $findings[] = $this->finding('warning', 'FOSUser security provider', 'Native Symfony Security should replace FOSUser provider before Symfony 6.4.');
        } elseif (strpos($contents, 'FOS\\UserBundle\\Model\\UserInterface') !== false) {
            $findings[] = $this->finding('info', 'FOSUser password encoder', 'Password encoding still targets the FOS user interface until the User entity no longer extends FOS base user.');
        }

        if (preg_match('/anonymous:\s*true\b/', $contents)) {
            $findings[] = $this->finding('warning', 'security anonymous', 'Symfony 5.4/6 security config changes anonymous/lazy authenticator behavior.');
        } elseif (preg_match('/anonymous:\s*lazy\b/', $contents)) {
            $findings[] = $this->finding('ok', 'security anonymous', 'Firewall anonymous access uses lazy mode.');
        } elseif (strpos($contents, 'lazy: true') !== false) {
            $findings[] = $this->finding('ok', 'security lazy firewall', 'Firewall uses lazy mode without the legacy anonymous key.');
        }

        if (strpos($contents, 'enable_authenticator_manager: true') !== false) {
            $findings[] = $this->finding('ok', 'security authenticator manager', 'Symfony 5.4 authenticator manager is enabled.');
        }

        return $findings;
    }

    private function hasSensioRouteSecurityAnnotations()
    {
        $controllerDir = $this->projectDir . '/src/Controller';

        if (!is_dir($controllerDir)) {
            return false;
        }

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($controllerDir));

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());

            if (strpos($contents, 'Sensio\\Bundle\\FrameworkExtraBundle\\Configuration\\Route') !== false
                || strpos($contents, 'Sensio\\Bundle\\FrameworkExtraBundle\\Configuration\\Method') !== false
                || strpos($contents, 'Sensio\\Bundle\\FrameworkExtraBundle\\Configuration\\Security') !== false
                || strpos($contents, '@Method') !== false
                || strpos($contents, '@Security') !== false
            ) {
                return true;
            }
        }

        return false;
    }

    private function finding($level, $area, $message)
    {
        return array(
            'level' => $level,
            'area' => $area,
            'message' => $message,
        );
    }

    private function targetAtLeast($target, $version)
    {
        return version_compare((string) $target, (string) $version, '>=');
    }

    private function constraintAtLeast($constraint, $version)
    {
        $normalized = ltrim((string) $constraint, '^~>=< ');
        $normalized = preg_replace('/[^0-9.].*$/', '', $normalized);
        $normalized = rtrim($normalized, '.');

        return version_compare($normalized, $version, '>=');
    }
}
