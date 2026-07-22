<?php

namespace Tests\Database;

use App\Services\CurriculumImportService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CurriculumImportTemplateTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = 'App';

    public function testTemplateIsValidAndMatchesImportContract(): void
    {
        $path = CurriculumImportService::generateTemplate();

        try {
            $this->assertFileExists($path);
            $workbook = IOFactory::load($path);
            $this->assertSame(['Data Import', 'Panduan', 'Referensi'], $workbook->getSheetNames());
            $headers = $workbook->getSheetByName('Data Import')->rangeToArray('A1:T1')[0];
            $this->assertSame('unit', $headers[0]);
            $this->assertSame('effective_source', $headers[9]);
            $this->assertSame('notes', $headers[19]);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
