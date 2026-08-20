<?php

namespace Tests\Unit;

use App\Services\AdaptiveModeService;
use App\Services\DifferentiationService;
use App\Services\MasteryHeatmapService;
use App\Services\RubricGeneratorService;
use CodeIgniter\Test\CIUnitTestCase;

final class SmartAnalyticsTest extends CIUnitTestCase
{
    public function testRubricGeneratorDetectsBloomLevelAndBuilds4Tiers(): void
    {
        $service = new RubricGeneratorService();

        // C4: Menganalisis
        $rubric = $service->generateForObjective('Menganalisis arsitektur sistem jaringan komputer');
        $this->assertSame('C4', $rubric['detected_bloom_level']);
        $this->assertCount(4, $rubric['levels']);
        $this->assertSame('NEEDS_SUPPORT', $rubric['levels'][0]['tier']);
        $this->assertSame('DEVELOPING', $rubric['levels'][1]['tier']);
        $this->assertSame('ACHIEVED', $rubric['levels'][2]['tier']);
        $this->assertSame('ADVANCED', $rubric['levels'][3]['tier']);

        // C6: Merancang
        $rubricC6 = $service->generateForObjective('Merancang algoritma pemrograman modular');
        $this->assertSame('C6', $rubricC6['detected_bloom_level']);

        // C1: Mengidentifikasi
        $rubricC1 = $service->generateForObjective('Mengidentifikasi komponen perangkat keras');
        $this->assertSame('C1', $rubricC1['detected_bloom_level']);
    }

    public function testDifferentiationServiceBuilds3Dimensions(): void
    {
        $service = new DifferentiationService();
        $strategies = $service->generateStrategies('Struktur Data Pohon Biner', 'Informatika', 'E');

        $this->assertArrayHasKey('dimensions', $strategies);
        $this->assertArrayHasKey('content', $strategies['dimensions']);
        $this->assertArrayHasKey('process', $strategies['dimensions']);
        $this->assertArrayHasKey('product', $strategies['dimensions']);

        $contentTiers = $strategies['dimensions']['content']['tiers'];
        $this->assertArrayHasKey('tier_1', $contentTiers);
        $this->assertArrayHasKey('tier_2', $contentTiers);
        $this->assertArrayHasKey('tier_3', $contentTiers);
        $this->assertStringContainsString('Perlu Pendampingan', $contentTiers['tier_1']['group']);
    }

    public function testAdaptiveModeServiceMatchesUnpluggedStrategies(): void
    {
        $service = new AdaptiveModeService();

        $algoAdapt = $service->getUnpluggedAlternative('Algoritma Pencarian Linear');
        $this->assertSame('algoritma', $algoAdapt['matched_keyword']);
        $this->assertNotEmpty($algoAdapt['unplugged_title']);
        $this->assertNotEmpty($algoAdapt['materials_needed']);

        $networkAdapt = $service->getUnpluggedAlternative('Topologi Jaringan Lokal');
        $this->assertSame('jaringan', $networkAdapt['matched_keyword']);

        $cryptoAdapt = $service->getUnpluggedAlternative('Keamanan Data dan Enkripsi');
        $this->assertSame('keamanan', $cryptoAdapt['matched_keyword']);

        $defaultAdapt = $service->getUnpluggedAlternative('Materi Teori Umum');
        $this->assertSame('general', $defaultAdapt['matched_keyword']);
    }

    public function testMasteryHeatmapColorConfigurations(): void
    {
        $this->assertArrayHasKey('NEEDS_SUPPORT', MasteryHeatmapService::RESULT_COLORS);
        $this->assertArrayHasKey('DEVELOPING', MasteryHeatmapService::RESULT_COLORS);
        $this->assertArrayHasKey('ACHIEVED', MasteryHeatmapService::RESULT_COLORS);
        $this->assertArrayHasKey('ADVANCED', MasteryHeatmapService::RESULT_COLORS);

        $this->assertSame('#dc3545', MasteryHeatmapService::RESULT_COLORS['NEEDS_SUPPORT']['bg']);
        $this->assertSame('#198754', MasteryHeatmapService::RESULT_COLORS['ACHIEVED']['bg']);
    }
}
