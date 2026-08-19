<?php

namespace Tests;

use CodeIgniter\Test\CIUnitTestCase;

/** @internal */
final class PwaAssetsTest extends CIUnitTestCase
{
    public function testManifestAndRequiredIconsAreValid(): void
    {
        $manifestPath = FCPATH . 'manifest.webmanifest';
        $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('./', $manifest['scope']);
        $this->assertNotEmpty($manifest['icons']);

        foreach ($manifest['icons'] as $icon) {
            $path = FCPATH . ltrim((string) $icon['src'], './');
            $this->assertFileExists($path);
            $this->assertNotFalse(getimagesize($path));
        }
    }

    public function testOfflineShellDoesNotCacheAuthenticatedPages(): void
    {
        $serviceWorker = (string) file_get_contents(FCPATH . 'sw.js');

        $this->assertStringContainsString("request.mode === 'navigate'", $serviceWorker);
        $this->assertStringContainsString("fetch(request).catch(() => caches.match(appUrl('offline.html')))", $serviceWorker);
        $this->assertStringNotContainsString("cache.put(request, response", $serviceWorker);
        $this->assertStringContainsString("offline.html", $serviceWorker);
    }

    public function testNativeMobileAssetsAreAvailable(): void
    {
        $this->assertFileExists(FCPATH . 'assets/css/native-mobile.css');
        $this->assertFileExists(FCPATH . 'assets/js/native-mobile.js');
        $this->assertFileExists(FCPATH . 'assets/js/pwa.js');
        $this->assertFileExists(APPPATH . 'Views/dashboard_mobile.php');
    }

    public function testTeacherPagesShareNativeMobileDesignContract(): void
    {
        $css = (string) file_get_contents(FCPATH . 'assets/css/native-mobile.css');
        $layout = (string) file_get_contents(APPPATH . 'Views/layouts/admin.php');
        $dashboard = (string) file_get_contents(APPPATH . 'Views/dashboard_mobile.php');
        $assignmentDocument = (string) file_get_contents(APPPATH . 'Views/assignments/documents/teacher.php');

        $this->assertStringContainsString('.persona-teacher.native-role-page', $css);
        $this->assertStringContainsString('text-decoration:none!important', $css);
        $this->assertStringContainsString("\$bodyClasses[] = 'persona-teacher'", $layout);
        $this->assertStringContainsString("'teaching'           => 'Ruang Mengajar Harian'", $layout);
        $this->assertStringContainsString("'bar-chart-3','Beban Saya'", $dashboard);
        $this->assertStringNotContainsString('chart-no-axes-column', $dashboard);
        $this->assertStringContainsString('viewport-fit=cover', $assignmentDocument);
        $this->assertStringContainsString("base_url('dashboard')", $assignmentDocument);
    }
}
